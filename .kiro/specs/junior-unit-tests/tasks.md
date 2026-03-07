# Implementation Plan: Junior/Pleno Unit Tests

## Overview

Implementação incremental da suíte de testes unitários simulando um desenvolvedor júnior/pleno. O plano segue a ordem: infraestrutura (factories, classe base, phpunit.xml) → testes de Models → testes de Services → testes de Controllers, garantindo que cada etapa construa sobre a anterior.

## Tasks

- [x]   1. Criar factories necessárias para os testes
    - [x] 1.1 Criar `database/factories/ProductFactory.php`
        - Gerar produtos com name, description, price (1.00-1000.00), stock (1-100), image_url e active (padrão true)
        - _Requirements: 8.1_
    - [x] 1.2 Criar `database/factories/OrderFactory.php`
        - Gerar pedidos associados a um User com status padrão "pending"
        - _Requirements: 8.2_
    - [x] 1.3 Criar `database/factories/OrderItemFactory.php`
        - Gerar itens de pedido associados a Order e Product com quantity (1-5) e price
        - _Requirements: 8.3_
    - [x] 1.4 Criar `database/factories/CartItemFactory.php`
        - Gerar itens de carrinho associados a User e Product com quantity (1-5)
        - _Requirements: 8.4_

- [x]   2. Configurar estrutura da suíte e classe base
    - [x] 2.1 Criar a classe base `tests/JuniorPlenoTests/JuniorPlenoTestCase.php`
        - Estender `Tests\TestCase`, incluir trait `RefreshDatabase`
        - Implementar helpers `createAdminUser()` e `createRegularUser()`
        - _Requirements: 1.1, 1.3, 1.4, 6.4_
    - [x] 2.2 Registrar testsuite "JuniorPleno" no `phpunit.xml`
        - Adicionar bloco `<testsuite name="JuniorPleno"><directory>tests/JuniorPlenoTests</directory></testsuite>`
        - _Requirements: 1.2, 7.5_

- [x]   3. Checkpoint - Verificar infraestrutura
    - Ensure all tests pass, ask the user if questions arise.

- [x]   4. Implementar testes dos Models
    - [x] 4.1 Criar `tests/JuniorPlenoTests/Models/ProductModelTest.php`
        - Implementar `test_available_stock_returns_stock_minus_reserved`: verificar que available_stock retorna stock menos reserved_quantity
        - Implementar `test_reserved_quantity_returns_sum_of_cart_items`: verificar soma das quantidades de CartItems
        - Implementar `test_decrease_stock_decrements_stock`: verificar que decreaseStock decrementa o atributo stock
        - Implementar `test_scope_active_returns_only_active_products`: verificar que scope active filtra apenas produtos ativos
        - _Requirements: 4.1, 4.2, 4.3, 4.4_
    - [ ]\* 4.2 Write property test for Product stock computation
        - **Property 12: Product stock computation invariant**
        - Para qualquer produto com CartItems associados, reserved_quantity == soma das quantidades dos cart items, available_stock == max(0, stock - reserved_quantity), e decreaseStock(n) decrementa stock em exatamente n
        - **Validates: Requirements 4.1, 4.2, 4.3**
    - [x] 4.3 Criar `tests/JuniorPlenoTests/Models/OrderModelTest.php`
        - Implementar `test_total_returns_sum_of_price_times_quantity`: verificar que total retorna soma de (price × quantity) dos OrderItems
        - _Requirements: 4.5_
    - [ ]\* 4.4 Write property test for Order total computation
        - **Property 13: Order total computation**
        - Para qualquer order com OrderItems, total == SUM(price × quantity) de cada item
        - **Validates: Requirements 4.5**
    - [x] 4.5 Criar `tests/JuniorPlenoTests/Models/UserModelTest.php`
        - Implementar `test_user_has_orders_relationship`: verificar relacionamento orders
        - Implementar `test_user_has_cart_items_relationship`: verificar relacionamento cartItems
        - _Requirements: 4.6_
    - [ ]\* 4.6 Write property test for User model relationships
        - **Property 14: User model relationships**
        - Para qualquer user com orders e cart items associados, user->orders retorna todos os pedidos do user e user->cartItems retorna todos os cart items do user
        - **Validates: Requirements 4.6**

- [x]   5. Checkpoint - Verificar testes dos Models
    - Ensure all tests pass, ask the user if questions arise.

- [x]   6. Implementar testes dos Services
    - [x] 6.1 Criar `tests/JuniorPlenoTests/Services/AuthServiceTest.php`
        - Implementar `test_login_returns_user_and_token_with_valid_credentials`: login válido retorna array com "user" e "token"
        - Implementar `test_login_returns_null_with_invalid_email`: email inexistente retorna null
        - Implementar `test_login_returns_null_with_wrong_password`: senha incorreta retorna null
        - Implementar `test_create_returns_user_and_token_and_persists`: criação de conta persiste user e retorna array com "user" e "token"
        - _Requirements: 2.1, 2.2, 2.3_
    - [ ]\* 6.2 Write property test for valid login
        - **Property 1: Valid login returns user and token**
        - Para qualquer user persistido com senha conhecida, AuthService::login com credenciais corretas retorna array com "user" e "token"
        - **Validates: Requirements 2.1**
    - [ ]\* 6.3 Write property test for invalid credentials
        - **Property 2: Invalid credentials return null**
        - Para qualquer combinação email/password onde email não existe ou password não confere, AuthService::login retorna null
        - **Validates: Requirements 2.2**
    - [ ]\* 6.4 Write property test for account creation round trip
        - **Property 3: Account creation round trip**
        - Para qualquer dado válido de user, AuthService::create retorna array com "user" e "token" e o user é encontrado no banco
        - **Validates: Requirements 2.3**
    - [x] 6.5 Criar `tests/JuniorPlenoTests/Services/ProductServiceTest.php`
        - Implementar `test_create_product_with_admin_user`: admin cria produto com sucesso
        - Implementar `test_create_product_without_admin_throws_exception`: não-admin lança AuthorizationException
        - Implementar `test_list_returns_only_active_products`: listagem retorna apenas produtos ativos
        - _Requirements: 2.4, 2.5, 2.6_
    - [ ]\* 6.6 Write property test for admin authorization
        - **Property 4: Admin authorization for product creation**
        - Para qualquer user não-admin, ProductService::create lança AuthorizationException; para admin, retorna Product persistido
        - **Validates: Requirements 2.4, 2.5**
    - [ ]\* 6.7 Write property test for active products filter
        - **Property 5: Active products filter invariant**
        - Para qualquer conjunto de produtos, ProductService::list() retorna apenas produtos com active=true
        - **Validates: Requirements 2.6**
    - [x] 6.8 Criar `tests/JuniorPlenoTests/Services/CartServiceTest.php`
        - Implementar `test_add_item_creates_cart_item_for_active_product`: produto ativo com estoque cria CartItem
        - Implementar `test_add_item_throws_exception_for_inactive_product`: produto inativo lança ValidationException
        - Implementar `test_add_item_throws_exception_when_stock_exceeded`: estoque insuficiente lança ValidationException
        - _Requirements: 2.7, 2.8, 2.9_
    - [ ]\* 6.9 Write property test for cart item creation with stock validation
        - **Property 6: Cart item creation with stock validation**
        - Para qualquer produto ativo com estoque, addItem cria CartItem; para inativo ou estoque insuficiente, lança ValidationException
        - **Validates: Requirements 2.7, 2.8, 2.9**
    - [x] 6.10 Criar `tests/JuniorPlenoTests/Services/OrderServiceTest.php`
        - Implementar `test_create_from_cart_creates_order_with_pending_status`: pedido válido cria Order com status "pending", OrderItems e decrementa estoque
        - Implementar `test_create_from_cart_throws_exception_for_insufficient_stock`: estoque insuficiente lança ValidationException
        - _Requirements: 2.10, 2.11_
    - [ ]\* 6.11 Write property test for order creation with stock decrement
        - **Property 7: Order creation with stock decrement**
        - Para itens válidos com estoque, createFromCart cria Order "pending" e decrementa stock; para estoque insuficiente, lança ValidationException
        - **Validates: Requirements 2.10, 2.11**

- [x]   7. Checkpoint - Verificar testes dos Services
    - Ensure all tests pass, ask the user if questions arise.

- [x]   8. Implementar testes dos Controllers
    - [x] 8.1 Criar `tests/JuniorPlenoTests/Controllers/AuthControllerTest.php`
        - Implementar `test_login_with_valid_credentials_returns_200`: POST /api/login com credenciais válidas retorna 200 com "user" e "token"
        - Implementar `test_login_with_invalid_credentials_returns_401`: POST /api/login com credenciais inválidas retorna 401
        - Implementar `test_create_account_with_valid_data_returns_201`: POST /api/create-account com dados válidos retorna 201 com "user" e "token"
        - _Requirements: 3.1, 3.2, 3.3_
    - [ ]\* 8.2 Write property test for HTTP authentication responses
        - **Property 8: HTTP authentication responses**
        - Para credenciais válidas, POST /api/login retorna 200 com "user" e "token"; para inválidas, retorna 401; POST /api/create-account com dados válidos retorna 201
        - **Validates: Requirements 3.1, 3.2, 3.3**
    - [x] 8.3 Criar `tests/JuniorPlenoTests/Controllers/ProductControllerTest.php`
        - Implementar `test_index_returns_200_with_active_products`: GET /api/products retorna 200 com lista de produtos ativos
        - Implementar `test_show_returns_200_for_existing_product`: GET /api/products/{id} existente retorna 200
        - Implementar `test_show_returns_404_for_nonexistent_product`: GET /api/products/{id} inexistente retorna 404
        - _Requirements: 3.4, 3.5, 3.6_
    - [ ]\* 8.4 Write property test for product endpoint responses
        - **Property 9: Product endpoint responses**
        - Para qualquer produto existente, GET /api/products/{id} retorna 200; para ID inexistente, retorna 404
        - **Validates: Requirements 3.5, 3.6**
    - [x] 8.5 Criar `tests/JuniorPlenoTests/Controllers/CartControllerTest.php`
        - Implementar `test_store_returns_200_for_authenticated_user`: POST /api/cart autenticado com dados válidos retorna 200
        - _Requirements: 3.7_
    - [x] 8.6 Criar `tests/JuniorPlenoTests/Controllers/OrderControllerTest.php`
        - Implementar `test_store_returns_201_for_valid_order`: POST /api/orders autenticado retorna 201 com pedido criado
        - Implementar `test_update_status_returns_200_for_admin`: PATCH /api/admin/orders/{id}/status por admin retorna 200
        - Implementar `test_update_status_returns_403_for_non_admin`: PATCH /api/admin/orders/{id}/status por não-admin retorna 403
        - _Requirements: 3.8, 3.9, 3.10_
    - [ ]\* 8.7 Write property test for cart and order endpoint responses
        - **Property 10: Cart and order endpoint responses**
        - Para user autenticado com dados válidos, POST /api/cart retorna 200 e POST /api/orders retorna 201
        - **Validates: Requirements 3.7, 3.8**
    - [ ]\* 8.8 Write property test for admin authorization at HTTP level
        - **Property 11: Admin authorization at HTTP level**
        - Para admin, PATCH /api/admin/orders/{id}/status retorna 200; para não-admin, retorna 403
        - **Validates: Requirements 3.9, 3.10**

- [x]   9. Checkpoint - Verificar testes dos Controllers
    - Ensure all tests pass, ask the user if questions arise.

- [x]   10. Validação final e integração com métricas
    - [x] 10.1 Verificar que a suíte completa executa via `php artisan test --testsuite=JuniorPleno`
        - Confirmar que todos os ~28 métodos de teste passam
        - Confirmar que a estrutura de pastas Controllers/, Services/, Models/ está correta
        - _Requirements: 1.1, 1.2, 1.3, 7.1, 7.5_
    - [ ]\* 10.2 Write property test for factory data generation
        - **Property 15: Factory data generation within constraints**
        - Para qualquer instância gerada pelas factories, verificar que os valores estão dentro dos ranges definidos (price 1.00-1000.00, stock 1-100, quantity 1-5, active=true, status="pending")
        - **Validates: Requirements 8.1, 8.2, 8.3, 8.4**

- [x]   11. Final checkpoint - Ensure all tests pass
    - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marcadas com `*` são opcionais e podem ser puladas para um MVP mais rápido
- Cada task referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Property tests validam propriedades universais de corretude
- Testes unitários validam exemplos específicos e edge cases
- A cobertura parcial é intencional (Requisito 5): UserController, métodos auxiliares do CartService, update/destroy do ProductController e cenários extremos são omitidos deliberadamente
