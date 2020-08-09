<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Buzz\Browser;
use Buzz\Client\Curl;
use Exception;
use Invoker\InvokerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class RuntimeHandler implements RuntimeHandlerInterface
{
    use EnvironmentTrait;

    private ContextFactoryInterface $contextFactory;
    private EventDispatcherInterface $eventDispatcher;
    private InvokerInterface $invoker;
    private LoggerInterface $logger;

    private Browser $browser;

    public function __construct(
        ContextFactoryInterface $contextFactory,
        EventDispatcherInterface $eventDispatcher,
        InvokerInterface $invoker,
        LoggerInterface $logger
    ) {
        $this->contextFactory = $contextFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->invoker = $invoker;
        $this->logger = $logger;

        $this->browser = $this->createBrowser();
    }

    public function __invoke(): void
    {
        $this->eventDispatcher->dispatch(new Events\StartEvent());

        while (true) {
            try {
                $invocation = $this
                    ->browser
                    ->request(
                        'GET',
                        $this->url('runtime/invocation/next')
                    );

                $this->propagateTraceId($invocation);

                $event = $this->getEvent($invocation);

                $context = $this
                    ->contextFactory
                    ->createContext(
                        $invocation,
                        $this->logger
                    );

                /**
                 * @var callable
                 **/
                $handler = $this->getEnv('_HANDLER');

                $this
                    ->browser
                    ->request(
                        'POST',
                        $this->url("runtime/invocation/{$context->getAwsRequestId()}/response"),
                        [],
                        $this->invoker->call(
                            $handler,
                            [
                                'event' => $event,
                                'context' => $context,
                            ]
                        )
                    );
            } catch (Throwable $exception) {
                $this->postError($context ?? null, $exception);
            }
        }
    }

    private function createBrowser(): Browser
    {
        $factory = new Psr17Factory();
        $client = new Curl($factory);

        return new Browser($client, $factory);
    }

    /**
     * @return array<string>
     */
    private function getEvent(ResponseInterface $invocation): array
    {
        $body = (string) $invocation->getBody();

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function propagateTraceId(ResponseInterface $invocation): void
    {
        if ($header = $invocation->getHeaderLine('lambda-runtime-trace-id')) {
            putenv("_X_AMZN_TRACE_ID={$header}");
        }
    }

    private function postError(?ContextInterface $context, Throwable $exception): void
    {
        $awsRequestId = $context
            ? $context->getAwsRequestId()
            : null;

        $path = $awsRequestId
            ? "runtime/invocation/{$awsRequestId}/error"
            : 'runtime/init/error';

        $this
            ->browser
            ->request(
                'POST',
                $this->url($path),
                [
                    'Lambda-Runtime-Function-Error-Type' => 'Unhandled',
                ],
                $this->transformThrowable($exception)
            );

        if (!$awsRequestId) {
            exit(1);
        }
    }

    /**
     * @return string
     */
    private function transformThrowable(Throwable $exception): string
    {
        $this->logger->debug('Exception', ['exception' => $exception]);

        return json_encode(
            [
                'errorMessage' => sprintf(
                    '%s %s:%d',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ),
                'errorType' => get_class($exception),
                'trace' => $exception->getTrace(),
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    private function url(string $path): string
    {
        return sprintf(
            'http://%s/2018-06-01/%s',
            $this->getEnv('AWS_LAMBDA_RUNTIME_API'),
            $path
        );
    }

    private function taskPath(string $path = ''): string
    {
        $path = realpath(
            sprintf(
                '%s%s%s',
                $this->getEnv('LAMBDA_TASK_ROOT'),
                DIRECTORY_SEPARATOR,
                $path
            )
        );

        if ($path) {
            return $path;
        }

        throw new Exception('File not found: ' . $path);
    }
}
