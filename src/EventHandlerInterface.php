<?php

declare(strict_types=1);

namespace Datashaman\Phial;

interface EventHandlerInterface
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
