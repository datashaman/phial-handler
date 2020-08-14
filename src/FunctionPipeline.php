<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use OutOfBoundsException;

class FunctionPipeline implements
    FunctionHandlerInterface,
    FunctionMiddlewareInterface
{
    /**
     * @var array<int,FunctionMiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @param array<int,FunctionMiddlewareInterface> $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function append(FunctionMiddlewareInterface ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);

        return $this;
    }

    public function prepend(FunctionMiddlewareInterface ...$middleware): self
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
    public function process(array $event, ContextInterface $context, FunctionHandlerInterface $handler)
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
    private function nextMiddleware(): FunctionMiddlewareInterface
    {
        $middleware = array_shift($this->middleware);

        if ($middleware === null) {
            throw new OutOfBoundsException("End of middleware stack");
        }

        return $middleware;
    }
}
