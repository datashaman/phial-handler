<?php

declare(strict_types=1);

namespace Datashaman\Phial\Listeners;

use Datashaman\Phial\Events\ResponseEvent;
use Pkerrigan\Xray\Submission\DaemonSegmentSubmitter;
use Pkerrigan\Xray\Trace;

class TraceEnd
{
    public function __invoke(ResponseEvent $event): void
    {
        Trace::getInstance()
            ->end()
            ->submit(new DaemonSegmentSubmitter());
    }
}
