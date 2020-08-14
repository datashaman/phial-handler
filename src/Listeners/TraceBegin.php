<?php

declare(strict_types=1);

namespace Datashaman\Phial\Listeners;

use Datashaman\Phial\Events\RequestEvent;
use Pkerrigan\Xray\Trace;

class TraceBegin
{
    public function __invoke(RequestEvent $event): void
    {
        Trace::getInstance()
            ->setTraceHeader(getenv('_X_AMZN_TRACE_ID') ?: null)
            ->setName('phial-handler')
            ->begin();
    }
}
