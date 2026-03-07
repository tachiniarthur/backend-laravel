<?php

namespace App\Testing\Metrics;

class MetricsStore
{
    /** @var array<string, TestMetric> */
    private array $metrics = [];

    public function __construct(
        private readonly string $projectName = 'laravel',
    ) {
    }

    public function add(TestMetric $metric): void
    {
        $this->metrics[$metric->testName] = $metric;
    }

    /**
     * @return array<string, TestMetric>
     */
    public function all(): array
    {
        return $this->metrics;
    }

    /**
     * @return array{metadata: array, metrics: array, summary: array}
     */
    public function toArray(): array
    {
        $collection = new MetricsResultCollection(array_values($this->metrics));

        return [
            'metadata' => [
                'project' => $this->projectName,
                'executed_at' => (new \DateTimeImmutable())->format('c'),
                'php_version' => PHP_VERSION,
                'coverage_driver' => $this->detectCoverageDriver(),
                'total_tests' => $collection->count(),
            ],
            'metrics' => array_map(fn(TestMetric $m) => [
                'test_class' => $m->testClass,
                'test_method' => $m->testMethod,
                'test_name' => $m->testName,
                'status' => $m->status,
                'wall_time_ms' => round($m->wallTime * 1000, 2),
                'user_cpu_time_ms' => round($m->userCpuTime * 1000, 2),
                'system_cpu_time_ms' => round($m->systemCpuTime * 1000, 2),
                'peak_memory_kb' => round($m->peakMemoryBytes / 1024, 2),
                'coverage_percent' => $m->coveragePercent,
                'lines_executed' => $m->linesExecuted,
                'lines_total' => $m->linesTotal,
                'executed_at' => $m->executedAt->format('c'),
            ], array_values($this->metrics)),
            'summary' => [
                'total_wall_time_ms' => round($collection->totalWallTime() * 1000, 2),
                'average_wall_time_ms' => round($collection->averageWallTime() * 1000, 2),
                'total_peak_memory_kb' => round($collection->totalMemoryPeak() / 1024, 2),
                'average_coverage_percent' => $collection->averageCoverage(),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new Exceptions\InvalidMetricsDataException("Invalid JSON: {$e->getMessage()}", 0, $e);
        }

        if (!is_array($data)) {
            throw new Exceptions\InvalidMetricsDataException('JSON must decode to an array');
        }

        foreach (['metadata', 'metrics', 'summary'] as $section) {
            if (!array_key_exists($section, $data)) {
                throw new Exceptions\InvalidMetricsDataException("Missing required section: {$section}");
            }
        }

        if (!is_array($data['metrics'])) {
            throw new Exceptions\InvalidMetricsDataException('The "metrics" section must be an array');
        }

        $store = new self();

        foreach ($data['metrics'] as $index => $metricData) {
            $store->add(self::hydrateMetric($metricData, $index));
        }

        return $store;
    }

    private static function hydrateMetric(array $metricData, int $index): TestMetric
    {
        $required = ['test_class', 'test_method', 'test_name', 'status', 'wall_time_ms', 'user_cpu_time_ms', 'system_cpu_time_ms', 'peak_memory_kb', 'executed_at'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $metricData)) {
                throw new Exceptions\InvalidMetricsDataException("Missing required field '{$key}' in metric at index {$index}");
            }
        }

        return new TestMetric(
            testClass: $metricData['test_class'],
            testMethod: $metricData['test_method'],
            testName: $metricData['test_name'],
            status: $metricData['status'],
            wallTime: $metricData['wall_time_ms'] / 1000,
            userCpuTime: $metricData['user_cpu_time_ms'] / 1000,
            systemCpuTime: $metricData['system_cpu_time_ms'] / 1000,
            peakMemoryBytes: (int) round($metricData['peak_memory_kb'] * 1024),
            coveragePercent: $metricData['coverage_percent'] ?? null,
            linesExecuted: $metricData['lines_executed'] ?? null,
            linesTotal: $metricData['lines_total'] ?? null,
            executedAt: new \DateTimeImmutable($metricData['executed_at']),
        );
    }

    private function detectCoverageDriver(): string
    {
        if (\extension_loaded('pcov')) {
            return 'pcov';
        }

        if (\extension_loaded('xdebug')) {
            return 'xdebug';
        }

        return 'none';
    }
}
