<?php

namespace App\Testing\Metrics;

class ResourceMonitor
{
    /**
     * Capture a snapshot of the current resource state.
     */
    public function snapshot(): ResourceSnapshot
    {
        $wallTime = microtime(true);
        $userCpuTime = 0.0;
        $systemCpuTime = 0.0;

        if (function_exists('getrusage')) {
            $usage = getrusage();
            if ($usage !== false) {
                $userCpuTime = $usage['ru_utime.tv_sec'] + $usage['ru_utime.tv_usec'] / 1_000_000;
                $systemCpuTime = $usage['ru_stime.tv_sec'] + $usage['ru_stime.tv_usec'] / 1_000_000;
            }
        }

        $peakMemoryBytes = memory_get_peak_usage(true);

        return new ResourceSnapshot(
            wallTime: $wallTime,
            userCpuTime: $userCpuTime,
            systemCpuTime: $systemCpuTime,
            peakMemoryBytes: $peakMemoryBytes,
        );
    }

    /**
     * Compute the difference between two snapshots.
     */
    public function diff(ResourceSnapshot $before, ResourceSnapshot $after): ResourceDiff
    {
        return new ResourceDiff(
            wallTimeDelta: $after->wallTime - $before->wallTime,
            userCpuTimeDelta: $after->userCpuTime - $before->userCpuTime,
            systemCpuTimeDelta: $after->systemCpuTime - $before->systemCpuTime,
            peakMemoryDelta: $after->peakMemoryBytes - $before->peakMemoryBytes,
        );
    }
}
