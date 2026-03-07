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

        return [
            'metrics' => $metrics,
            'summary' => $summary,
            'is_empty' => empty($data['metrics']),
        ];
    }
}
