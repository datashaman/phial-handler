<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @var RuntimeHandler
     */
    private $handler;

    public function __construct(RuntimeHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getRemainingTimeInMillis(): int
    {
    }

    public function getFunctionName(): string
    {
        return getenv('AWS_LAMBDA_FUNCTION_NAME');
    }

    public function getFunctionVersion(): string
    {
        return getenv('AWS_LAMBDA_FUNCTION_VERSION');
    }

    public function getInvokedFunctionArn(): string
    {
    }

    public function getMemoryLimitInMB(): int
    {
        return (int) getenv('AWS_LAMBDA_FUNCTION_MEMORY_SIZE');
    }

    public function getAwsRequestId(): string
    {
        return $this->handler->getRequestId();
    }

    public function getLogGroupName(): string
    {
        return getenv('AWS_LAMBDA_LOG_GROUP_NAME');
    }

    public function getLogStreamName(): string
    {
        return getenv('AWS_LAMBDA_LOG_STREAM_NAME');
    }

    public function getLogger(): LoggerInterface
    {
        return $this->handler->getLogger();
    }
}
