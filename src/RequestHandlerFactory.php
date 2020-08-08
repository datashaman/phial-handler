<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerFactory implements RequestHandlerFactoryInterface
{
    /**
     * @var array<MiddlewareInterface>
     */
    private array $middleware;

    private RequestHandlerInterface $fallback;

    /**
     * @param array<MiddlewareInterface> $middleware
     */
    public function __construct(
        array $middleware,
        RequestHandlerInterface $fallback
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
