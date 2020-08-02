<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Datashaman\Phial\ContextInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestEvent
{
    public ServerRequestInterface $request;
    public ContextInterface $context;

    public function __construct(
        ServerRequestInterface $request,
        ContextInterface $context
    ) {
        $this->request = $request;
        $this->context = $context;
    }
}
