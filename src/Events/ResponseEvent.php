<?php

declare(strict_types=1);

namespace Datashaman\Phial\Events;

use Datashaman\Phial\ContextInterface;

class ResponseEvent
{
    /**
     * @var array<string,mixed>
     */
    public array $event;

    /**
     * @var mixed
     */
    public $response;

    public ContextInterface $context;

    /**
     * @param array<string,mixed> $event
     * @param mixed $response
     */
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
