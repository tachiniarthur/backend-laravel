<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;

class TestPassedSubscriber implements PassedSubscriber
{
    public function __construct(
        private readonly TestStatusTracker $statusTracker,
    ) {
    }

    public function notify(Passed $event): void
    {
        $this->statusTracker->setStatus($event->test()->id(), 'passed');
    }
}
