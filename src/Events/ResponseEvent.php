<?php

declare(strict_types=1);

namespace Datashaman\Phial\Events;

use Datashaman\Phial\Lambda\ContextInterface;

class ResponseEvent
{
    public array $event;
    public $response;
    public ContextInterface $context;

    public function __construct(
        array $event,
        $response,
        ContextInterface $context
    ) {
        $this->event = $event;
        $this->response = $response;
        $this->context = $context;
    }
}
