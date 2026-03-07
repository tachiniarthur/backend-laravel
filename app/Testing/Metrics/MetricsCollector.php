<?php

namespace App\Testing\Metrics;

class MetricsCollector
{
    private MetricsStore $store;
    private ResourceMonitor $resourceMonitor;
    private CoverageCollector $coverageCollector;
    private bool $coverageEnabled;

    /** @var array<string, ResourceSnapshot> */
    private array $startSnapshots = [];

    public function __construct(
        ResourceMonitor $resourceMonitor,
        CoverageCollector $coverageCollector,
        bool $coverageEnabled = false,
    ) {
        $this->resourceMonitor = $resourceMonitor;
        $this->coverageCollector = $coverageCollector;
        $this->coverageEnabled = $coverageEnabled && $coverageCollector->isAvailable();
        $this->store = new MetricsStore();
    }

    /**
     * Record the start of a test execution.
     * Takes a resource snapshot and starts coverage collection if enabled.
     */
    public function recordTestStart(string $testName): void
    {
        $this->startSnapshots[$testName] = $this->resourceMonitor->snapshot();

        if ($this->coverageEnabled) {
            $this->coverageCollector->startCoverage($testName);
        }
    }

    /**
     * Record the end of a test execution.
     * Takes a final snapshot, computes resource diff, stops coverage, and stores the metric.
     *
     * @param string $testName Full test name (e.g., "Tests\Unit\ExampleTest::testBasicExample")
     * @param string $status One of: 'passed', 'failed', 'skipped', 'error'
     */
    public function recordTestEnd(string $testName, string $status): void
    {
        $endSnapshot = $this->resourceMonitor->snapshot();

        $startSnapshot = $this->startSnapshots[$testName] ?? $endSnapshot;
        $diff = $this->resourceMonitor->diff($startSnapshot, $endSnapshot);

        $coverageResult = null;
        if ($this->coverageEnabled) {
            $coverageResult = $this->coverageCollector->stopCoverage();
        }

        [$testClass, $testMethod] = $this->parseTestName($testName);

        $metric = new TestMetric(
            testClass: $testClass,
            testMethod: $testMethod,
            testName: $testName,
            status: $status,
            wallTime: $diff->wallTimeDelta,
            userCpuTime: $diff->userCpuTimeDelta,
            systemCpuTime: $diff->systemCpuTimeDelta,
            peakMemoryBytes: $endSnapshot->peakMemoryBytes,
            coveragePercent: $coverageResult?->coveragePercentage,
            linesExecuted: $coverageResult?->linesExecuted,
            linesTotal: $coverageResult?->linesTotal,
            executedAt: new \DateTimeImmutable(),
        );

        $this->store->add($metric);

        unset($this->startSnapshots[$testName]);
    }

    /**
     * Get all collected metrics as a MetricsResultCollection.
     */
    public function getResults(): MetricsResultCollection
    {
        return new MetricsResultCollection(array_values($this->store->all()));
    }

    /**
     * Get the underlying MetricsStore.
     */
    public function getStore(): MetricsStore
    {
        return $this->store;
    }


    /**
     * Parse a test name into [testClass, testMethod].
     * Splits on "::" separator. If no separator found, class is the full name and method is empty.
     *
     * @return array{0: string, 1: string}
     */
    private function parseTestName(string $testName): array
    {
        if (str_contains($testName, '::')) {
            $parts = explode('::', $testName, 2);
            return [$parts[0], $parts[1]];
        }

        return [$testName, ''];
    }
}
