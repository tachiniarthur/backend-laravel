# Documento de Requisitos

## Introdução

Este documento descreve os requisitos para a implementação de uma primeira rodada de testes unitários no backend Laravel, simulando testes escritos por um desenvolvedor júnior/pleno. O objetivo é criar uma suíte de testes parcial que cubra as funcionalidades principais do sistema (Controllers, Services e Models) sem atingir cobertura total. Esta suíte servirá como baseline para comparação futura com testes gerados por IA que buscarão 100% de cobertura. Os testes devem utilizar PHPUnit (framework padrão do Laravel) e integrar-se ao sistema de métricas já existente em `app/Testing/Metrics/` para coleta de dados de tempo de execução, cobertura de código e consumo de recursos computacionais.

## Glossário

- **Suite_Junior**: Conjunto de testes unitários desta primeira rodada, organizados na pasta `tests/JuniorPlenoTests`, simulando testes escritos por um desenvolvedor júnior/pleno
- **PHPUnit**: Framework de testes padrão do Laravel utilizado para execução dos testes unitários
- **Sistema_De_Metricas**: Infraestrutura existente em `app/Testing/Metrics/` responsável por coletar métricas de tempo, cobertura e recursos durante a execução dos testes
- **AuthService**: Serviço responsável pela lógica de autenticação (login e criação de conta)
- **ProductService**: Serviço responsável pela lógica de negócio de produtos (CRUD com controle de permissão admin)
- **CartService**: Serviço responsável pela lógica de negócio do carrinho de compras (adicionar, atualizar, remover itens com controle de estoque)
- **OrderService**: Serviço responsável pela lógica de negócio de pedidos (criação a partir do carrinho com validação de estoque)
- **AuthController**: Controller responsável pelos endpoints de autenticação (login, logout, criação de conta)
- **ProductController**: Controller responsável pelos endpoints de produtos (listagem, detalhes, CRUD admin)
- **CartController**: Controller responsável pelos endpoints do carrinho de compras
- **OrderController**: Controller responsável pelos endpoints de pedidos
- **UserController**: Controller responsável pelos endpoints de perfil do usuário
- **Product_Model**: Model Eloquent que representa um produto com atributos calculados (available_stock, reserved_quantity) e scope active
- **Order_Model**: Model Eloquent que representa um pedido com atributo calculado total
- **User_Model**: Model Eloquent que representa um usuário com relacionamentos para pedidos e itens do carrinho
- **Cobertura_Parcial**: Nível de cobertura de código esperado para esta rodada, representando o que é comum em projetos reais por desenvolvedores júnior/pleno (cobertura incompleta intencional)

## Requisitos

### Requisito 1: Organização dos Testes em Pasta Dedicada

**User Story:** Como pesquisador, eu quero que os testes desta primeira rodada estejam em uma pasta separada, para que eu possa identificar claramente que pertencem à suíte simulando um desenvolvedor júnior/pleno e compará-los com testes gerados por IA na segunda fase.

#### Critérios de Aceitação

1. THE Suite_Junior SHALL armazenar todos os testes unitários desta rodada no diretório `tests/JuniorPlenoTests`
2. WHEN a Suite_Junior é configurada, THE PHPUnit SHALL registrar o diretório `tests/JuniorPlenoTests` como uma testsuite nomeada "JuniorPleno" no arquivo `phpunit.xml`
3. THE Suite_Junior SHALL manter uma estrutura de subpastas que espelhe a organização do código-fonte: `Controllers/`, `Services/` e `Models/`
4. THE Suite_Junior SHALL incluir uma classe base `JuniorPlenoTestCase` que estenda `Tests\TestCase` para uso compartilhado de helpers e setup comum

### Requisito 2: Testes Unitários dos Services

**User Story:** Como pesquisador, eu quero testes unitários para os Services do sistema, para que as regras de negócio principais estejam cobertas nesta primeira rodada de testes.

#### Critérios de Aceitação

1. WHEN o AuthService recebe credenciais válidas, THE Suite_Junior SHALL verificar que o método login retorna um array contendo as chaves "user" e "token"
2. WHEN o AuthService recebe credenciais inválidas (email inexistente ou senha incorreta), THE Suite_Junior SHALL verificar que o método login retorna null
3. WHEN o AuthService recebe dados válidos para criação de conta, THE Suite_Junior SHALL verificar que o método create retorna um array contendo as chaves "user" e "token" e que o usuário é persistido no banco de dados
4. WHEN o ProductService recebe dados válidos e um ator admin, THE Suite_Junior SHALL verificar que o método create retorna um Product persistido no banco de dados
5. WHEN o ProductService recebe um ator não-admin, THE Suite_Junior SHALL verificar que o método create lança AuthorizationException
6. WHEN o ProductService lista produtos, THE Suite_Junior SHALL verificar que o método list retorna apenas produtos com status active igual a true
7. WHEN o CartService recebe um produto ativo com estoque disponível, THE Suite_Junior SHALL verificar que o método addItem cria um CartItem associado ao usuário
8. WHEN o CartService recebe um produto inativo, THE Suite_Junior SHALL verificar que o método addItem lança ValidationException com mensagem indicando indisponibilidade
9. WHEN o CartService recebe uma quantidade que excede o estoque disponível, THE Suite_Junior SHALL verificar que o método addItem lança ValidationException com mensagem indicando estoque insuficiente
10. WHEN o OrderService recebe itens válidos com estoque suficiente, THE Suite_Junior SHALL verificar que o método createFromCart cria um Order com status "pending", cria os OrderItems associados e decrementa o estoque dos produtos
11. WHEN o OrderService recebe itens com estoque insuficiente, THE Suite_Junior SHALL verificar que o método createFromCart lança ValidationException

### Requisito 3: Testes Unitários dos Controllers

**User Story:** Como pesquisador, eu quero testes unitários para os Controllers principais, para que os fluxos de API mais comuns estejam cobertos nesta primeira rodada.

#### Critérios de Aceitação

1. WHEN uma requisição POST /api/login é feita com credenciais válidas, THE Suite_Junior SHALL verificar que o AuthController retorna status 200 com JSON contendo "user" e "token"
2. WHEN uma requisição POST /api/login é feita com credenciais inválidas, THE Suite_Junior SHALL verificar que o AuthController retorna status 401
3. WHEN uma requisição POST /api/create-account é feita com dados válidos, THE Suite_Junior SHALL verificar que o AuthController retorna status 201 com JSON contendo "user" e "token"
4. WHEN uma requisição GET /api/products é feita, THE Suite_Junior SHALL verificar que o ProductController retorna status 200 com JSON contendo a lista de produtos ativos
5. WHEN uma requisição GET /api/products/{id} é feita com um ID existente, THE Suite_Junior SHALL verificar que o ProductController retorna status 200 com os dados do produto
6. WHEN uma requisição GET /api/products/{id} é feita com um ID inexistente, THE Suite_Junior SHALL verificar que o ProductController retorna status 404
7. WHEN uma requisição POST /api/cart é feita por um usuário autenticado com dados válidos, THE Suite_Junior SHALL verificar que o CartController retorna status 200 com o item adicionado
8. WHEN uma requisição POST /api/orders é feita por um usuário autenticado com itens válidos, THE Suite_Junior SHALL verificar que o OrderController retorna status 201 com o pedido criado
9. WHEN uma requisição PATCH /api/admin/orders/{id}/status é feita por um usuário admin, THE Suite_Junior SHALL verificar que o OrderController retorna status 200 com o pedido atualizado
10. WHEN uma requisição PATCH /api/admin/orders/{id}/status é feita por um usuário não-admin, THE Suite_Junior SHALL verificar que o OrderController retorna status 403

### Requisito 4: Testes Unitários dos Models

**User Story:** Como pesquisador, eu quero testes unitários para os Models com lógica relevante, para que os atributos calculados e relacionamentos principais estejam verificados.

#### Critérios de Aceitação

1. THE Suite_Junior SHALL verificar que o atributo calculado available_stock do Product_Model retorna o estoque total menos a quantidade reservada em carrinhos
2. THE Suite_Junior SHALL verificar que o atributo calculado reserved_quantity do Product_Model retorna a soma das quantidades de CartItems associados ao produto
3. WHEN o método decreaseStock é chamado com uma quantidade, THE Suite_Junior SHALL verificar que o Product_Model decrementa o atributo stock pela quantidade informada
4. WHEN o scope active é aplicado, THE Suite_Junior SHALL verificar que o Product_Model retorna apenas produtos com active igual a true
5. THE Suite_Junior SHALL verificar que o atributo calculado total do Order_Model retorna a soma de (price multiplicado por quantity) de todos os OrderItems associados
6. THE Suite_Junior SHALL verificar que o User_Model possui os relacionamentos orders e cartItems configurados corretamente

### Requisito 5: Limitação Intencional de Cobertura

**User Story:** Como pesquisador, eu quero que a cobertura de código desta rodada seja parcial e realista, para que represente fielmente o cenário de um desenvolvedor júnior/pleno no mercado.

#### Critérios de Aceitação

1. THE Suite_Junior SHALL cobrir os fluxos principais (happy paths) dos Services e Controllers sem cobrir todos os fluxos alternativos
2. THE Suite_Junior SHALL omitir testes para o UserController (por ser um controller simples com lógica mínima)
3. THE Suite_Junior SHALL omitir testes para métodos auxiliares como CartService::clear, CartService::getItems e CartService::availableStockForUser
4. THE Suite_Junior SHALL omitir testes para cenários extremos como nomes de produto com caracteres especiais, quantidades no limite máximo de inteiros e requisições com payloads malformados
5. THE Suite_Junior SHALL omitir testes para os métodos update e destroy do ProductController (fluxos de atualização de imagem e remoção)
6. THE Suite_Junior SHALL omitir testes para o método updateItem do CartService (fluxo de atualização de quantidade no carrinho)

### Requisito 6: Estilo e Estrutura dos Testes

**User Story:** Como pesquisador, eu quero que os testes sigam um estilo simples e realista de desenvolvedor júnior/pleno, para que a comparação com testes gerados por IA seja significativa.

#### Critérios de Aceitação

1. THE Suite_Junior SHALL utilizar assertions simples do PHPUnit (assertEquals, assertTrue, assertFalse, assertNull, assertNotNull, assertCount, assertInstanceOf)
2. THE Suite_Junior SHALL utilizar mocks e stubs básicos do Mockery ou das funcionalidades nativas do Laravel (actingAs, mock) apenas quando necessário para isolar dependências
3. THE Suite_Junior SHALL utilizar factories do Laravel para criação de dados de teste, criando factories para Product, CartItem, Order e OrderItem quando não existirem
4. THE Suite_Junior SHALL utilizar o trait RefreshDatabase para garantir isolamento entre testes que acessam o banco de dados
5. THE Suite_Junior SHALL nomear os métodos de teste de forma descritiva usando o padrão test_descricao_do_comportamento (snake_case)
6. THE Suite_Junior SHALL manter cada método de teste com no máximo 25 linhas de código, seguindo o padrão Arrange-Act-Assert

### Requisito 7: Integração com Sistema de Métricas Existente

**User Story:** Como pesquisador, eu quero que a execução da Suite_Junior colete métricas automaticamente usando a infraestrutura existente, para que eu possa analisar os dados de performance e cobertura desta rodada.

#### Critérios de Aceitação

1. WHEN a Suite_Junior é executada via comando `php artisan test:metrics --filter=JuniorPleno`, THE Sistema_De_Metricas SHALL coletar métricas de tempo de execução para cada teste da suíte
2. WHEN a Suite_Junior é executada com a flag --coverage, THE Sistema_De_Metricas SHALL coletar métricas de cobertura de código para cada teste da suíte
3. WHEN a Suite_Junior completa a execução, THE Sistema_De_Metricas SHALL gerar um relatório PDF contendo: tempo total da suíte, tempo médio por teste, cobertura de linhas, cobertura de métodos e pico de memória utilizado
4. WHEN a Suite_Junior é executada com a flag --json, THE Sistema_De_Metricas SHALL exportar as métricas em formato JSON para análise comparativa posterior
5. THE Suite_Junior SHALL ser compatível com a extensão MetricsExtension já configurada no phpunit.xml, sem exigir configuração adicional para coleta de métricas

### Requisito 8: Criação de Factories Necessárias

**User Story:** Como desenvolvedor, eu quero que as factories necessárias para os testes sejam criadas, para que os testes possam gerar dados de teste de forma consistente.

#### Critérios de Aceitação

1. THE Suite_Junior SHALL incluir uma factory ProductFactory em `database/factories/ProductFactory.php` que gere produtos com name, description, price (entre 1.00 e 1000.00), stock (entre 1 e 100), image_url e active (padrão true)
2. THE Suite_Junior SHALL incluir uma factory OrderFactory em `database/factories/OrderFactory.php` que gere pedidos associados a um User com status padrão "pending"
3. THE Suite_Junior SHALL incluir uma factory OrderItemFactory em `database/factories/OrderItemFactory.php` que gere itens de pedido associados a um Order e um Product com quantity (entre 1 e 5) e price
4. THE Suite_Junior SHALL incluir uma factory CartItemFactory em `database/factories/CartItemFactory.php` que gere itens de carrinho associados a um User e um Product com quantity (entre 1 e 5)
