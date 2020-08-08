<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class QueueRequestHandler implements RequestHandlerInterface
{
    private array $middleware = [];
    private RequestHandlerInterface $fallback;

    public function __construct(
        array $middleware,
        RequestHandlerInterface $fallback
    ) {
        $this->middleware = $middleware;
        $this->fallback = $fallback;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->middleware) {
            return $this->fallback->handle($request);
        }

        $middleware = array_shift($this->middleware);

        return $middleware->process($request, $this);
    }
}
