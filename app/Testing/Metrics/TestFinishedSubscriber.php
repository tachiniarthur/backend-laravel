<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

class TestFinishedSubscriber implements FinishedSubscriber
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector,
        private readonly TestStatusTracker $statusTracker,
    ) {
    }

    public function notify(Finished $event): void
    {
        $testId = $event->test()->id();
        $status = $this->statusTracker->getStatus($testId);

        $this->metricsCollector->recordTestEnd($testId, $status);
        $this->statusTracker->clear($testId);
    }
}
