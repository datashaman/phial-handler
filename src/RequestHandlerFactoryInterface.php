<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Psr\Http\Server\RequestHandlerInterface;

interface RequestHandlerFactoryInterface
{
    public function createRequestHandler(): RequestHandlerInterface;
}
