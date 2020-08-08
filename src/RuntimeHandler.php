<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;
use Invoker\InvokerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RuntimeHandler implements RuntimeHandlerInterface
{
    use EnvironmentTrait;

    private ClientInterface $client;
    private ContextFactoryInterface $contextFactory;
    private EventDispatcherInterface $eventDispatcher;
    private InvokerInterface $invoker;
    private LoggerInterface $logger;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ClientInterface $client,
        ContextFactoryInterface $contextFactory,
        EventDispatcherInterface $eventDispatcher,
        InvokerInterface $invoker,
        LoggerInterface $logger,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->contextFactory = $contextFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->invoker = $invoker;
        $this->logger = $logger;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function __invoke(): void
    {
        $this->eventDispatcher->dispatch(new Events\StartEvent());

        while (true) {
            try {
                $invocation = $this->sendRequest('GET', 'runtime/invocation/next');
                $this->propagateTraceId($invocation);

                $event = $this->getEvent($invocation);
                $context = $this
                    ->contextFactory
                    ->createContext(
                        $invocation,
                        $this->logger
                    );

                $this->sendRequest(
                    'POST',
                    "runtime/invocation/{$context->getAwsRequestId()}/response",
                    [],
                    $this->invoker->call(
                        $this->getEnv('_HANDLER'),
                        [
                            'event' => $event,
                            'context' => $context,
                        ]
                    )
                );
            } catch (Exception $exception) {
                $this->postError($context ?? null, $exception);
            }
        }
    }

    /**
     * @return array<string>
     */
    private function getEvent(ResponseInterface $invocation): array
    {
        $body = (string) $invocation->getBody();

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function propagateTraceId(ResponseInterface $response): void
    {
        if ($header = $response->getHeaderLine('lambda-runtime-trace-id')) {
            putenv("_X_AMZN_TRACE_ID={$header}");
        }
    }

    /**
     * @param array<string> $headers
     */
    private function sendRequest(
        string $method,
        string $path,
        array $headers = [],
        ?string $body = null
    ): ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $this->url($path));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (is_string($body)) {
            $stream = $this->streamFactory->createStream($body);
            $request = $request->withBody($stream);
        }

        return $this->client->sendRequest($request);
    }

    private function postError(?ContextInterface $context, Exception $exception): void
    {
        $awsRequestId = $context
            ? $context->getAwsRequestId()
            : null;

        $path = $awsRequestId
            ? "runtime/invocation/{$awsRequestId}/error"
            : 'runtime/init/error';

        $this->sendRequest(
            'POST',
            $path,
            [
                'Lambda-Runtime-Function-Error-Type' => 'Unhandled',
            ],
            $this->transformException($exception)
        );

        if (!$awsRequestId) {
            $this->logger->debug('Error with no AWS Request ID, exiting');
            exit(1);
        }
    }

    /**
     * @return string
     */
    private function transformException(Exception $exception): string
    {
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
            getenv('AWS_LAMBDA_RUNTIME_API'),
            $path
        );
    }

    private function taskPath(string $path = ''): string
    {
        $path = realpath(
            sprintf(
                '%s%s%s',
                getenv('LAMBDA_TASK_ROOT'),
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
