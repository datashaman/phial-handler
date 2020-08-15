<?php

declare(strict_types=1);

namespace Datashaman\Phial;

interface FunctionHandlerInterface
{
    /**
     * @param array<string,mixed> $event
     *
     * @return ?mixed
     */
    public function handle(
        array $event,
        ContextInterface $context
    );
}
