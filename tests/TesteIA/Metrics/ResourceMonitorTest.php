<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\ResourceDiff;
use App\Testing\Metrics\ResourceMonitor;
use App\Testing\Metrics\ResourceSnapshot;
use Tests\TesteIA\TesteIATestCase;

class ResourceMonitorTest extends TesteIATestCase
{
    public function test_snapshot_returnsResourceSnapshot(): void
    {
        $monitor = new ResourceMonitor();

        $snapshot = $monitor->snapshot();

        $this->assertInstanceOf(ResourceSnapshot::class, $snapshot);
        $this->assertGreaterThan(0, $snapshot->wallTime);
        $this->assertGreaterThanOrEqual(0, $snapshot->userCpuTime);
        $this->assertGreaterThanOrEqual(0, $snapshot->systemCpuTime);
        $this->assertGreaterThan(0, $snapshot->peakMemoryBytes);
    }

    public function test_diff_computesDifferenceBetweenSnapshots(): void
    {
        $monitor = new ResourceMonitor();

        $before = $monitor->snapshot();
        // Do some work to create a measurable diff
        $arr = range(1, 10000);
        sort($arr);
        $after = $monitor->snapshot();

        $diff = $monitor->diff($before, $after);

        $this->assertInstanceOf(ResourceDiff::class, $diff);
        $this->assertGreaterThanOrEqual(0, $diff->wallTimeDelta);
        $this->assertIsFloat($diff->wallTimeDelta);
        $this->assertIsFloat($diff->userCpuTimeDelta);
        $this->assertIsFloat($diff->systemCpuTimeDelta);
        $this->assertIsInt($diff->peakMemoryDelta);
    }

    public function test_diff_wallTimeDeltaIsPositive(): void
    {
        $monitor = new ResourceMonitor();

        $before = $monitor->snapshot();
        usleep(1000); // 1ms
        $after = $monitor->snapshot();

        $diff = $monitor->diff($before, $after);

        $this->assertGreaterThan(0, $diff->wallTimeDelta);
    }

    public function test_snapshot_peakMemoryIsReasonable(): void
    {
        $monitor = new ResourceMonitor();

        $snapshot = $monitor->snapshot();

        // Peak memory should be at least 1MB for a PHP process
        $this->assertGreaterThan(1024 * 1024, $snapshot->peakMemoryBytes);
    }
}
