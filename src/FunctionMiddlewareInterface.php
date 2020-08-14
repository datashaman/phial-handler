<?php

declare(strict_types=1);

namespace Datashaman\Phial;

interface FunctionMiddlewareInterface
{
    /**
     * @param array<string,mixed> $event
     *
     * @return mixed
     */
    public function process(
        array $event,
        ContextInterface $context,
        FunctionHandlerInterface $handler
    );
}
