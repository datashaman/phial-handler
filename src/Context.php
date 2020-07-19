<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Context implements
    ContextInterface,
    JsonSerializable
{
    use EnvironmentTrait;

    /**
     * @var ResponseInterface $response
     */
    private $response;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ResponseInterface $response, LoggerInterface $logger)
    {
        $this->response = $response;
        $this->logger = $logger;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'remainingTimeInMillis' => $this->getRemainingTimeInMillis(),
            'functionName' => $this->getFunctionName(),
            'functionVersion' => $this->getFunctionVersion(),
            'invokedFunctionArn' => $this->getInvokedFunctionArn(),
            'memoryLimitInMB' => $this->getMemoryLimitInMB(),
            'awsRequestId' => $this->getAwsRequestId(),
            'logGroupName' => $this->getLogGroupName(),
            'logStreamName' => $this->getLogStreamName(),
            'identity' => $this->getIdentity(),
            'clientContext' => $this->getClientContext(),
        ];
    }

    public function getRemainingTimeInMillis(): int
    {
        $deadline = (int) $this->getHeader('lambda-runtime-deadline-ms');
        $time = $this->getTimeinMillis();

        return $deadline - $time;
    }

    public function getFunctionName(): string
    {
        return $this->getEnv('AWS_LAMBDA_FUNCTION_NAME');
    }

    public function getFunctionVersion(): string
    {
        return $this->getEnv('AWS_LAMBDA_FUNCTION_VERSION');
    }

    public function getInvokedFunctionArn(): string
    {
        return $this->getHeader('lambda-runtime-invoked-function-arn');
    }

    public function getMemoryLimitInMB(): int
    {
        return (int) $this->getEnv('AWS_LAMBDA_FUNCTION_MEMORY_SIZE');
    }

    public function getAwsRequestId(): string
    {
        return $this->getHeader('lambda-runtime-aws-request-id');
    }

    public function getLogGroupName(): string
    {
        return $this->getEnv('AWS_LAMBDA_LOG_GROUP_NAME');
    }

    public function getLogStreamName(): string
    {
        return $this->getEnv('AWS_LAMBDA_LOG_STREAM_NAME');
    }

    public function getIdentity(): array
    {
        $header = $this->getHeader('lambda-runtime-cognito-identity');

        return json_decode($header, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getClientContext(): array
    {
        $header = $this->getHeader('lambda-runtime-client-context');

        return json_decode($header, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    private function getHeader(string $name): string
    {
        $header = $this->response->getHeader($name);

        if ($header) {
            return $header[0];
        }

        return '';
    }

    private function getTimeinMillis(): int
    {
        $microtime = microtime();
        $parts = explode(' ', $microtime);

        return (int) sprintf('%d%03d', $parts[1], (int) $parts[0] * 1000);
    }
}
