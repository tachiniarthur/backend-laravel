<?php

namespace App\Testing\Metrics;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class MetricsExtension implements Extension
{
    private static ?MetricsCollector $collector = null;

    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $resourceMonitor = new ResourceMonitor();
        $coverageCollector = new CoverageCollector();

        $coverageEnv = getenv('METRICS_COVERAGE_ENABLED')
            ?: ($_SERVER['METRICS_COVERAGE_ENABLED'] ?? $_ENV['METRICS_COVERAGE_ENABLED'] ?? 'false');

        $coverageEnabled = ($parameters->has('coverage')
            && $parameters->get('coverage') === 'true')
            || $coverageEnv === 'true';

        $metricsCollector = new MetricsCollector(
            $resourceMonitor,
            $coverageCollector,
            $coverageEnabled,
        );

        self::$collector = $metricsCollector;

        $statusTracker = new TestStatusTracker();

        $facade->registerSubscribers(
            new TestPreparedSubscriber($metricsCollector),
            new TestPassedSubscriber($statusTracker),
            new TestFailedSubscriber($statusTracker),
            new TestErroredSubscriber($statusTracker),
            new TestSkippedSubscriber($statusTracker),
            new TestFinishedSubscriber($metricsCollector, $statusTracker),
            new ExecutionFinishedSubscriber($metricsCollector),
        );
    }

    public static function getCollector(): ?MetricsCollector
    {
        return self::$collector;
    }

    public static function resetCollector(): void
    {
        self::$collector = null;
    }
}
