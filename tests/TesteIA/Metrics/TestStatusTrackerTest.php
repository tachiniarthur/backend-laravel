<?php

namespace Tests\TesteIA\Metrics;

use App\Testing\Metrics\TestStatusTracker;
use Tests\TesteIA\TesteIATestCase;

class TestStatusTrackerTest extends TesteIATestCase
{
    public function test_setStatus_andGetStatus_returnsCorrectStatus(): void
    {
        $tracker = new TestStatusTracker();

        $tracker->setStatus('test-1', 'passed');

        $this->assertEquals('passed', $tracker->getStatus('test-1'));
    }

    public function test_getStatus_withUnknownTestId_returnsError(): void
    {
        $tracker = new TestStatusTracker();

        $this->assertEquals('error', $tracker->getStatus('unknown-test'));
    }

    public function test_clear_removesStatus(): void
    {
        $tracker = new TestStatusTracker();

        $tracker->setStatus('test-1', 'passed');
        $tracker->clear('test-1');

        // After clear, should return default 'error'
        $this->assertEquals('error', $tracker->getStatus('test-1'));
    }

    public function test_setStatus_overwritesPreviousStatus(): void
    {
        $tracker = new TestStatusTracker();

        $tracker->setStatus('test-1', 'passed');
        $tracker->setStatus('test-1', 'failed');

        $this->assertEquals('failed', $tracker->getStatus('test-1'));
    }

    public function test_multipleTests_trackedIndependently(): void
    {
        $tracker = new TestStatusTracker();

        $tracker->setStatus('test-1', 'passed');
        $tracker->setStatus('test-2', 'failed');
        $tracker->setStatus('test-3', 'skipped');

        $this->assertEquals('passed', $tracker->getStatus('test-1'));
        $this->assertEquals('failed', $tracker->getStatus('test-2'));
        $this->assertEquals('skipped', $tracker->getStatus('test-3'));
    }
}
