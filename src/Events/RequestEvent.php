<?php

declare(strict_types=1);

namespace Datashaman\Phial\Events;

use Datashaman\Phial\ContextInterface;

class RequestEvent
{
    /**
     * @var array<string,mixed>
     */
    public array $event;

    public ContextInterface $context;

    /**
     * @param array<string,mixed> $event
     */
    public function __construct(
        array $event,
        ContextInterface $context
    ) {
        $this->event = $event;
        $this->context = $context;
    }
}
