<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;
use Invoker\InvokerInterface;
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

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var InvokerInterface
     */
    private $invoker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContextFactoryInterface
     */
    private $contextFactory;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        InvokerInterface $invoker,
        LoggerInterface $logger,
        ContextFactoryInterface $contextFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->invoker = $invoker;
        $this->logger = $logger;
        $this->contextFactory = $contextFactory;
    }

    public function __invoke(): void
    {
        $this->logger->debug('Invoke handler event loop');

        while (true) {
            try {
                $response = $this->getNextInvocation();
                $event = $this->getEvent($response);
                $this->propagateTraceId($response);
                $context = $this->createContext($response);
                $response = $this->invokeHandler($event, $context);
                $this->postResponse($response, $context);
            } catch (Exception $exception) {
                $this->logger->error(
                    'Error handling event',
                    [
                        'exception' => $exception,
                        'event' => $event ?? [],
                        'response' => $response ?? [],
                    ]
                );

                $this->postError($context ?? null, $exception);
            }
        }
    }

    private function getNextInvocation(): ResponseInterface
    {
        $this->logger->debug('Get next invocation');

        return $this->sendRequest('GET', 'runtime/invocation/next');
    }

    /**
     * @return array<string>
     */
    private function getEvent(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function propagateTraceId(ResponseInterface $response): void
    {
        $traceId = $response->getHeader('lambda-runtime-trace-id ')[0];
        putenv("_X_AMZN_TRACE_ID=$traceId");
    }

    private function createContext(ResponseInterface $response): ContextInterface
    {
        return $this->contextFactory->createContext($response, $this->logger);
    }

    /**
     * @param array<string> $event
     *
     * @return mixed
     */
    private function invokeHandler(
        array $event,
        ContextInterface $context
    ) {
        /** @var callable */
        $handler = $this->getEnv('_HANDLER');

        return $this->invoker->call(
            $handler,
            [
                'event' => $event,
                'context' => $context,
            ]
        );
    }

    private function postResponse(
        string $response,
        ContextInterface $context
    ): void {
        $this->logger->debug('Post response');

        $this->sendRequest(
            'POST',
            "runtime/invocation/{$context->getAwsRequestId()}/response",
            [],
            $response
        );
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
        $this->logger->debug('Post error');

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
            ], JSON_THROW_ON_ERROR
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
