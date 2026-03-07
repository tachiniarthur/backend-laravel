<?php

namespace App\Testing\Metrics;

/**
 * Tracks the outcome status of each test.
 *
 * Since PHPUnit 11's Finished event doesn't carry pass/fail status,
 * outcome subscribers (Passed, Failed, Errored, Skipped) update this
 * tracker before the Finished event fires.
 */
class TestStatusTracker
{
    /** @var array<string, string> */
    private array $statuses = [];

    public function setStatus(string $testId, string $status): void
    {
        $this->statuses[$testId] = $status;
    }

    public function getStatus(string $testId): string
    {
        return $this->statuses[$testId] ?? 'error';
    }

    public function clear(string $testId): void
    {
        unset($this->statuses[$testId]);
    }
}
