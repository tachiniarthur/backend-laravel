<?php

namespace App\Testing\Metrics;

class CoverageResult
{
    /**
     * @param array<string, array<int, int>> $filesCovered
     */
    public function __construct(
        public readonly string $testName,
        public readonly int $linesExecuted,
        public readonly int $linesTotal,
        public readonly float $coveragePercentage,
        public readonly array $filesCovered,
    ) {
    }
}
