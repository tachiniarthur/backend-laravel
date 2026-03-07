<?php

namespace App\Testing\Metrics;

class ResourceSnapshot
{
    public function __construct(
        public readonly float $wallTime,
        public readonly float $userCpuTime,
        public readonly float $systemCpuTime,
        public readonly int $peakMemoryBytes,
    ) {
    }
}
