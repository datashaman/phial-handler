<?php

declare(strict_types=1);

namespace Datashaman\Phial\Listeners;

use Datashaman\Phial\Http\Events\RequestEvent;
use Pkerrigan\Xray\Trace;

class TraceBegin
{
    public function __construct()
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->request;

        Trace::getInstance()
            ->setTraceHeader(getenv('_X_AMZN_TRACE_ID') ?: null)
            ->setName('phial-handler')
            ->setUrl((string) $request->getUri())
            ->setMethod($request->getMethod())
            ->begin();
    }
}
