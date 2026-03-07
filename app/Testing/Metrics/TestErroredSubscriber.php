<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

class TestErroredSubscriber implements ErroredSubscriber
{
    public function __construct(
        private readonly TestStatusTracker $statusTracker,
    ) {
    }

    public function notify(Errored $event): void
    {
        $this->statusTracker->setStatus($event->test()->id(), 'error');
    }
}
