<?php

declare(strict_types=1);

namespace Datashaman\Phial\Lambda;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

interface ContextFactoryInterface
{
    /**
     * @param ResponseInterface $response
     * @param LoggerInterface $logger
     *
     * @return ContextInterface
     */
    public function createContext(
        ResponseInterface $response,
        LoggerInterface $logger
    ): ContextInterface;
}
