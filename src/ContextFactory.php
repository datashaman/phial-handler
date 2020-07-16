<?php

declare(strict_types=1);

namespace Datashaman\Phial;

class ContextFactory implements ContextFactoryInterface
{
    /**
     * @param RuntimeHandlerInterface
     *
     * @return ContextInterface
     */
    public function createContext(RuntimeHandlerInterface $handler): ContextInterface
    {
        return new Context($handler);
    }
}
