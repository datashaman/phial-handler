<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use OutOfBoundsException;

class EventPipeline implements
    EventHandlerInterface,
    EventMiddlewareInterface
{
    /**
     * @var array<int,EventMiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @param array<int,EventMiddlewareInterface> $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function append(EventMiddlewareInterface ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);

        return $this;
    }

    public function prepend(EventMiddlewareInterface ...$middleware): self
    {
        array_unshift($this->middleware, ...$middleware);

        return $this;
    }

    /**
     * @param array<string,mixed> $event
     *
     * @return mixed
     */
    public function handle(array $event, ContextInterface $context)
    {
        $pipeline = clone $this;

        return $pipeline->nextMiddleware()->process($event, $context, $pipeline);
    }

    /**
     * @param array<string,mixed> $event
     *
     * @return mixed
     */
    public function process(array $event, ContextInterface $context, EventHandlerInterface $handler)
    {
        try {
            return $this->handle($event, $context);
        } catch (OutOfBoundsException $exception) {
            return $handler->handle($event, $context);
        }
    }

    /**
     * @throws OutOfBoundsException If no middleware is available
     */
    private function nextMiddleware(): EventMiddlewareInterface
    {
        $middleware = array_shift($this->middleware);

        if ($middleware === null) {
            throw new OutOfBoundsException("End of middleware stack");
        }

        return $middleware;
    }
}
