<?php

declare(strict_types=1);

namespace Datashaman\Phial\Http\Events;

use Datashaman\Phial\Lambda\ContextInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEvent
{
    public ServerRequestInterface $request;
    public ResponseInterface $response;
    public ContextInterface $context;

    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContextInterface $context
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->context = $context;
    }
}
