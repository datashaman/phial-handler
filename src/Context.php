<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Context implements ContextInterface
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

    public function __construct(string $awsRequestId, LoggerInterface $logger)
    {
        $this->awsRequestId = $awsRequestId;
        $this->logger = $logger;
    }

    private function getTimeinMillis(): double
    {
        $microtime = microtime();
        $parts = explode(' ', $microtime);

        return (double) sprintf('%d%03d', $parts[1], $parts[0] * 1000);
    }

    public function getRemainingTimeInMillis(): double
    {
        $deadline = (double) $this
            ->response
            ->getHeader('lambda-runtime-deadline-ms');
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
        return $this->response->getHeader('lambda-runtime-invoked-function-Arn');
    }

    public function getMemoryLimitInMB(): int
    {
        return (int) $this->getEnv('AWS_LAMBDA_FUNCTION_MEMORY_SIZE');
    }

    public function getAwsRequestId(): string
    {
        return $this->awsRequestId;
    }

    public function getLogGroupName(): string
    {
        return $this->getEnv('AWS_LAMBDA_LOG_GROUP_NAME');
    }

    public function getLogStreamName(): string
    {
        return $this->getEnv('AWS_LAMBDA_LOG_STREAM_NAME');
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
