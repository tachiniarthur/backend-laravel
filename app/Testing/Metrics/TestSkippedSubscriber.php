<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;

class TestSkippedSubscriber implements SkippedSubscriber
{
    public function __construct(
        private readonly TestStatusTracker $statusTracker,
    ) {
    }

    public function notify(Skipped $event): void
    {
        $this->statusTracker->setStatus($event->test()->id(), 'skipped');
    }
}
