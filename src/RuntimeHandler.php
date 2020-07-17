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
     * @var ContextFactoryInterface
     */
    private $contextFactory;

    /**
     * @var string
     */
    private $awsRequestId = '';

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?ContextFactoryInterface $contextFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->contextFactory = $contextFactory ?: new ContextFactory();
        $this->logger = $logger ?: new NullLogger();
    }

    public function __invoke(InvokerInterface $invoker): void
    {
        $this->logger->info('Invoke handler event loop');

        while (true) {
            try {
                $event = $this->getNextInvocation();
                $context = $this->createContext();
                $response = $invoker->call(
                    getenv('_HANDLER'),
                    [
                        'event' => $event,
                        'context' => $context,
                    ]
                );
                $this->postResponse($response);
            } catch (Exception $exception) {
                $this->logger->error(
                    'Error handling event',
                    [
                        'exception' => $exception,
                        'event' => $event ?? [],
                        'response' => $response ?? [],
                    ]
                );
                $this->postError($exception);
            }
        }
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getAwsRequestId(): string
    {
        return $this->awsRequestId;
    }

    private function createContext(): Context
    {
        return $this->contextFactory->createContext($this);
    }

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

    private function getNextInvocation(): array
    {
        $this->logger->info('Get next invocation');
        $response = $this->sendRequest('GET', 'runtime/invocation/next');
        $this->awsRequestId = $response->getHeader('lambda-runtime-aws-request-id')[0];
        $body = (string) $response->getBody();

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function postResponse(array $response): void
    {
        $this->logger->info('Post response');

        $this->sendRequest(
            'POST',
            "runtime/invocation/{$this->awsRequestId}/response",
            [],
            $response
        );
    }

    private function postError(Exception $exception): void
    {
        $this->logger->info('Post error');

        $path = $this->awsRequestId
            ? "runtime/invocation/{$this->awsRequestId}/error"
            : 'runtime/init/error';

        $error = [
            'errorMessage' => sprintf(
                '%s %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ),
            'errorType' => get_class($exception),
            'trace' => $exception->getTrace(),
        ];

        $this->sendRequest(
            'POST',
            $path,
            [
                'Lambda-Runtime-Function-Error-Type' => 'Unhandled',
            ],
            $error
        );

        if (!$this->awsRequestId) {
            exit(1);
        }
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
        return realpath(
            sprintf(
                '%s%s%s',
                getenv('LAMBDA_TASK_ROOT'),
                DIRECTORY_SEPARATOR,
                $path
            )
        );
    }
}
