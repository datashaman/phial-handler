<?php

declare(strict_types=1);

namespace Datashaman\Phial;

interface ContextFactoryInterface
{
    /**
     * @param RuntimeHandlerInterface
     *
     * @return ContextInterface
     */
    public function createContext(RuntimeHandlerInterface $handler): ContextInterface;
}
