<?php

namespace App\Testing\Metrics;

class CoverageCollector
{
    private string $driver;
    private string $currentTestName = '';

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? $this->detectDriver();
    }

    public function isAvailable(): bool
    {
        return $this->driver !== 'none';
    }

    /**
     * @return string 'xdebug', 'pcov', or 'none'
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    public function startCoverage(string $testName): void
    {
        $this->currentTestName = $testName;

        if ($this->driver === 'pcov') {
            \pcov\start();
        } elseif ($this->driver === 'xdebug') {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
        // If 'none', no-op
    }

    public function stopCoverage(): CoverageResult
    {
        if ($this->driver === 'none') {
            return new CoverageResult(
                testName: $this->currentTestName,
                linesExecuted: 0,
                linesTotal: 0,
                coveragePercentage: 0.0,
                filesCovered: [],
            );
        }

        $coverageData = $this->collectCoverageData();

        $linesExecuted = 0;
        $linesTotal = 0;

        foreach ($coverageData as $lines) {
            foreach ($lines as $status) {
                $linesTotal++;
                if ($status === 1) {
                    $linesExecuted++;
                }
            }
        }

        $coveragePercentage = $linesTotal > 0
            ? ($linesExecuted / $linesTotal) * 100
            : 0.0;

        return new CoverageResult(
            testName: $this->currentTestName,
            linesExecuted: $linesExecuted,
            linesTotal: $linesTotal,
            coveragePercentage: $coveragePercentage,
            filesCovered: $coverageData,
        );
    }

    private function detectDriver(): string
    {
        if (extension_loaded('pcov')) {
            return 'pcov';
        }

        if (extension_loaded('xdebug') && function_exists('xdebug_start_code_coverage')) {
            return 'xdebug';
        }

        return 'none';
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function collectCoverageData(): array
    {
        if ($this->driver === 'pcov') {
            \pcov\stop();
            $data = \pcov\collect();
            \pcov\clear();
            return $data;
        }

        if ($this->driver === 'xdebug') {
            $data = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            return $data;
        }

        return [];
    }
}
