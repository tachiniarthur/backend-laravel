<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

class TestPreparedSubscriber implements PreparedSubscriber
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector,
    ) {
    }

    public function notify(Prepared $event): void
    {
        $this->metricsCollector->recordTestStart($event->test()->id());
    }
}
