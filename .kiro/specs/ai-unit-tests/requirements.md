# Documento de Requisitos — Suite de Testes Unitários IA (TesteIA)

## Introdução

Este documento especifica os requisitos para a criação de uma segunda suite de testes unitários no backend PHP/Laravel, representando testes gerados por IA. A suite será armazenada na pasta `tests/TesteIA` e cobrirá todo o código relevante do backend: Models, Services, Controllers e Commands. O objetivo é atingir cobertura máxima (100%) de linhas, funções e branches, seguindo padrões modernos de testes automatizados com PHPUnit. Os resultados serão comparados com a suite existente (`JuniorPlenoTests`), criada para simular testes de um desenvolvedor júnior/pleno.

## Glossário

- **Suite_TesteIA**: Conjunto completo de testes unitários gerados por IA, armazenados em `tests/TesteIA/`.
- **Suite_JuniorPleno**: Suite de testes existente em `tests/JuniorPlenoTests/`, simulando testes de desenvolvedor júnior/pleno.
- **TestCase_Base**: Classe abstrata base para todos os testes da Suite_TesteIA, estendendo `Tests\TestCase` e fornecendo helpers comuns.
- **PHPUnit**: Framework de testes utilizado (PHPUnit 10+).
- **Cobertura**: Métrica que mede a porcentagem de linhas, funções e branches executados pelos testes.
- **Mock**: Objeto simulado que substitui dependências reais durante os testes para garantir isolamento.
- **Backend**: Aplicação Laravel composta por Models (`User`, `Product`, `Order`, `OrderItem`, `CartItem`), Services (`AuthService`, `CartService`, `OrderService`, `ProductService`), Controllers (`AuthController`, `CartController`, `OrderController`, `ProductController`, `UserController`) e Commands (`TestMetricsCommand`).
- **Métricas**: Dados coletados durante a execução dos testes: tempo de execução, cobertura de código e consumo de recursos.

## Requisitos

### Requisito 1: Estrutura e Organização da Suite TesteIA

**User Story:** Como desenvolvedor, quero que os testes de IA estejam organizados em uma pasta dedicada com estrutura clara, para que seja possível comparar diretamente com a suite JuniorPleno.

#### Critérios de Aceitação

1. THE Suite_TesteIA SHALL armazenar todos os arquivos de teste dentro do diretório `tests/TesteIA/`.
2. THE Suite_TesteIA SHALL organizar os testes em subpastas espelhando a estrutura do código-fonte: `Controllers/`, `Services/`, `Models/` e `Commands/`.
3. THE Suite_TesteIA SHALL fornecer uma classe TestCase_Base abstrata em `tests/TesteIA/TesteIATestCase.php` que estenda `Tests\TestCase`, utilize o trait `RefreshDatabase` e forneça métodos helper para criação de usuários admin e regulares.
4. WHEN a suite PHPUnit é executada com `--testsuite=TesteIA`, THE Suite_TesteIA SHALL executar exclusivamente os testes contidos em `tests/TesteIA/`.
5. THE Suite_TesteIA SHALL registrar a testsuite `TesteIA` no arquivo `phpunit.xml` apontando para o diretório `tests/TesteIA`.

### Requisito 2: Testes de Models

**User Story:** Como desenvolvedor, quero testes completos para todos os Models, para que relacionamentos, scopes, accessors, casts e métodos de negócio sejam validados.

#### Critérios de Aceitação

1. THE Suite_TesteIA SHALL testar todos os relacionamentos definidos nos Models: `User.orders`, `User.cartItems`, `Product.cartItems`, `Product.orderItems`, `Order.user`, `Order.items`, `OrderItem.order`, `OrderItem.product`, `CartItem.user`, `CartItem.product`.
2. THE Suite_TesteIA SHALL testar os atributos `fillable` de cada Model, verificando que mass assignment funciona corretamente para os campos permitidos.
3. THE Suite_TesteIA SHALL testar os casts definidos: `User.password` como `hashed`, `User.is_admin` como `boolean`, `Product.price` como `decimal:2`, `Product.stock` como `integer`, `Product.active` como `boolean`, `OrderItem.price` como `decimal:2`, `OrderItem.quantity` como `integer`, `CartItem.quantity` como `integer`.
4. THE Suite_TesteIA SHALL testar o scope `Product::scopeActive` verificando que retorna apenas produtos com `active = true`.
5. THE Suite_TesteIA SHALL testar o accessor `Product.reserved_quantity` verificando que retorna a soma das quantidades de CartItems associados ao produto.
6. THE Suite_TesteIA SHALL testar o accessor `Product.available_stock` verificando que retorna `max(0, stock - reserved_quantity)`.
7. THE Suite_TesteIA SHALL testar o método `Product.decreaseStock` verificando que decrementa o estoque pela quantidade informada.
8. THE Suite_TesteIA SHALL testar o accessor `Order.total` verificando que retorna a soma de `price * quantity` de todos os OrderItems do pedido.
9. THE Suite_TesteIA SHALL testar os atributos `hidden` do Model User (`password`, `remember_token`), verificando que não aparecem na serialização.

### Requisito 3: Testes de AuthService

**User Story:** Como desenvolvedor, quero testes completos para o AuthService, para que login e criação de conta sejam validados em todos os cenários.

#### Critérios de Aceitação

1. WHEN credenciais válidas são fornecidas, THE Suite_TesteIA SHALL verificar que `AuthService.login` retorna um array contendo o usuário e um token válido.
2. WHEN o email não existe no banco, THE Suite_TesteIA SHALL verificar que `AuthService.login` retorna `null`.
3. WHEN a senha está incorreta, THE Suite_TesteIA SHALL verificar que `AuthService.login` retorna `null`.
4. WHEN dados válidos são fornecidos, THE Suite_TesteIA SHALL verificar que `AuthService.create` cria um usuário no banco e retorna um array com o usuário e um token.
5. WHEN dados com campos opcionais (`username`, `phone`) ausentes são fornecidos, THE Suite_TesteIA SHALL verificar que `AuthService.create` cria o usuário com esses campos como `null`.
6. THE Suite_TesteIA SHALL verificar que a senha do usuário criado por `AuthService.create` é armazenada como hash e não em texto plano.

### Requisito 4: Testes de CartService

**User Story:** Como desenvolvedor, quero testes completos para o CartService, para que todas as operações de carrinho sejam validadas incluindo cenários de estoque e concorrência.

#### Critérios de Aceitação

1. THE Suite_TesteIA SHALL testar `CartService.availableStockForUser` verificando que subtrai do estoque total apenas as quantidades reservadas por outros usuários.
2. THE Suite_TesteIA SHALL testar `CartService.getItems` verificando que retorna os itens do carrinho do usuário com dados do produto e estoque disponível calculado.
3. WHEN um produto ativo com estoque disponível é adicionado, THE Suite_TesteIA SHALL verificar que `CartService.addItem` cria um novo CartItem com a quantidade correta.
4. WHEN o produto já existe no carrinho, THE Suite_TesteIA SHALL verificar que `CartService.addItem` incrementa a quantidade do item existente.
5. WHEN o produto está inativo, THE Suite_TesteIA SHALL verificar que `CartService.addItem` lança `ValidationException` com mensagem sobre produto indisponível.
6. WHEN o estoque disponível para o usuário é zero, THE Suite_TesteIA SHALL verificar que `CartService.addItem` lança `ValidationException` com mensagem sobre estoque indisponível.
7. WHEN a quantidade solicitada excede o estoque disponível, THE Suite_TesteIA SHALL verificar que `CartService.addItem` lança `ValidationException` com mensagem sobre estoque insuficiente.
8. WHEN uma quantidade válida é fornecida, THE Suite_TesteIA SHALL verificar que `CartService.updateItem` atualiza a quantidade do item do carrinho.
9. WHEN a quantidade excede o estoque disponível, THE Suite_TesteIA SHALL verificar que `CartService.updateItem` lança `ValidationException`.
10. THE Suite_TesteIA SHALL verificar que `CartService.updateItem` aplica `max(1, quantity)` para garantir quantidade mínima de 1.
11. THE Suite_TesteIA SHALL verificar que `CartService.removeItem` remove o item do carrinho do usuário.
12. THE Suite_TesteIA SHALL verificar que `CartService.clear` remove todos os itens do carrinho do usuário.

### Requisito 5: Testes de OrderService

**User Story:** Como desenvolvedor, quero testes completos para o OrderService, para que criação de pedidos, listagem e atualização de status sejam validados.

#### Critérios de Aceitação

1. WHEN itens válidos são fornecidos, THE Suite_TesteIA SHALL verificar que `OrderService.createFromCart` cria um pedido com status `pending`, cria os OrderItems com preço e quantidade corretos, decrementa o estoque dos produtos e limpa o carrinho do usuário.
2. WHEN um produto nos itens está inativo, THE Suite_TesteIA SHALL verificar que `OrderService.createFromCart` lança `ValidationException` com mensagem sobre produto indisponível.
3. WHEN o estoque de um produto é insuficiente para a quantidade solicitada, THE Suite_TesteIA SHALL verificar que `OrderService.createFromCart` lança `ValidationException` com mensagem sobre estoque insuficiente.
4. THE Suite_TesteIA SHALL verificar que `OrderService.listForUser` retorna apenas os pedidos do usuário informado, com items e produtos carregados, ordenados do mais recente para o mais antigo.
5. THE Suite_TesteIA SHALL verificar que `OrderService.listAll` retorna todos os pedidos com items, produtos e dados do usuário carregados, ordenados do mais recente para o mais antigo.
6. WHEN um status válido é fornecido, THE Suite_TesteIA SHALL verificar que `OrderService.updateStatus` atualiza o status do pedido e retorna o pedido com relacionamentos carregados.

### Requisito 6: Testes de ProductService

**User Story:** Como desenvolvedor, quero testes completos para o ProductService, para que CRUD de produtos e controle de autorização sejam validados.

#### Critérios de Aceitação

1. THE Suite_TesteIA SHALL verificar que `ProductService.list` retorna apenas produtos ativos.
2. THE Suite_TesteIA SHALL verificar que `ProductService.find` retorna o produto quando existe e `null` quando não existe.
3. WHEN um usuário admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.create` cria o produto com sucesso.
4. WHEN um usuário não-admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.create` lança `AuthorizationException`.
5. WHEN nenhum actor é fornecido, THE Suite_TesteIA SHALL verificar que `ProductService.create` cria o produto com sucesso.
6. WHEN um usuário admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.update` atualiza o produto com sucesso.
7. WHEN um usuário não-admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.update` lança `AuthorizationException`.
8. WHEN um usuário admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.delete` remove o produto com sucesso.
9. WHEN um usuário não-admin é fornecido como actor, THE Suite_TesteIA SHALL verificar que `ProductService.delete` lança `AuthorizationException`.

### Requisito 7: Testes de AuthController

**User Story:** Como desenvolvedor, quero testes completos para o AuthController, para que os endpoints de autenticação sejam validados incluindo validação de request e respostas HTTP.

#### Critérios de Aceitação

1. WHEN uma requisição POST `/api/login` com credenciais válidas é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com o usuário e token.
2. WHEN uma requisição POST `/api/login` com credenciais inválidas é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 401 com mensagem de erro.
3. WHEN uma requisição POST `/api/login` com dados faltando é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com erros de validação.
4. WHEN uma requisição POST `/api/create-account` com dados válidos é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 201 com o usuário e token.
5. WHEN uma requisição POST `/api/create-account` com email duplicado é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com erro de validação.
6. WHEN uma requisição POST `/api/create-account` com username duplicado é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com erro de validação.
7. WHEN uma requisição POST `/api/create-account` com senha sem confirmação é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com erro de validação.
8. WHEN um usuário autenticado faz POST `/api/logout`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 e o token é invalidado.

### Requisito 8: Testes de CartController

**User Story:** Como desenvolvedor, quero testes completos para o CartController, para que os endpoints do carrinho sejam validados com autenticação e tratamento de erros.

#### Critérios de Aceitação

1. WHEN um usuário autenticado faz GET `/api/cart`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com os itens do carrinho.
2. WHEN um usuário não autenticado faz GET `/api/cart`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 401.
3. WHEN um usuário autenticado faz POST `/api/cart` com dados válidos, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com o item adicionado.
4. WHEN um usuário autenticado faz POST `/api/cart` com `product_id` inexistente, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422.
5. WHEN um usuário autenticado faz POST `/api/cart` e ocorre erro de estoque, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com mensagem de erro.
6. WHEN um usuário autenticado faz PUT `/api/cart/{id}` com quantidade válida, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com o item atualizado.
7. WHEN um usuário autenticado faz PUT `/api/cart/{id}` com quantidade excedendo estoque, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422.
8. WHEN um usuário autenticado faz DELETE `/api/cart/{id}`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 e o item é removido.

### Requisito 9: Testes de OrderController

**User Story:** Como desenvolvedor, quero testes completos para o OrderController, para que criação de pedidos, listagem e operações admin sejam validadas.

#### Critérios de Aceitação

1. WHEN um usuário autenticado faz POST `/api/orders` com itens válidos, THE Suite_TesteIA SHALL verificar que a resposta retorna status 201 com o pedido criado.
2. WHEN um usuário autenticado faz POST `/api/orders` com dados inválidos, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422.
3. WHEN um usuário autenticado faz POST `/api/orders` e ocorre erro de estoque, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com mensagem de erro.
4. WHEN um usuário autenticado faz GET `/api/orders`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com os pedidos do usuário.
5. WHEN um usuário admin faz GET `/api/admin/orders`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com todos os pedidos.
6. WHEN um usuário não-admin faz GET `/api/admin/orders`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 403.
7. WHEN um usuário admin faz PATCH `/api/admin/orders/{id}/status` com status válido, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com o pedido atualizado.
8. WHEN um usuário não-admin faz PATCH `/api/admin/orders/{id}/status`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 403.
9. WHEN um usuário admin faz PATCH `/api/admin/orders/{id}/status` com status inválido, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422.

### Requisito 10: Testes de ProductController

**User Story:** Como desenvolvedor, quero testes completos para o ProductController, para que CRUD de produtos via API seja validado incluindo upload de imagem e autorização.

#### Critérios de Aceitação

1. WHEN uma requisição GET `/api/products` é feita, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com a lista de produtos ativos incluindo `available_stock`.
2. WHEN uma requisição GET `/api/products/{id}` é feita com ID existente, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com os dados do produto incluindo `available_stock`.
3. WHEN uma requisição GET `/api/products/{id}` é feita com ID inexistente, THE Suite_TesteIA SHALL verificar que a resposta retorna status 404.
4. WHEN um admin faz POST `/api/products` com dados válidos e imagem, THE Suite_TesteIA SHALL verificar que a resposta retorna status 201 com o produto criado e `image_url` preenchido.
5. WHEN um usuário não-admin faz POST `/api/products`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 403.
6. WHEN um admin faz POST `/api/products/{id}` com dados válidos, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com o produto atualizado.
7. WHEN um admin faz POST `/api/products/{id}` com nova imagem, THE Suite_TesteIA SHALL verificar que a imagem antiga é removida do storage e a nova é salva.
8. WHEN um admin faz DELETE `/api/products/{id}`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200, o produto é removido e a imagem é deletada do storage.
9. WHEN um usuário não-admin faz DELETE `/api/products/{id}`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 403.
10. WHEN uma requisição POST `/api/products` é feita com dados inválidos (nome curto, preço negativo, sem imagem), THE Suite_TesteIA SHALL verificar que a resposta retorna status 422 com erros de validação.

### Requisito 11: Testes de UserController

**User Story:** Como desenvolvedor, quero testes completos para o UserController, para que visualização e atualização de perfil sejam validadas.

#### Critérios de Aceitação

1. WHEN um usuário autenticado faz GET `/api/user`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com os dados do usuário autenticado.
2. WHEN um usuário não autenticado faz GET `/api/user`, THE Suite_TesteIA SHALL verificar que a resposta retorna status 401.
3. WHEN um usuário autenticado faz PUT `/api/user` com dados válidos, THE Suite_TesteIA SHALL verificar que a resposta retorna status 200 com os dados atualizados.
4. WHEN um usuário autenticado faz PUT `/api/user` com nome menor que 3 caracteres, THE Suite_TesteIA SHALL verificar que a resposta retorna status 422.

### Requisito 12: Qualidade e Padrões dos Testes

**User Story:** Como desenvolvedor, quero que os testes sigam padrões de alta qualidade, para que representem o nível esperado de testes gerados por IA.

#### Critérios de Aceitação

1. THE Suite*TesteIA SHALL utilizar nomes de teste descritivos seguindo o padrão `test*<ação>_<cenário>_<resultado_esperado>`ou o equivalente com anotação`#[Test]`.
2. THE Suite_TesteIA SHALL garantir isolamento completo entre testes utilizando `RefreshDatabase` e mocks quando apropriado.
3. THE Suite_TesteIA SHALL utilizar Factories do Laravel para criação de dados de teste.
4. THE Suite_TesteIA SHALL utilizar mocks, stubs ou fakes do Laravel (Storage::fake, etc.) para isolar dependências externas.
5. THE Suite_TesteIA SHALL organizar cada teste seguindo o padrão Arrange-Act-Assert com separação clara entre as seções.
6. THE Suite_TesteIA SHALL evitar testes com tempo de execução excessivo, priorizando operações leves e evitando operações custosas desnecessárias.

### Requisito 13: Cobertura de Código

**User Story:** Como desenvolvedor, quero cobertura máxima de código, para que a comparação com a suite JuniorPleno seja significativa.

#### Critérios de Aceitação

1. THE Suite_TesteIA SHALL cobrir todas as linhas executáveis dos arquivos em `app/Models/`, `app/Services/` e `app/Http/Controllers/`.
2. THE Suite_TesteIA SHALL cobrir todos os branches condicionais (if/else, ternários, null coalescing) presentes nos Services e Controllers.
3. THE Suite_TesteIA SHALL cobrir todos os métodos públicos de cada classe no escopo definido.
4. THE Suite_TesteIA SHALL incluir testes para cenários de edge case: valores limítrofes (quantidade 0, estoque 0), strings vazias, e campos opcionais nulos.

### Requisito 14: Métricas e Integração com Sistema de Métricas

**User Story:** Como desenvolvedor, quero que a suite TesteIA seja compatível com o sistema de métricas existente, para que os relatórios de comparação possam ser gerados.

#### Critérios de Aceitação

1. WHEN o comando `php artisan test:metrics --testsuite=TesteIA` é executado, THE Suite_TesteIA SHALL ser executada e as métricas de tempo de execução, cobertura e consumo de recursos coletadas pela extensão MetricsExtension.
2. THE Suite_TesteIA SHALL ser compatível com a extensão `MetricsExtension` já configurada no `phpunit.xml`, sem necessidade de configuração adicional.
