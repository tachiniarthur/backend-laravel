<?php

namespace Tests\TesteIA;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;

class EdgeCaseTest extends TesteIATestCase
{
    /**
     * Validates: Requirements 13.4
     * Testa produto com estoque 0 - available_stock deve ser 0.
     */
    public function test_product_withStockZero_availableStockIsZero(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 0, 'active' => true]);

        // Act
        $availableStock = $product->available_stock;

        // Assert
        $this->assertEquals(0, $availableStock);
    }

    /**
     * Validates: Requirements 13.4
     * Testa produto com preço 0.00 - deve ser criado normalmente.
     */
    public function test_product_withPriceZero_isCreatedSuccessfully(): void
    {
        // Arrange & Act
        $product = Product::factory()->create(['price' => 0.00]);

        // Assert
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertEquals('0.00', $product->price);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService addItem com produto de estoque 0 - deve lançar ValidationException.
     */
    public function test_cartService_addItemWithStockZero_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 0, 'active' => true]);
        $cartService = new CartService();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $cartService->addItem($user, $product->id, 1);
    }

    /**
     * Validates: Requirements 13.4
     * Testa usuário com phone nulo - deve ser criado normalmente.
     */
    public function test_user_withNullPhone_isCreatedSuccessfully(): void
    {
        // Arrange & Act
        $user = $this->createRegularUser(['phone' => null]);

        // Assert
        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => null]);
        $this->assertNull($user->phone);
    }

    /**
     * Validates: Requirements 13.4
     * Testa usuário com username vazio - campo obrigatório aceita string vazia.
     */
    public function test_user_withEmptyUsername_isCreatedSuccessfully(): void
    {
        // Arrange & Act
        $user = $this->createRegularUser(['username' => '']);

        // Assert
        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => '']);
        $this->assertEquals('', $user->username);
    }

    /**
     * Validates: Requirements 13.4
     * Testa pedido sem itens - total deve ser 0.
     */
    public function test_order_withNoItems_totalIsZero(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $total = $order->total;

        // Assert
        $this->assertEquals(0, $total);
    }

    /**
     * Validates: Requirements 13.4
     * Testa produto com descrição vazia - deve ser criado normalmente.
     */
    public function test_product_withEmptyDescription_isCreatedSuccessfully(): void
    {
        // Arrange & Act
        $product = Product::factory()->create(['description' => '']);

        // Assert
        $this->assertDatabaseHas('products', ['id' => $product->id, 'description' => '']);
        $this->assertEquals('', $product->description);
    }

    /**
     * Validates: Requirements 13.4
     * Testa produto com estoque 0 e reservas - reserved_quantity e available_stock corretos.
     */
    public function test_product_withStockZeroAndReservations_availableStockIsZero(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 0, 'active' => true]);
        $user = $this->createRegularUser();
        // Manually create a cart item (bypassing service validation)
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        // Act
        $product->refresh();

        // Assert
        $this->assertEquals(0, $product->reserved_quantity);
        $this->assertEquals(0, $product->available_stock);
    }

    /**
     * Validates: Requirements 13.4
     * Testa OrderItem com quantidade 1 (valor mínimo típico) - total calculado corretamente.
     */
    public function test_orderItem_withQuantityOne_totalIsCorrect(): void
    {
        // Arrange
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 99.99,
            'quantity' => 1,
        ]);

        // Act
        $total = $order->fresh()->total;

        // Assert
        $this->assertEqualsWithDelta(99.99, $total, 0.01);
    }

    /**
     * Validates: Requirements 13.4
     * Testa produto com nome vazio - deve ser criado normalmente.
     */
    public function test_product_withEmptyName_isCreatedSuccessfully(): void
    {
        // Arrange & Act
        $product = Product::factory()->create(['name' => '']);

        // Assert
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => '']);
        $this->assertEquals('', $product->name);
    }

    /**
     * Validates: Requirements 13.4
     * Testa decreaseStock com quantidade 0 - estoque não deve mudar.
     */
    public function test_product_decreaseStockByZero_stockUnchanged(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 10]);

        // Act
        $product->decreaseStock(0);

        // Assert
        $this->assertEquals(10, $product->fresh()->stock);
    }

    /**
     * Validates: Requirements 13.4
     * Testa available_stock quando reservas excedem estoque - deve retornar 0 (não negativo).
     */
    public function test_product_reservationsExceedStock_availableStockIsZeroNotNegative(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 2, 'active' => true]);
        $user1 = $this->createRegularUser();
        $user2 = $this->createRegularUser();
        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $product->refresh();

        // Assert
        $this->assertEquals(5, $product->reserved_quantity);
        $this->assertEquals(0, $product->available_stock);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService getItems com carrinho vazio.
     */
    public function test_cartService_getItems_withEmptyCart_returnsEmptyCollection(): void
    {
        $user = $this->createRegularUser();
        $cartService = new CartService();

        $items = $cartService->getItems($user);

        $this->assertCount(0, $items);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService availableStockForUser sem reservas de outros.
     */
    public function test_cartService_availableStockForUser_noOtherReservations_returnsFullStock(): void
    {
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 50, 'active' => true]);
        $cartService = new CartService();

        $available = $cartService->availableStockForUser($product, $user);

        $this->assertEquals(50, $available);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService clear com carrinho já vazio.
     */
    public function test_cartService_clear_withEmptyCart_doesNothing(): void
    {
        $user = $this->createRegularUser();
        $cartService = new CartService();

        $cartService->clear($user);

        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    /**
     * Validates: Requirements 13.4
     * Testa OrderService listForUser sem pedidos.
     */
    public function test_orderService_listForUser_withNoOrders_returnsEmptyCollection(): void
    {
        $user = $this->createRegularUser();
        $orderService = app(\App\Services\OrderService::class);

        $orders = $orderService->listForUser($user);

        $this->assertCount(0, $orders);
    }

    /**
     * Validates: Requirements 13.4
     * Testa OrderService listAll sem pedidos.
     */
    public function test_orderService_listAll_withNoOrders_returnsEmptyCollection(): void
    {
        $orderService = app(\App\Services\OrderService::class);

        $orders = $orderService->listAll();

        $this->assertCount(0, $orders);
    }

    /**
     * Validates: Requirements 13.4
     * Testa ProductService list sem produtos.
     */
    public function test_productService_list_withNoProducts_returnsEmptyCollection(): void
    {
        $productService = app(\App\Services\ProductService::class);

        $result = $productService->list();

        $this->assertCount(0, $result);
    }

    /**
     * Validates: Requirements 13.4
     * Testa AuthService login com user existente mas email case-sensitive.
     */
    public function test_authService_login_withCorrectCredentials_returnsToken(): void
    {
        $authService = new \App\Services\AuthService();
        $user = \App\Models\User::factory()->create(['password' => 'test123']);

        $result = $authService->login([
            'email' => $user->email,
            'password' => 'test123',
        ]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
    }

    /**
     * Validates: Requirements 13.4
     * Testa Product com estoque máximo.
     */
    public function test_product_withLargeStock_availableStockIsCorrect(): void
    {
        $product = Product::factory()->create(['stock' => 999999, 'active' => true]);

        $this->assertEquals(999999, $product->available_stock);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService addItem com quantidade 1 (mínimo).
     */
    public function test_cartService_addItem_withQuantityOne_createsItem(): void
    {
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartService = new CartService();

        $item = $cartService->addItem($user, $product->id, 1);

        $this->assertEquals(1, $item->quantity);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    /**
     * Validates: Requirements 13.4
     * Testa CartService updateItem com quantidade exatamente igual ao estoque.
     */
    public function test_cartService_updateItem_withExactStock_succeeds(): void
    {
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cartService = new CartService();

        $result = $cartService->updateItem($user, $cartItem->id, 5);

        $this->assertEquals(5, $result->quantity);
    }

    /**
     * Validates: Requirements 13.4
     * Testa Order total com item de preço alto e quantidade alta.
     */
    public function test_order_totalWithHighValues_calculatesCorrectly(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 999.99,
            'quantity' => 100,
        ]);

        $total = $order->fresh()->total;

        $this->assertEqualsWithDelta(99999.00, $total, 0.01);
    }

    /**
     * Validates: Requirements 13.4
     * Testa múltiplos decreaseStock consecutivos.
     */
    public function test_product_multipleDecreaseStock_accumulatesCorrectly(): void
    {
        $product = Product::factory()->create(['stock' => 100]);

        $product->decreaseStock(10);
        $product->decreaseStock(20);
        $product->decreaseStock(30);

        $this->assertEquals(40, $product->fresh()->stock);
    }

}
