<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;
use Psr\Log\LoggerInterface;

class Context implements ContextInterface
{
    use EnvironmentTrait;

    /**
     * @var string
     */
    private $awsRequestId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $awsRequestId, LoggerInterface $logger)
    {
        $this->awsRequestId = $awsRequestId;
        $this->logger = $logger;
    }

    public function getRemainingTimeInMillis(): int
    {
        throw new Exception('Not implemented');
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
        throw new Exception('Not implemented');
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
