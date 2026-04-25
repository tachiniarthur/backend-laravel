<?php

namespace App\Testing\Metrics;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportGenerator
{
    private MetricsStore $store;

    public function __construct(MetricsStore $store)
    {
        $this->store = $store;
    }

    /**
     * Generate a PDF report and save it to the specified path.
     *
     * @throws \App\Testing\Metrics\Exceptions\ReportGenerationException
     */
    public function generatePdf(string $outputPath): string
    {
        try {
            $html = $this->generateHtml();
            Pdf::loadHTML($html)->save($outputPath);

            return $outputPath;
        } catch (\Throwable $e) {
            throw new Exceptions\ReportGenerationException(
                "Falha ao gerar relatório PDF: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate an HTML report string.
     */
    public function generateHtml(): string
    {
        $data = $this->generateArray();
        $data['formatted'] = $this->formatDataForDisplay($data);

        return view('metrics.report', $data)->render();
    }

    /**
     * Generate the report data as an array.
     */
    public function generateArray(): array
    {
        return $this->store->toArray();
    }

    /**
     * Format raw data for display in the report.
     */
    private function formatDataForDisplay(array $data): array
    {
        $metrics = array_map(function (array $metric) {
            return [
                'test_name' => $metric['test_name'],
                'test_class' => $metric['test_class'],
                'test_method' => $metric['test_method'],
                'status' => $metric['status'],
                'wall_time_ms' => number_format($metric['wall_time_ms'], 2),
                'user_cpu_time_ms' => number_format($metric['user_cpu_time_ms'], 2),
                'system_cpu_time_ms' => number_format($metric['system_cpu_time_ms'], 2),
                'peak_memory_mb' => number_format($metric['peak_memory_kb'] / 1024, 2),
                'coverage_percent' => $metric['coverage_percent'] !== null
                    ? number_format($metric['coverage_percent'], 2)
                    : '-',
                'lines_executed' => $metric['lines_executed'] ?? '-',
                'lines_total' => $metric['lines_total'] ?? '-',
            ];
        }, $data['metrics']);

        $summary = [
            'total_wall_time_ms' => number_format($data['summary']['total_wall_time_ms'], 2),
            'average_wall_time_ms' => number_format($data['summary']['average_wall_time_ms'], 2),
            'total_peak_memory_mb' => number_format($data['summary']['total_peak_memory_kb'] / 1024, 2),
            'average_coverage_percent' => $data['summary']['average_coverage_percent'] !== null
                ? number_format($data['summary']['average_coverage_percent'], 2)
                : '-',
        ];

        $moduleCoverage = $this->computeModuleCoverage($data['metrics']);

        return [
            'metrics' => $metrics,
            'summary' => $summary,
            'module_coverage' => $moduleCoverage,
            'is_empty' => empty($data['metrics']),
        ];
    }

    /**
     * Group test metrics by business domain module and compute average coverage per module.
     *
     * @param array $metrics Raw metrics array
     * @return array<string, array{tests: int, avg_coverage: string, avg_time_ms: string}>
     */
    private function computeModuleCoverage(array $metrics): array
    {
        $moduleKeywords = [
            'auth' => ['auth', 'login', 'logout', 'register', 'password'],
            'user' => ['user', 'profile', 'account'],
            'product' => ['product'],
            'cart' => ['cart'],
            'order' => ['order'],
            'admin' => ['admin'],
        ];

        $moduleData = [];

        foreach ($metrics as $metric) {
            $testClass = strtolower($metric['test_class'] ?? '');
            $testMethod = strtolower($metric['test_method'] ?? '');
            $combined = $testClass . ' ' . $testMethod;

            $assigned = 'outros';
            foreach ($moduleKeywords as $module => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($combined, $kw)) {
                        $assigned = $module;
                        break 2;
                    }
                }
            }

            if (!isset($moduleData[$assigned])) {
                $moduleData[$assigned] = ['tests' => 0, 'coverage_sum' => 0.0, 'coverage_count' => 0, 'time_sum' => 0.0];
            }

            $moduleData[$assigned]['tests']++;
            $moduleData[$assigned]['time_sum'] += (float) $metric['wall_time_ms'];

            if ($metric['coverage_percent'] !== null) {
                $moduleData[$assigned]['coverage_sum'] += (float) $metric['coverage_percent'];
                $moduleData[$assigned]['coverage_count']++;
            }
        }

        ksort($moduleData);

        $result = [];
        foreach ($moduleData as $module => $data) {
            $avgCoverage = $data['coverage_count'] > 0
                ? number_format($data['coverage_sum'] / $data['coverage_count'], 2) . '%'
                : '-';
            $result[$module] = [
                'tests' => $data['tests'],
                'avg_coverage' => $avgCoverage,
                'avg_time_ms' => number_format($data['time_sum'] / max(1, $data['tests']), 2),
            ];
        }

        return $result;
    }
}
