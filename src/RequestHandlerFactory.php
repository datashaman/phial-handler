<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerFactory implements RequestHandlerFactoryInterface
{
    private array $middleware;
    private RequestHandlerInterface $fallback;

    public function __construct(
        array $middleware,
        RequestHandlerInterface $fallbackRequestHandler
    ) {
        $this->middleware = $middleware;
        $this->fallback = $fallback;
    }

    public function createRequestHandler(): RequestHandlerInterface
    {
        return new QueueRequestHandler(
            $this->middleware,
            $this->fallback
        );
    }
}
