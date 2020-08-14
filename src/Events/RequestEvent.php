<?php

declare(strict_types=1);

namespace Datashaman\Phial\Events;

use Datashaman\Phial\Lambda\ContextInterface;

class RequestEvent
{
    public array $event;
    public ContextInterface $context;

    public function __construct(
        array $event,
        ContextInterface $context
    ) {
        $this->event = $event;
        $this->context = $context;
    }
}
