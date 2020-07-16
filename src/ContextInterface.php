<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Log\LoggerInterface;

interface ContextInterface
{
    public function getRemainingTimeInMillis(): int;
    public function getFunctionName(): string;
    public function getFunctionVersion(): string;
    public function getInvokedFunctionArn(): string;
    public function getMemoryLimitInMB(): int;
    public function getAwsRequestId(): string;
    public function getLogGroupName(): string;
    public function getLogStreamName(): string;
    public function getLogger(): LoggerInterface;
}
