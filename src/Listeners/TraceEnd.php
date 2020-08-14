<?php

declare(strict_types=1);

namespace Datashaman\Phial\Http\Listeners;

use Datashaman\Phial\Http\Events\ResponseEvent;
use Pkerrigan\Xray\Submission\DaemonSegmentSubmitter;
use Pkerrigan\Xray\Trace;

class TraceEnd
{
    public function __invoke(ResponseEvent $event): void
    {
        Trace::getInstance()
            ->end()
            ->setResponseCode($event->response->getStatusCode())
            ->submit(new DaemonSegmentSubmitter());
    }
}
