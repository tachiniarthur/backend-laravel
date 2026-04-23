<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\Exceptions\InvalidMetricsDataException;
use App\Testing\Metrics\MetricsStore;
use App\Testing\Metrics\TestMetric;
use Tests\TesteIA\TesteIATestCase;

class MetricsStoreTest extends TesteIATestCase
{
    private function createMetric(string $name = 'Test::method', string $status = 'passed'): TestMetric
    {
        return new TestMetric(
            testClass: 'Test',
            testMethod: 'method',
            testName: $name,
            status: $status,
            wallTime: 0.1,
            userCpuTime: 0.05,
            systemCpuTime: 0.02,
            peakMemoryBytes: 1024 * 1024,
            coveragePercent: 85.0,
            linesExecuted: 85,
            linesTotal: 100,
            executedAt: new \DateTimeImmutable(),
        );
    }

    public function test_add_andAll_storesAndRetrievesMetrics(): void
    {
        $store = new MetricsStore();
        $metric = $this->createMetric();

        $store->add($metric);

        $all = $store->all();
        $this->assertCount(1, $all);
        $this->assertSame($metric, $all['Test::method']);
    }

    public function test_toArray_returnsCorrectStructure(): void
    {
        $store = new MetricsStore();
        $store->add($this->createMetric());

        $array = $store->toArray();

        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('metrics', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertEquals(1, $array['metadata']['total_tests']);
        $this->assertCount(1, $array['metrics']);
    }

    public function test_toJson_returnsValidJson(): void
    {
        $store = new MetricsStore();
        $store->add($this->createMetric());

        $json = $store->toJson();

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('metrics', $decoded);
    }

    public function test_fromJson_reconstructsStore(): void
    {
        $store = new MetricsStore();
        $store->add($this->createMetric());
        $json = $store->toJson();

        $restored = MetricsStore::fromJson($json);

        $this->assertCount(1, $restored->all());
    }

    public function test_fromJson_withInvalidJson_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson('not valid json{{{');
    }

    public function test_fromJson_withMissingMetadata_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson(json_encode(['metrics' => [], 'summary' => []]));
    }

    public function test_fromJson_withMissingMetrics_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson(json_encode(['metadata' => [], 'summary' => []]));
    }

    public function test_fromJson_withMissingSummary_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson(json_encode(['metadata' => [], 'metrics' => []]));
    }

    public function test_fromJson_withNonArrayMetrics_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson(json_encode([
            'metadata' => [],
            'metrics' => 'not an array',
            'summary' => [],
        ]));
    }

    public function test_fromJson_withMissingMetricField_throwsException(): void
    {
        $this->expectException(InvalidMetricsDataException::class);

        MetricsStore::fromJson(json_encode([
            'metadata' => [],
            'metrics' => [['test_class' => 'A']], // missing required fields
            'summary' => [],
        ]));
    }

    public function test_toArray_summaryContainsExpectedKeys(): void
    {
        $store = new MetricsStore();
        $store->add($this->createMetric());

        $array = $store->toArray();

        $this->assertArrayHasKey('total_wall_time_ms', $array['summary']);
        $this->assertArrayHasKey('average_wall_time_ms', $array['summary']);
        $this->assertArrayHasKey('total_peak_memory_kb', $array['summary']);
        $this->assertArrayHasKey('average_coverage_percent', $array['summary']);
    }

    public function test_toArray_metadataContainsPhpVersion(): void
    {
        $store = new MetricsStore();

        $array = $store->toArray();

        $this->assertEquals(PHP_VERSION, $array['metadata']['php_version']);
    }

    public function test_emptyStore_toArray_hasZeroTests(): void
    {
        $store = new MetricsStore();

        $array = $store->toArray();

        $this->assertEquals(0, $array['metadata']['total_tests']);
        $this->assertEmpty($array['metrics']);
    }
}
