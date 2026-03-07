<?php

namespace App\Testing\Metrics;

class ResourceDiff
{
    public function __construct(
        public readonly float $wallTimeDelta,
        public readonly float $userCpuTimeDelta,
        public readonly float $systemCpuTimeDelta,
        public readonly int $peakMemoryDelta,
    ) {
    }
}
