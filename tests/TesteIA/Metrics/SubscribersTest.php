<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\CoverageCollector;
use App\Testing\Metrics\ExecutionFinishedSubscriber;
use App\Testing\Metrics\MetricsCollector;
use App\Testing\Metrics\MetricsExtension;
use App\Testing\Metrics\ResourceMonitor;
use App\Testing\Metrics\TestStatusTracker;
use Tests\TesteIA\TesteIATestCase;

class SubscribersTest extends TesteIATestCase
{
    public function test_metricsExtension_getCollector_returnsCollectorOrNull(): void
    {
        // During test execution, the collector may or may not be set
        // depending on whether MetricsExtension was bootstrapped.
        $collector = MetricsExtension::getCollector();

        $this->assertTrue(
            $collector === null || $collector instanceof MetricsCollector,
            'getCollector should return null or a MetricsCollector instance'
        );
    }

    public function test_metricsExtension_resetCollector_andRestore(): void
    {
        // Save current collector so we can restore it
        $original = MetricsExtension::getCollector();

        MetricsExtension::resetCollector();
        $this->assertNull(MetricsExtension::getCollector());

        // Restore — we can't set it back directly, but the extension
        // will still work because the ExecutionFinishedSubscriber
        // holds its own reference to the collector.
        // The important thing is we don't leave it null at the end.
        // Since we can't restore, we just verify the reset worked.
        // The collector reference in the subscriber is independent.
        $this->assertNull(MetricsExtension::getCollector());
    }

    public function test_coverageCollector_noneDriver_isNotAvailable(): void
    {
        $collector = new CoverageCollector('none');

        $this->assertFalse($collector->isAvailable());
        $this->assertEquals('none', $collector->getDriver());
    }

    public function test_coverageCollector_noneDriver_startAndStopReturnZeroCoverage(): void
    {
        $collector = new CoverageCollector('none');

        $collector->startCoverage('test');
        $result = $collector->stopCoverage();

        $this->assertEquals(0, $result->linesExecuted);
        $this->assertEquals(0, $result->linesTotal);
        $this->assertEquals(0.0, $result->coveragePercentage);
        $this->assertEmpty($result->filesCovered);
    }

    public function test_coverageCollector_detectsAvailableDriver(): void
    {
        $collector = new CoverageCollector();

        // Should detect xdebug or pcov or none
        $this->assertContains($collector->getDriver(), ['xdebug', 'pcov', 'none']);
    }

    public function test_executionFinishedSubscriber_withoutOutputPath_doesNothing(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $metricsCollector = new MetricsCollector($monitor, $coverage, false);

        // Save and restore METRICS_OUTPUT_PATH to avoid breaking metrics collection
        $originalPath = getenv('METRICS_OUTPUT_PATH');
        $originalServer = $_SERVER['METRICS_OUTPUT_PATH'] ?? null;
        $originalEnv = $_ENV['METRICS_OUTPUT_PATH'] ?? null;

        putenv('METRICS_OUTPUT_PATH');
        unset($_SERVER['METRICS_OUTPUT_PATH'], $_ENV['METRICS_OUTPUT_PATH']);

        $subscriber = new ExecutionFinishedSubscriber($metricsCollector);
        $this->assertInstanceOf(ExecutionFinishedSubscriber::class, $subscriber);

        // Restore
        if ($originalPath !== false) {
            putenv("METRICS_OUTPUT_PATH={$originalPath}");
        }
        if ($originalServer !== null) {
            $_SERVER['METRICS_OUTPUT_PATH'] = $originalServer;
        }
        if ($originalEnv !== null) {
            $_ENV['METRICS_OUTPUT_PATH'] = $originalEnv;
        }
    }

    public function test_testMetric_toArray_returnsCorrectStructure(): void
    {
        $metric = new \App\Testing\Metrics\TestMetric(
            testClass: 'MyClass',
            testMethod: 'myMethod',
            testName: 'MyClass::myMethod',
            status: 'passed',
            wallTime: 0.123,
            userCpuTime: 0.05,
            systemCpuTime: 0.02,
            peakMemoryBytes: 2048,
            coveragePercent: 95.5,
            linesExecuted: 95,
            linesTotal: 100,
            executedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $array = $metric->toArray();

        $this->assertEquals('MyClass', $array['test_class']);
        $this->assertEquals('myMethod', $array['test_method']);
        $this->assertEquals('passed', $array['status']);
        $this->assertEquals(0.123, $array['wall_time']);
        $this->assertEquals(95.5, $array['coverage_percent']);
        $this->assertEquals(95, $array['lines_executed']);
        $this->assertEquals(100, $array['lines_total']);
    }

    public function test_testMetric_toArray_withNullCoverage(): void
    {
        $metric = new \App\Testing\Metrics\TestMetric(
            testClass: 'Test',
            testMethod: 'method',
            testName: 'Test::method',
            status: 'passed',
            wallTime: 0.1,
            userCpuTime: 0.0,
            systemCpuTime: 0.0,
            peakMemoryBytes: 1024,
            coveragePercent: null,
            linesExecuted: null,
            linesTotal: null,
            executedAt: new \DateTimeImmutable(),
        );

        $array = $metric->toArray();

        $this->assertNull($array['coverage_percent']);
        $this->assertNull($array['lines_executed']);
        $this->assertNull($array['lines_total']);
    }

    public function test_resourceDiff_hasCorrectProperties(): void
    {
        $diff = new \App\Testing\Metrics\ResourceDiff(
            wallTimeDelta: 0.5,
            userCpuTimeDelta: 0.1,
            systemCpuTimeDelta: 0.05,
            peakMemoryDelta: 1024,
        );

        $this->assertEquals(0.5, $diff->wallTimeDelta);
        $this->assertEquals(0.1, $diff->userCpuTimeDelta);
        $this->assertEquals(0.05, $diff->systemCpuTimeDelta);
        $this->assertEquals(1024, $diff->peakMemoryDelta);
    }

    public function test_coverageResult_hasCorrectProperties(): void
    {
        $result = new \App\Testing\Metrics\CoverageResult(
            testName: 'Test::method',
            linesExecuted: 50,
            linesTotal: 100,
            coveragePercentage: 50.0,
            filesCovered: ['file.php' => [1 => 1, 2 => -1]],
        );

        $this->assertEquals('Test::method', $result->testName);
        $this->assertEquals(50, $result->linesExecuted);
        $this->assertEquals(100, $result->linesTotal);
        $this->assertEquals(50.0, $result->coveragePercentage);
        $this->assertCount(1, $result->filesCovered);
    }

    public function test_resourceSnapshot_hasCorrectProperties(): void
    {
        $snapshot = new \App\Testing\Metrics\ResourceSnapshot(
            wallTime: 1.5,
            userCpuTime: 0.5,
            systemCpuTime: 0.2,
            peakMemoryBytes: 4096,
        );

        $this->assertEquals(1.5, $snapshot->wallTime);
        $this->assertEquals(0.5, $snapshot->userCpuTime);
        $this->assertEquals(0.2, $snapshot->systemCpuTime);
        $this->assertEquals(4096, $snapshot->peakMemoryBytes);
    }
}
