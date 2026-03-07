# Documento de Requisitos

## Introdução

Este documento descreve os requisitos para a funcionalidade de coleta de métricas de testes unitários em um projeto Laravel 12. O sistema deve coletar métricas de tempo de execução, cobertura de código e consumo de recursos computacionais (CPU e memória) durante a execução dos testes unitários via PHPUnit. Após a execução, o sistema deve gerar um relatório em formato PDF contendo todas as métricas coletadas, estruturadas de forma adequada para posterior análise estatística com o teste de Wilcoxon.

## Glossário

- **Coletor_De_Metricas**: Componente responsável por instrumentar e coletar métricas durante a execução dos testes unitários
- **Monitor_De_Recursos**: Componente responsável por monitorar o consumo de CPU e memória durante a execução de cada teste
- **Gerador_De_Relatorio**: Componente responsável por compilar as métricas coletadas e gerar o relatório em formato PDF
- **Metrica_De_Tempo**: Registro do tempo de execução de um teste unitário individual, medido em milissegundos
- **Metrica_De_Cobertura**: Registro da porcentagem de linhas de código cobertas pela execução de um teste ou suíte de testes
- **Metrica_De_Recursos**: Registro do consumo de CPU (percentual) e memória (em MB) durante a execução de um teste
- **Relatorio_PDF**: Documento PDF gerado contendo todas as métricas coletadas, formatado para análise estatística
- **Suite_De_Testes**: Conjunto de testes unitários do projeto Laravel executados via PHPUnit

## Requisitos

### Requisito 1: Coleta de Métricas de Tempo de Execução

**User Story:** Como pesquisador, eu quero coletar o tempo de execução de cada teste unitário, para que eu possa analisar a performance dos testes usando o teste de Wilcoxon.

#### Critérios de Aceitação

1. WHEN um teste unitário é executado, THE Coletor_De_Metricas SHALL registrar o tempo de início e o tempo de término do teste em milissegundos
2. WHEN um teste unitário finaliza a execução, THE Coletor_De_Metricas SHALL calcular a duração total do teste subtraindo o tempo de início do tempo de término
3. THE Coletor_De_Metricas SHALL armazenar o nome do teste, o nome da classe de teste e a duração em milissegundos para cada teste executado
4. WHEN a Suite_De_Testes completa a execução, THE Coletor_De_Metricas SHALL calcular o tempo total de execução da suíte inteira

### Requisito 2: Coleta de Métricas de Cobertura de Código

**User Story:** Como pesquisador, eu quero coletar a cobertura de código dos testes unitários, para que eu possa comparar a efetividade dos testes estatisticamente.

#### Critérios de Aceitação

1. WHEN a Suite_De_Testes é executada com cobertura habilitada, THE Coletor_De_Metricas SHALL registrar a porcentagem de linhas cobertas por classe de código-fonte
2. WHEN a Suite_De_Testes completa a execução, THE Coletor_De_Metricas SHALL calcular a porcentagem total de cobertura de código do projeto
3. THE Coletor_De_Metricas SHALL registrar o número de linhas cobertas e o número total de linhas executáveis para cada arquivo de código-fonte
4. IF a extensão Xdebug ou PCOV não estiver disponível, THEN THE Coletor_De_Metricas SHALL exibir uma mensagem de erro indicando que a coleta de cobertura requer Xdebug ou PCOV

### Requisito 3: Coleta de Métricas de Consumo de Recursos Computacionais

**User Story:** Como pesquisador, eu quero monitorar o consumo de CPU e memória durante a execução dos testes, para que eu possa avaliar o impacto computacional dos testes.

#### Critérios de Aceitação

1. WHEN um teste unitário inicia a execução, THE Monitor_De_Recursos SHALL registrar o uso de memória em MB no início do teste
2. WHEN um teste unitário finaliza a execução, THE Monitor_De_Recursos SHALL registrar o pico de uso de memória em MB durante o teste
3. WHEN um teste unitário finaliza a execução, THE Monitor_De_Recursos SHALL registrar o tempo de CPU consumido pelo teste em milissegundos
4. THE Monitor_De_Recursos SHALL calcular o consumo incremental de memória (diferença entre pico e início) para cada teste
5. WHEN a Suite_De_Testes completa a execução, THE Monitor_De_Recursos SHALL registrar o pico total de memória e o tempo total de CPU da suíte

### Requisito 4: Geração de Relatório PDF

**User Story:** Como pesquisador, eu quero gerar um relatório PDF com todas as métricas coletadas, para que eu possa usar os dados na análise estatística com Wilcoxon.

#### Critérios de Aceitação

1. WHEN a Suite_De_Testes completa a execução, THE Gerador_De_Relatorio SHALL gerar um arquivo PDF contendo todas as métricas coletadas
2. THE Gerador_De_Relatorio SHALL incluir no relatório uma tabela com as métricas de tempo de execução por teste (nome do teste, classe, duração em ms)
3. THE Gerador_De_Relatorio SHALL incluir no relatório uma tabela com as métricas de cobertura de código por arquivo (arquivo, linhas cobertas, linhas totais, porcentagem)
4. THE Gerador_De_Relatorio SHALL incluir no relatório uma tabela com as métricas de recursos por teste (nome do teste, memória inicial em MB, pico de memória em MB, tempo de CPU em ms)
5. THE Gerador_De_Relatorio SHALL incluir no relatório um resumo com os totais: tempo total de execução, cobertura total, pico de memória total e tempo total de CPU
6. THE Gerador_De_Relatorio SHALL nomear o arquivo PDF com o padrão "metricas-testes-YYYY-MM-DD-HHmmss.pdf" usando a data e hora da execução
7. THE Gerador_De_Relatorio SHALL salvar o arquivo PDF no diretório "storage/app/reports" do projeto Laravel

### Requisito 5: Integração com PHPUnit

**User Story:** Como desenvolvedor, eu quero que a coleta de métricas se integre ao PHPUnit existente, para que eu possa coletar métricas sem alterar os testes existentes.

#### Critérios de Aceitação

1. THE Coletor_De_Metricas SHALL funcionar como uma extensão do PHPUnit, sem exigir modificações nos testes unitários existentes
2. WHEN o comando de execução de testes é invocado, THE Coletor_De_Metricas SHALL iniciar a coleta de métricas automaticamente
3. WHEN a execução dos testes finaliza, THE Coletor_De_Metricas SHALL acionar o Gerador_De_Relatorio para gerar o PDF
4. THE Coletor_De_Metricas SHALL fornecer um comando Artisan dedicado para executar os testes com coleta de métricas habilitada
5. IF um teste falha durante a execução, THEN THE Coletor_De_Metricas SHALL registrar as métricas coletadas até o ponto de falha e marcar o teste como falho no relatório

### Requisito 6: Estrutura de Dados para Análise Estatística

**User Story:** Como pesquisador, eu quero que os dados no PDF estejam organizados de forma tabular, para que eu possa extrair os dados facilmente para análise com o teste de Wilcoxon.

#### Critérios de Aceitação

1. THE Gerador_De_Relatorio SHALL organizar as métricas em tabelas com colunas claramente rotuladas e valores numéricos precisos
2. THE Gerador_De_Relatorio SHALL incluir no relatório os dados individuais de cada teste (não apenas médias ou totais)
3. THE Gerador_De_Relatorio SHALL formatar valores de tempo com precisão de 2 casas decimais em milissegundos
4. THE Gerador_De_Relatorio SHALL formatar valores de memória com precisão de 2 casas decimais em megabytes
5. THE Gerador_De_Relatorio SHALL incluir um cabeçalho no PDF com a data de execução, versão do PHP, versão do Laravel e número total de testes executados
