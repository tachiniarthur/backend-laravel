# Plano de Implementação: Métricas de Testes Unitários Laravel

## Visão Geral

Implementação incremental do sistema de coleta de métricas de testes unitários, começando pelos modelos de dados e componentes de coleta, seguido pela extensão PHPUnit, geração de relatório PDF e comando Artisan. Cada etapa é validada com testes antes de avançar.

## Tarefas

- [-] 1. Criar modelos de dados e estruturas base
    - [ ] 1.1 Criar as classes `ResourceSnapshot`, `ResourceDiff` e `TestMetric`
        - Criar `app/Testing/Metrics/ResourceSnapshot.php` com propriedades readonly: wallTime, userCpuTime, systemCpuTime, peakMemoryBytes
        - Criar `app/Testing/Metrics/ResourceDiff.php` com propriedades readonly: wallTimeDelta, userCpuTimeDelta, systemCpuTimeDelta, peakMemoryDelta
        - Criar `app/Testing/Metrics/TestMetric.php` com todas as propriedades do design (testClass, testMethod, testName, status, wallTime, userCpuTime, systemCpuTime, peakMemoryBytes, coveragePercent, linesExecuted, linesTotal, executedAt) e método `toArray()`
        - _Requisitos: 1.2, 1.3, 3.1, 3.2, 3.3_
    - [ ]\* 1.2 Escrever teste de propriedade para invariantes de métricas válidas
        - **Propriedade 1: Invariantes de métricas válidas**
        - Gerar 100+ TestMetrics aleatórios e verificar: wallTime >= 0, userCpuTime >= 0, systemCpuTime >= 0, peakMemoryBytes > 0, status válido, toArray() contém todas as chaves obrigatórias
        - **Valida: Requisitos 1.2, 1.3, 4.2**

- [ ]   2. Implementar MetricsResultCollection e MetricsStore
    - [ ] 2.1 Criar `MetricsResultCollection`
        - Criar `app/Testing/Metrics/MetricsResultCollection.php` com métodos: count(), totalWallTime(), averageWallTime(), totalMemoryPeak(), averageCoverage(), groupByClass(), sortByWallTime(), toArray()
        - Implementar lógica de agregação matemática correta
        - _Requisitos: 1.4, 6.2_
    - [ ]\* 2.2 Escrever teste de propriedade para agregação matemática
        - **Propriedade 7: Corretude da agregação matemática**
        - Gerar coleções aleatórias de TestMetrics e verificar: totalWallTime() == soma dos wallTime, averageWallTime() == totalWallTime() / N, count() == N
        - **Valida: Requisitos 1.4**
    - [ ]\* 2.3 Escrever teste de propriedade para unicidade de identificadores
        - **Propriedade 9: Unicidade de identificadores para pareamento Wilcoxon**
        - Verificar que todos os testName são únicos dentro da coleção
        - **Valida: Requisitos 6.2**
    - [ ] 2.4 Criar `MetricsStore`
        - Criar `app/Testing/Metrics/MetricsStore.php` com métodos: add(), all(), toArray(), toJson(), fromJson()
        - Implementar serialização/deserialização JSON com estrutura metadata, metrics e summary
        - _Requisitos: 4.5, 6.1, 6.2_
    - [ ]\* 2.5 Escrever teste de propriedade para round-trip JSON
        - **Propriedade 5: Round-trip de serialização JSON**
        - Gerar MetricsStore com métricas aleatórias, serializar via toJson() e deserializar via fromJson(), verificar equivalência
        - **Valida: Requisitos 4.5, 6.1**

- [ ]   3. Checkpoint - Verificar modelos de dados
    - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [ ]   4. Implementar ResourceMonitor e CoverageCollector
    - [ ] 4.1 Criar `ResourceMonitor`
        - Criar `app/Testing/Metrics/ResourceMonitor.php` com métodos: snapshot() e diff()
        - Usar `getrusage()` para CPU e `memory_get_peak_usage()` para memória
        - Implementar fallback com `microtime(true)` quando `getrusage()` não estiver disponível
        - _Requisitos: 3.1, 3.2, 3.3, 3.4_
    - [ ]\* 4.2 Escrever teste de propriedade para diff de recursos
        - **Propriedade 8: Corretude do diff de recursos**
        - Gerar pares de ResourceSnapshots aleatórios e verificar: wallTimeDelta == after.wallTime - before.wallTime, userCpuTimeDelta == after.userCpuTime - before.userCpuTime, systemCpuTimeDelta == after.systemCpuTime - before.systemCpuTime
        - **Valida: Requisitos 3.4**
    - [ ] 4.3 Criar `CoverageCollector`
        - Criar `app/Testing/Metrics/CoverageCollector.php` com métodos: isAvailable(), getDriver(), startCoverage(), stopCoverage()
        - Criar `app/Testing/Metrics/CoverageResult.php` com propriedades: testName, linesExecuted, linesTotal, coveragePercentage, filesCovered
        - Detectar automaticamente Xdebug ou PCOV; exibir mensagem de erro se nenhum estiver disponível
        - _Requisitos: 2.1, 2.2, 2.3, 2.4_
    - [ ]\* 4.4 Escrever teste de propriedade para invariante de cobertura
        - **Propriedade 3: Invariante de cobertura de código**
        - Gerar CoverageResults aleatórios e verificar: linesExecuted <= linesTotal, linesTotal > 0, coveragePercent == (linesExecuted / linesTotal) \* 100 com tolerância de 0.01
        - **Valida: Requisitos 2.3**
    - [ ]\* 4.5 Escrever teste de propriedade para domínio do driver
        - **Propriedade 4: Domínio do driver de cobertura**
        - Verificar que getDriver() retorna 'xdebug', 'pcov' ou 'none'; quando isAvailable() == true, getDriver() != 'none'
        - **Valida: Requisitos 2.4**

- [ ]   5. Implementar MetricsCollector e extensão PHPUnit
    - [ ] 5.1 Criar `MetricsCollector`
        - Criar `app/Testing/Metrics/MetricsCollector.php` com métodos: recordTestStart(), recordTestEnd(), getResults()
        - Orquestrar ResourceMonitor e CoverageCollector para coletar métricas de cada teste
        - _Requisitos: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3_
    - [ ] 5.2 Criar `MetricsExtension` e subscribers PHPUnit
        - Criar `app/Testing/Metrics/MetricsExtension.php` implementando `PHPUnit\Runner\Extension\Extension`
        - Criar `app/Testing/Metrics/TestPreparedSubscriber.php` implementando `PHPUnit\Event\Test\PreparedSubscriber`
        - Criar `app/Testing/Metrics/TestFinishedSubscriber.php` implementando `PHPUnit\Event\Test\FinishedSubscriber`
        - Registrar subscribers no método bootstrap() da extensão
        - Capturar métricas até o ponto de falha para testes com erro e marcar status como 'error'
        - _Requisitos: 5.1, 5.2, 5.5_
    - [ ]\* 5.3 Escrever teste unitário para transparência da extensão
        - **Propriedade 2: Transparência da extensão**
        - Verificar que a extensão não altera o resultado dos testes (passed/failed/skipped permanece idêntico)
        - **Valida: Requisitos 5.1**

- [ ]   6. Checkpoint - Verificar coleta de métricas
    - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [ ]   7. Implementar geração de relatório PDF
    - [ ] 7.1 Criar `ReportGenerator`
        - Criar `app/Testing/Metrics/ReportGenerator.php` com métodos: generatePdf(), generateHtml(), generateArray()
        - Utilizar `barryvdh/laravel-dompdf` para geração do PDF
        - Gerar HTML intermediário com tabelas de métricas por teste, cobertura por arquivo, recursos por teste e resumo
        - Incluir cabeçalho com data de execução, versão do PHP, versão do Laravel e total de testes
        - Formatar valores de tempo com 2 casas decimais em ms e memória com 2 casas decimais em MB
        - Gerar PDF com mensagem "Nenhum teste executado" quando coleção estiver vazia
        - _Requisitos: 4.1, 4.2, 4.3, 4.4, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_
    - [ ] 7.2 Criar template Blade para o relatório
        - Criar `resources/views/metrics/report.blade.php` com layout das tabelas e formatação
        - Incluir seções: cabeçalho, resumo, tabela de métricas por teste, tabela resumo por classe, dados para Wilcoxon
        - _Requisitos: 4.2, 4.3, 4.4, 4.5, 6.1, 6.3, 6.4_
    - [ ] 7.3 Criar exceção `ReportGenerationException`
        - Criar `app/Testing/Metrics/Exceptions/ReportGenerationException.php`
        - _Requisitos: 4.1_
    - [ ]\* 7.4 Escrever teste de propriedade para conteúdo do relatório
        - **Propriedade 6: Conteúdo do relatório**
        - Gerar conjuntos aleatórios de TestMetrics e verificar que o HTML gerado contém: nome de cada teste, valores de tempo e memória, seções de cabeçalho, resumo e tabela
        - **Valida: Requisitos 4.2, 4.3, 4.4**

- [ ]   8. Implementar comando Artisan e integração final
    - [ ] 8.1 Criar comando `TestMetricsCommand`
        - Criar `app/Console/Commands/TestMetricsCommand.php` com signature `test:metrics` e opções: --filter, --coverage, --output, --json
        - Configurar extensão PHPUnit programaticamente e executar testes
        - Acionar ReportGenerator após execução dos testes
        - Nomear PDF com padrão "metricas-testes-YYYY-MM-DD-HHmmss.pdf"
        - Salvar PDF em `storage/app/reports`, criando diretório recursivamente se necessário
        - Exportar JSON quando opção --json for utilizada
        - _Requisitos: 4.6, 4.7, 5.2, 5.3, 5.4_
    - [ ] 8.2 Criar exceção `InvalidMetricsDataException`
        - Criar `app/Testing/Metrics/Exceptions/InvalidMetricsDataException.php` para erros de deserialização JSON
        - _Requisitos: 4.5_
    - [ ]\* 8.3 Escrever testes unitários para o comando Artisan
        - Testar execução com diferentes combinações de opções (--filter, --output, --json, --coverage)
        - Testar criação automática do diretório de saída
        - Testar tratamento de erros (diretório sem permissão, driver de cobertura indisponível)
        - _Requisitos: 5.2, 5.3, 5.4_

- [ ]   9. Checkpoint final - Verificar integração completa
    - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

## Notas

- Tarefas marcadas com `*` são opcionais e podem ser puladas para um MVP mais rápido
- Cada tarefa referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Testes de propriedade validam propriedades universais de corretude
- Testes unitários validam exemplos específicos e edge cases
