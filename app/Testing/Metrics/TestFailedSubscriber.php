<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

class TestFailedSubscriber implements FailedSubscriber
{
    public function __construct(
        private readonly TestStatusTracker $statusTracker,
    ) {
    }

    public function notify(Failed $event): void
    {
        $this->statusTracker->setStatus($event->test()->id(), 'failed');
    }
}
