<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Log\LoggerInterface;

class ContextFactory implements ContextFactoryInterface
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
    ): ContextInterface {
        return new Context($awsRequestId, $logger);
    }
}
