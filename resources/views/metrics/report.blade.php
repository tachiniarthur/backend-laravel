<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Métricas de Testes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 15px;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 13px;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 3px 5px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            font-size: 10px;
        }

        .header-info {
            color: #555;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #888;
            font-size: 14px;
        }

        .metrics-table td:first-child {
            font-size: 8px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .metrics-table td {
            font-size: 9px;
            text-align: right;
        }

        .metrics-table td:first-child,
        .metrics-table td:nth-child(2) {
            text-align: left;
        }

        .class-table td:first-child {
            font-size: 9px;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wilcoxon-table td:first-child {
            font-size: 8px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wilcoxon-table td {
            font-size: 9px;
            text-align: right;
        }

        .wilcoxon-table td:first-child {
            text-align: left;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <h1>Relatório de Métricas de Testes</h1>
    <div class="header-info">
        <p>Data de execução: {{ $metadata['executed_at'] }}</p>
        <p>PHP: {{ $metadata['php_version'] }} | Laravel: {{ app()->version() }} | Total de testes:
            {{ $metadata['total_tests'] }}
        </p>
    </div>

    @if($formatted['is_empty'])
        <div class="empty-message">Nenhum teste executado</div>
    @else
        <h2>Resumo</h2>
        <table>
            <tr>
                <th>Tempo Total (ms)</th>
                <th>Tempo Médio (ms)</th>
                <th>Pico de Memória (MB)</th>
                <th>Cobertura Média (%)</th>
            </tr>
            <tr>
                <td>{{ $formatted['summary']['total_wall_time_ms'] }}</td>
                <td>{{ $formatted['summary']['average_wall_time_ms'] }}</td>
                <td>{{ $formatted['summary']['total_peak_memory_mb'] }}</td>
                <td>{{ $formatted['summary']['average_coverage_percent'] }}</td>
            </tr>
        </table>

        <h2>Métricas por Teste</h2>
        <table class="metrics-table">
            <colgroup>
                <col style="width: 38%">
                <col style="width: 8%">
                <col style="width: 10%">
                <col style="width: 10%">
                <col style="width: 10%">
                <col style="width: 12%">
                <col style="width: 12%">
            </colgroup>
            <tr>
                <th>Teste</th>
                <th>Status</th>
                <th>Tempo (ms)</th>
                <th>CPU User (ms)</th>
                <th>CPU Sys (ms)</th>
                <th>Memória (MB)</th>
                <th>Cobertura (%)</th>
            </tr>
            @foreach($formatted['metrics'] as $metric)
                <tr>
                    <td title="{{ $metric['test_name'] }}">{{ $metric['test_method'] ?: $metric['test_name'] }}</td>
                    <td>{{ $metric['status'] }}</td>
                    <td>{{ $metric['wall_time_ms'] }}</td>
                    <td>{{ $metric['user_cpu_time_ms'] }}</td>
                    <td>{{ $metric['system_cpu_time_ms'] }}</td>
                    <td>{{ $metric['peak_memory_mb'] }}</td>
                    <td>{{ $metric['coverage_percent'] }}</td>
                </tr>
            @endforeach
        </table>

        <div class="page-break"></div>
        <h2>Resumo por Classe</h2>
        <table class="class-table">
            <colgroup>
                <col style="width: 55%">
                <col style="width: 15%">
                <col style="width: 15%">
                <col style="width: 15%">
            </colgroup>
            <tr>
                <th>Classe</th>
                <th>Testes</th>
                <th>Tempo Total (ms)</th>
                <th>Memória Pico (MB)</th>
            </tr>
            @php
                $grouped = collect($metrics)->groupBy('test_class');
            @endphp
            @foreach($grouped as $class => $classMetrics)
                <tr>
                    <td>{{ $class }}</td>
                    <td>{{ count($classMetrics) }}</td>
                    <td>{{ number_format(collect($classMetrics)->sum('wall_time_ms'), 2) }}</td>
                    <td>{{ number_format(collect($classMetrics)->max('peak_memory_kb') / 1024, 2) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="page-break"></div>
        <h2>Dados para Análise Wilcoxon</h2>
        <table class="wilcoxon-table">
            <colgroup>
                <col style="width: 40%">
                <col style="width: 15%">
                <col style="width: 15%">
                <col style="width: 15%">
                <col style="width: 15%">
            </colgroup>
            <tr>
                <th>Teste</th>
                <th>Tempo (ms)</th>
                <th>CPU User (ms)</th>
                <th>CPU Sys (ms)</th>
                <th>Memória (KB)</th>
            </tr>
            @foreach($metrics as $metric)
                <tr>
                    <td title="{{ $metric['test_name'] }}">{{ $metric['test_method'] ?: $metric['test_name'] }}</td>
                    <td>{{ $metric['wall_time_ms'] }}</td>
                    <td>{{ $metric['user_cpu_time_ms'] }}</td>
                    <td>{{ $metric['system_cpu_time_ms'] }}</td>
                    <td>{{ $metric['peak_memory_kb'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>

</html>
