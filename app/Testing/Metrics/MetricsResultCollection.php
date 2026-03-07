<?php

namespace App\Testing\Metrics;

class MetricsResultCollection
{
    /** @var TestMetric[] */
    private array $metrics;

    /**
     * @param TestMetric[] $metrics
     */
    public function __construct(array $metrics = [])
    {
        $this->metrics = array_values($metrics);
    }

    public function count(): int
    {
        return count($this->metrics);
    }

    public function totalWallTime(): float
    {
        return array_sum(array_map(fn(TestMetric $m) => $m->wallTime, $this->metrics));
    }

    public function averageWallTime(): float
    {
        if ($this->count() === 0) {
            return 0.0;
        }

        return $this->totalWallTime() / $this->count();
    }

    public function totalMemoryPeak(): int
    {
        if ($this->count() === 0) {
            return 0;
        }

        return max(array_map(fn(TestMetric $m) => $m->peakMemoryBytes, $this->metrics));
    }

    public function averageCoverage(): ?float
    {
        $withCoverage = array_filter(
            $this->metrics,
            fn(TestMetric $m) => $m->coveragePercent !== null
        );

        if (empty($withCoverage)) {
            return null;
        }

        return array_sum(array_map(fn(TestMetric $m) => $m->coveragePercent, $withCoverage)) / count($withCoverage);
    }

    /**
     * @return array<string, TestMetric[]>
     */
    public function groupByClass(): array
    {
        $grouped = [];

        foreach ($this->metrics as $metric) {
            $grouped[$metric->testClass][] = $metric;
        }

        return $grouped;
    }

    public function sortByWallTime(string $direction = 'desc'): self
    {
        $sorted = $this->metrics;

        usort($sorted, function (TestMetric $a, TestMetric $b) use ($direction) {
            return $direction === 'desc'
                ? $b->wallTime <=> $a->wallTime
                : $a->wallTime <=> $b->wallTime;
        });

        return new self($sorted);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn(TestMetric $m) => $m->toArray(), $this->metrics);
    }
}
