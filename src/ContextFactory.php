<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ContextFactory implements ContextFactoryInterface
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
    ): ContextInterface {
        return new Context($response, $logger);
    }
}
