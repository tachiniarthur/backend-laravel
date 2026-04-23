<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\CoverageCollector;
use App\Testing\Metrics\CoverageResult;
use App\Testing\Metrics\MetricsCollector;
use App\Testing\Metrics\ResourceMonitor;
use App\Testing\Metrics\TestMetric;
use Tests\TesteIA\TesteIATestCase;

class MetricsCollectorTest extends TesteIATestCase
{
    public function test_recordTestStart_takesSnapshotAndStoresIt(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('TestClass::testMethod');

        // No assertion on internals, but recordTestEnd should work without error
        $collector->recordTestEnd('TestClass::testMethod', 'passed');

        $store = $collector->getStore();
        $all = $store->all();
        $this->assertCount(1, $all);
        $this->assertArrayHasKey('TestClass::testMethod', $all);
    }

    public function test_recordTestEnd_createsMetricWithCorrectData(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('MyTest::testSomething');
        $collector->recordTestEnd('MyTest::testSomething', 'passed');

        $store = $collector->getStore();
        $metric = $store->all()['MyTest::testSomething'];

        $this->assertInstanceOf(TestMetric::class, $metric);
        $this->assertEquals('MyTest', $metric->testClass);
        $this->assertEquals('testSomething', $metric->testMethod);
        $this->assertEquals('passed', $metric->status);
        $this->assertNull($metric->coveragePercent);
    }

    public function test_recordTestEnd_withoutStart_usesEndSnapshotAsFallback(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        // Don't call recordTestStart — should still work
        $collector->recordTestEnd('Orphan::test', 'failed');

        $store = $collector->getStore();
        $this->assertCount(1, $store->all());
        $this->assertEquals('failed', $store->all()['Orphan::test']->status);
    }

    public function test_recordTestEnd_parsesTestNameWithoutSeparator(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('simpleTestName');
        $collector->recordTestEnd('simpleTestName', 'passed');

        $metric = $collector->getStore()->all()['simpleTestName'];
        $this->assertEquals('simpleTestName', $metric->testClass);
        $this->assertEquals('', $metric->testMethod);
    }

    public function test_recordTestEnd_withCoverageDisabled_noCoverageData(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('Test::method');
        $collector->recordTestEnd('Test::method', 'passed');

        $metric = $collector->getStore()->all()['Test::method'];
        $this->assertNull($metric->coveragePercent);
        $this->assertNull($metric->linesExecuted);
        $this->assertNull($metric->linesTotal);
    }

    public function test_getResults_returnsMetricsResultCollection(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('A::b');
        $collector->recordTestEnd('A::b', 'passed');
        $collector->recordTestStart('C::d');
        $collector->recordTestEnd('C::d', 'failed');

        $results = $collector->getResults();
        $this->assertEquals(2, $results->count());
    }

    public function test_getStore_returnsMetricsStore(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $store = $collector->getStore();
        $this->assertInstanceOf(\App\Testing\Metrics\MetricsStore::class, $store);
    }

    public function test_multipleTests_recordedCorrectly(): void
    {
        $monitor = new ResourceMonitor();
        $coverage = new CoverageCollector('none');
        $collector = new MetricsCollector($monitor, $coverage, false);

        $collector->recordTestStart('Test1::a');
        $collector->recordTestEnd('Test1::a', 'passed');
        $collector->recordTestStart('Test2::b');
        $collector->recordTestEnd('Test2::b', 'skipped');
        $collector->recordTestStart('Test3::c');
        $collector->recordTestEnd('Test3::c', 'error');

        $all = $collector->getStore()->all();
        $this->assertCount(3, $all);
        $this->assertEquals('passed', $all['Test1::a']->status);
        $this->assertEquals('skipped', $all['Test2::b']->status);
        $this->assertEquals('error', $all['Test3::c']->status);
    }
}
