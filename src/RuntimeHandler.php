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
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RuntimeHandler implements RuntimeHandlerInterface
{
    use EnvironmentTrait;
    use LoggerAwareTrait;

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
                $context = $this->createContext($response);
                $response = $this->invokeHandler($context, $event);
                $this->postResponse($context, $response);
            } catch (Exception $exception) {
                $context = $context ?? null;

                $this->logger->error(
                    'Error handling event',
                    [
                        'exception' => $exception,
                        'event' => $event ?? [],
                        'response' => $response ?? [],
                    ]
                );
                $this->postError($context, $exception);
            }
        }
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    private function createContext(ResponseInterface $response): ContextInterface
    {
        $awsRequestId = $response->getHeader('lambda-runtime-aws-request-id')[0];

        return $this->contextFactory->createContext($awsRequestId, $this->logger);
    }

    /**
     * @param array<string> $event
     *
     * @return array<string>
     */
    private function invokeHandler(
        ContextInterface $context,
        array $event
    ): array {
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

    /**
     * @param array<string> $headers
     * @param array<array|string> $body
     */
    private function sendRequest(
        string $method,
        string $path,
        array $headers = [],
        array $body = []
    ): ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $this->url($path));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body) {
            $content = json_encode($body, JSON_THROW_ON_ERROR);
            $stream = $this->streamFactory->createStream($content);
            $request = $request->withBody($stream);
        }

        return $this->client->sendRequest($request);
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

    /**
     * @param array<string> $response
     */
    private function postResponse(
        ContextInterface $context,
        array $response
    ): void {
        $this->logger->debug('Post response');

        $this->sendRequest(
            'POST',
            "runtime/invocation/{$context->getAwsRequestId()}/response",
            [],
            $response
        );
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
     * @return array<string, array|string>
     */
    private function transformException(Exception $exception): array
    {
        return [
            'errorMessage' => sprintf(
                '%s %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ),
            'errorType' => get_class($exception),
            'trace' => $exception->getTrace(),
        ];
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
