<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use DI\ContainerBuilder;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RuntimeHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $requestId = '';

    public function __construct()
    {
        try {
            $this->buildContainer();
            $this->configureLogging();
        } catch (Exception $exception) {
            $this->error(
                'Error initializing handler',
                [
                    'exception' => $exception,
                ]
            );
            $this->postError($exception);
        }
    }

    public function __invoke(): void
    {
        $this->info('Invoke handler event loop');

        while (true) {
            try {
                $event = $this->getNextInvocation();
                $context = $this->createContext();
                $response = $this->container->call(
                    getenv('_HANDLER'),
                    [
                        'event' => $event,
                        'context' => $context,
                    ]
                );
                $this->postResponse($response);
            } catch (Exception $exception) {
                $this->error(
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

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    private function configureLogging(): void
    {
        $this->info('Configure logging');
        $this->logger = $this->container->get(LoggerInterface::class);
    }

    private function createContext(): Context
    {
        return new Context($this);
    }

    private function buildContainer(): void
    {
        $this->info('Build container');

        $containerBuilder = new ContainerBuilder();

        if ($configPath = $this->taskPath('config.php')) {
            $containerBuilder->addDefinitions($configPath);
        }

        $this->container = $containerBuilder->build();
    }

    private function sendRequest(
        string $method,
        string $path,
        array $headers = [],
        array $body = []
    ): ResponseInterface {
        $request = $this->container->get(RequestFactoryInterface::class)
            ->createRequest($method, $this->url($path));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body) {
            $stream = $this->container->make(StreamInterface::class);
            $stream->write(json_encode($body));
            $request = $request->withBody($stream);
        }

        return $this->container
            ->get(ClientInterface::class)
            ->sendRequest($request);
    }

    private function getNextInvocation(): array
    {
        $this->info('Get next invocation');
        $response = $this->sendRequest('GET', 'runtime/invocation/next');
        $this->requestId = $response->getHeader('lambda-runtime-aws-request-id')[0];
        $body = (string) $response->getBody();
        $this->info('Invocation body', ['body' => $body]);

        return json_decode($body, true);
    }

    private function postResponse(array $response): void
    {
        $this->info('Post response');

        $response = $this->sendRequest(
            'POST',
            "runtime/invocation/{$this->requestId}/response",
            [],
            $response
        );
    }

    private function postError(Exception $exception): void
    {
        if ($this->client) {
            $this->info('Post error');

            $path = $this->requestId
                ? "runtime/invocation/{$this->requestId}/error"
                : 'runtime/init/error';

            $error = [
                'errorMessage' => sprintf(
                    '%s %s:%d',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ),
                'errorType' => get_class($exception),
            ];

            $response = $this->sendRequest(
                'POST',
                $path,
                [
                    'Lambda-Runtime-Function-Error-Type' => 'Unhandled',
                ],
                $error
            );
        }

        if (!$this->requestId) {
            exit(1);
        }
    }

    private function url($path)
    {
        return sprintf('http://%s/2018-06-01/', getenv('AWS_LAMBDA_RUNTIME_API'));
    }

    private function taskPath(string $path = '')
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

    private function error(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }

    private function info(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }
}
