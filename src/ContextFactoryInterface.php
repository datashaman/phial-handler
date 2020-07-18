<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Log\LoggerInterface;

interface ContextFactoryInterface
{
    /**
     * @param string $awsRequestId
     * @param LoggerInterface $logger
     *
     * @return ContextInterface
     */
    public function createContext(
        string $awsRequestId,
        LoggerInterface $logger
    ): ContextInterface;
}
