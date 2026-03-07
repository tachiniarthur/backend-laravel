<?php

namespace App\Testing\Metrics;

class TestMetric
{
    public function __construct(
        public readonly string $testClass,
        public readonly string $testMethod,
        public readonly string $testName,
        public readonly string $status,
        public readonly float $wallTime,
        public readonly float $userCpuTime,
        public readonly float $systemCpuTime,
        public readonly int $peakMemoryBytes,
        public readonly ?float $coveragePercent,
        public readonly ?int $linesExecuted,
        public readonly ?int $linesTotal,
        public readonly \DateTimeImmutable $executedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'test_class' => $this->testClass,
            'test_method' => $this->testMethod,
            'test_name' => $this->testName,
            'status' => $this->status,
            'wall_time' => $this->wallTime,
            'user_cpu_time' => $this->userCpuTime,
            'system_cpu_time' => $this->systemCpuTime,
            'peak_memory_bytes' => $this->peakMemoryBytes,
            'coverage_percent' => $this->coveragePercent,
            'lines_executed' => $this->linesExecuted,
            'lines_total' => $this->linesTotal,
            'executed_at' => $this->executedAt->format('c'),
        ];
    }
}
