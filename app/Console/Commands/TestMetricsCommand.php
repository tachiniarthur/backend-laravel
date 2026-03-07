<?php

namespace App\Console\Commands;

use App\Testing\Metrics\Exceptions\ReportGenerationException;
use App\Testing\Metrics\MetricsStore;
use App\Testing\Metrics\ReportGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestMetricsCommand extends Command
{
    protected $signature = 'test:metrics
        {--filter= : Filtro de testes PHPUnit}
        {--testsuite=JuniorPleno : Nome da testsuite PHPUnit (padrão: JuniorPleno)}
        {--all : Executar todas as testsuites em vez de apenas a padrão}
        {--no-coverage : Desabilitar coleta de cobertura de código}
        {--output= : Caminho do arquivo PDF de saída}
        {--json : Exportar também em formato JSON}';

    protected $description = 'Executa testes unitários e coleta métricas de desempenho';

    public function handle(): int
    {
        $outputPath = $this->resolveOutputPath();
        $outputDir = dirname($outputPath);

        if (!$this->ensureDirectoryExists($outputDir)) {
            $this->error("Não foi possível criar o diretório de saída: {$outputDir}");
            return 1;
        }

        $metricsJsonPath = tempnam(sys_get_temp_dir(), 'metrics_') . '.json';

        $this->info('Executando testes com coleta de métricas...');

        $exitCode = $this->runPhpUnit($metricsJsonPath);

        if (!file_exists($metricsJsonPath)) {
            $this->error('Nenhum dado de métricas foi coletado. Verifique se a extensão MetricsExtension está configurada no phpunit.xml.');
            return 1;
        }

        $json = file_get_contents($metricsJsonPath);
        @unlink($metricsJsonPath);

        if ($json === false || $json === '') {
            $this->error('Arquivo de métricas vazio ou ilegível.');
            return 1;
        }

        try {
            $store = MetricsStore::fromJson($json);
        } catch (\InvalidArgumentException $e) {
            $this->error("Erro ao processar métricas: {$e->getMessage()}");
            return 1;
        }

        return $this->generateReports($store, $outputPath);
    }

    private function resolveOutputPath(): string
    {
        if ($output = $this->option('output')) {
            return $output;
        }

        return storage_path('app/reports/metricas-testes-' . date('Y-m-d-His') . '.pdf');
    }

    private function ensureDirectoryExists(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return mkdir($directory, 0755, true);
    }

    private function runPhpUnit(string $metricsJsonPath): int
    {
        $command = $this->buildPhpUnitCommand();

        $env = [
            'METRICS_OUTPUT_PATH' => $metricsJsonPath,
        ];

        if (!$this->option('no-coverage')) {
            $env['METRICS_COVERAGE_ENABLED'] = 'true';
        }

        $process = new Process(
            $command,
            base_path(),
            array_merge(getenv(), $env),
        );

        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }

    /**
     * @return string[]
     */
    private function buildPhpUnitCommand(): array
    {
        $command = [PHP_BINARY, 'vendor/bin/phpunit'];

        if ($filter = $this->option('filter')) {
            $command[] = '--filter';
            $command[] = $filter;
        }

        if (!$this->option('all') && ($testsuite = $this->option('testsuite'))) {
            $command[] = '--testsuite';
            $command[] = $testsuite;
        }

        return $command;
    }

    private function generateReports(MetricsStore $store, string $outputPath): int
    {
        $generator = new ReportGenerator($store);

        try {
            $generator->generatePdf($outputPath);
            $this->info("Relatório PDF gerado: {$outputPath}");
        } catch (ReportGenerationException $e) {
            $this->error("Erro ao gerar relatório PDF: {$e->getMessage()}");
            return 1;
        }

        if ($this->option('json')) {
            $jsonPath = preg_replace('/\.pdf$/i', '.json', $outputPath);
            if ($jsonPath === $outputPath) {
                $jsonPath = $outputPath . '.json';
            }

            file_put_contents($jsonPath, $store->toJson());
            $this->info("Relatório JSON gerado: {$jsonPath}");
        }

        return 0;
    }
}
