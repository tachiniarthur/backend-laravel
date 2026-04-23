<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\MetricsResultCollection;
use App\Testing\Metrics\TestMetric;
use Tests\TesteIA\TesteIATestCase;

class MetricsResultCollectionTest extends TesteIATestCase
{
    private function createMetric(
        float $wallTime = 0.1,
        int $peakMemory = 1048576,
        ?float $coverage = 80.0,
        string $class = 'Test',
        string $method = 'method',
    ): TestMetric {
        return new TestMetric(
            testClass: $class,
            testMethod: $method,
            testName: "{$class}::{$method}",
            status: 'passed',
            wallTime: $wallTime,
            userCpuTime: 0.05,
            systemCpuTime: 0.02,
            peakMemoryBytes: $peakMemory,
            coveragePercent: $coverage,
            linesExecuted: $coverage !== null ? (int) $coverage : null,
            linesTotal: $coverage !== null ? 100 : null,
            executedAt: new \DateTimeImmutable(),
        );
    }

    public function test_count_returnsNumberOfMetrics(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(),
            $this->createMetric(),
        ]);

        $this->assertEquals(2, $collection->count());
    }

    public function test_count_emptyCollection_returnsZero(): void
    {
        $collection = new MetricsResultCollection();

        $this->assertEquals(0, $collection->count());
    }

    public function test_totalWallTime_sumOfAllWallTimes(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(wallTime: 0.1),
            $this->createMetric(wallTime: 0.2),
            $this->createMetric(wallTime: 0.3),
        ]);

        $this->assertEqualsWithDelta(0.6, $collection->totalWallTime(), 0.001);
    }

    public function test_averageWallTime_calculatesCorrectly(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(wallTime: 0.1),
            $this->createMetric(wallTime: 0.3),
        ]);

        $this->assertEqualsWithDelta(0.2, $collection->averageWallTime(), 0.001);
    }

    public function test_averageWallTime_emptyCollection_returnsZero(): void
    {
        $collection = new MetricsResultCollection();

        $this->assertEquals(0.0, $collection->averageWallTime());
    }

    public function test_totalMemoryPeak_returnsMaxPeakMemory(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(peakMemory: 1000),
            $this->createMetric(peakMemory: 5000),
            $this->createMetric(peakMemory: 3000),
        ]);

        $this->assertEquals(5000, $collection->totalMemoryPeak());
    }

    public function test_totalMemoryPeak_emptyCollection_returnsZero(): void
    {
        $collection = new MetricsResultCollection();

        $this->assertEquals(0, $collection->totalMemoryPeak());
    }

    public function test_averageCoverage_calculatesCorrectly(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(coverage: 80.0),
            $this->createMetric(coverage: 90.0),
        ]);

        $this->assertEqualsWithDelta(85.0, $collection->averageCoverage(), 0.01);
    }

    public function test_averageCoverage_withNullCoverage_excludesNulls(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(coverage: 80.0),
            $this->createMetric(coverage: null),
            $this->createMetric(coverage: 100.0),
        ]);

        $this->assertEqualsWithDelta(90.0, $collection->averageCoverage(), 0.01);
    }

    public function test_averageCoverage_allNull_returnsNull(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(coverage: null),
            $this->createMetric(coverage: null),
        ]);

        $this->assertNull($collection->averageCoverage());
    }

    public function test_groupByClass_groupsCorrectly(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(class: 'ClassA', method: 'test1'),
            $this->createMetric(class: 'ClassA', method: 'test2'),
            $this->createMetric(class: 'ClassB', method: 'test1'),
        ]);

        $grouped = $collection->groupByClass();

        $this->assertCount(2, $grouped);
        $this->assertCount(2, $grouped['ClassA']);
        $this->assertCount(1, $grouped['ClassB']);
    }

    public function test_sortByWallTime_desc_sortsCorrectly(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(wallTime: 0.1, class: 'A', method: 'fast'),
            $this->createMetric(wallTime: 0.5, class: 'B', method: 'slow'),
            $this->createMetric(wallTime: 0.3, class: 'C', method: 'mid'),
        ]);

        $sorted = $collection->sortByWallTime('desc');
        $array = $sorted->toArray();

        $this->assertEquals('B', $array[0]['test_class']);
        $this->assertEquals('C', $array[1]['test_class']);
        $this->assertEquals('A', $array[2]['test_class']);
    }

    public function test_sortByWallTime_asc_sortsCorrectly(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(wallTime: 0.5, class: 'B', method: 'slow'),
            $this->createMetric(wallTime: 0.1, class: 'A', method: 'fast'),
        ]);

        $sorted = $collection->sortByWallTime('asc');
        $array = $sorted->toArray();

        $this->assertEquals('A', $array[0]['test_class']);
        $this->assertEquals('B', $array[1]['test_class']);
    }

    public function test_toArray_returnsArrayOfMetricArrays(): void
    {
        $collection = new MetricsResultCollection([
            $this->createMetric(),
        ]);

        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertArrayHasKey('test_class', $array[0]);
        $this->assertArrayHasKey('test_method', $array[0]);
        $this->assertArrayHasKey('status', $array[0]);
    }
}
