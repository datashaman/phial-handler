<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerAdapter
{
    private RequestHandlerInterface $requestHandler;
    private EventDispatcherInterface $eventDispatcher;
    private ServerRequestFactoryInterface $serverRequestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestHandlerInterface $requestHandler,
        EventDispatcherInterface $eventDispatcher,
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestHandler = $requestHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param array<string,mixed> $event
     */
    public function __invoke(array $event, ContextInterface $context): string
    {
        $request = $this->createServerRequest($event, $context);
        $this->eventDispatcher->dispatch(new Events\RequestEvent($request, $context));
        $response = $this->requestHandler->handle($request);

        return $this->adaptResponse($response);
    }

    /**
     * @param array<string,mixed> $event
     */
    private function createServerRequest(
        array $event,
        ContextInterface $context
    ): ServerRequestInterface {
        $request = $this->serverRequestFactory
            ->createServerRequest(
                $event['httpMethod'],
                $event['path'],
                $this->generateServerParams($event, $context)
            );

        foreach ($event['headers'] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        foreach ($event['multiValueHeaders'] as $name => $values) {
            foreach ($values as $index => $value) {
                $request = $index
                    ? $request->withAddedHeader($name, $value)
                    : $request->withHeader($name, $value);
            }
        }

        $queryParams = [];

        if (isset($event['queryStringParameters'])) {
            foreach ($event['queryStringParameters'] as $name => $value) {
                if (!$this->endsWith($name, '[]')) {
                    $queryParams[$name] = $value;
                }
            }
        }

        if (isset($event['multiValueQueryStringParameters'])) {
            foreach ($event['multiValueQueryStringParameters'] as $name => $value) {
                if ($this->endsWith($name, '[]')) {
                    $name = substr($name, 0, strlen($name) - 2);
                    $queryParams[$name] = $value;
                }
            }
        }

        if ($queryParams) {
            $request = $request
                ->withQueryParams($queryParams);
        }

        if (!is_null($event['body'])) {
            $body = $event['isBase64Encoded']
                ? base64_decode($event['body'])
                : $event['body'];
            $stream = $this->streamFactory->createStream();
            $stream->write($body);
            $request = $request->withBody($stream);
        }

        return $request;
    }

    /**
     * @param array<string,mixed> $event
     *
     * @return array<string,mixed>
     */
    private function generateServerParams(
        array $event,
        ContextInterface $context
    ): array {
        return [];
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);

        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

    private function adaptResponse(ResponseInterface $response): string
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

        $payload = [
            'statusCode' => $response->getStatusCode(),
            'body' => (string) $response->getBody(),
            'headers' => $headers,
        ];

        return json_encode(
            $payload,
            JSON_PRETTY_PRINT
            | JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }
}
