<?php

namespace App\Testing\Metrics;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;

class ExecutionFinishedSubscriber implements ExecutionFinishedSubscriberInterface
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector,
    ) {
    }

    public function notify(ExecutionFinished $event): void
    {
        $outputPath = getenv('METRICS_OUTPUT_PATH')
            ?: ($_SERVER['METRICS_OUTPUT_PATH'] ?? $_ENV['METRICS_OUTPUT_PATH'] ?? '');

        if ($outputPath === false || $outputPath === '') {
            return;
        }

        $store = $this->metricsCollector->getStore();
        $json = $store->toJson();

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $json);
    }
}
