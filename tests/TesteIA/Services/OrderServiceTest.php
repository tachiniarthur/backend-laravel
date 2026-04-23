<?php

namespace Tests\TesteIA\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;
use Tests\TesteIA\TesteIATestCase;

class OrderServiceTest extends TesteIATestCase
{
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    // ── createFromCart ─────────────────────────────────────────────

    public function test_createFromCart_withValidItems_createsOrderWithPendingStatus(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product1 = Product::factory()->create(['stock' => 20, 'active' => true, 'price' => 10.50]);
        $product2 = Product::factory()->create(['stock' => 15, 'active' => true, 'price' => 25.00]);

        // Add items to cart so clear() has something to remove
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 3]);

        $items = [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ];

        // Act
        $order = $this->orderService->createFromCart($user, $items);

        // Assert — order created with pending status
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_createFromCart_withValidItems_createsCorrectOrderItems(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product1 = Product::factory()->create(['stock' => 20, 'active' => true, 'price' => 10.50]);
        $product2 = Product::factory()->create(['stock' => 15, 'active' => true, 'price' => 25.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 3]);

        $items = [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ];

        // Act
        $order = $this->orderService->createFromCart($user, $items);

        // Assert — correct OrderItems created
        $this->assertCount(2, $order->items);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 10.50,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'price' => 25.00,
        ]);
    }

    public function test_createFromCart_withValidItems_decrementsStock(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product1 = Product::factory()->create(['stock' => 20, 'active' => true, 'price' => 10.50]);
        $product2 = Product::factory()->create(['stock' => 15, 'active' => true, 'price' => 25.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 3]);

        $items = [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ];

        // Act
        $this->orderService->createFromCart($user, $items);

        // Assert — stock decremented
        $this->assertEquals(18, $product1->fresh()->stock);
        $this->assertEquals(12, $product2->fresh()->stock);
    }

    public function test_createFromCart_withValidItems_clearsCart(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 20, 'active' => true, 'price' => 10.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 5],
        ];

        // Act
        $this->orderService->createFromCart($user, $items);

        // Assert — cart cleared
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    public function test_createFromCart_withValidItems_returnsOrderWithLoadedRelationships(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true, 'price' => 15.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 2],
        ];

        // Act
        $order = $this->orderService->createFromCart($user, $items);

        // Assert — relationships loaded
        $this->assertTrue($order->relationLoaded('items'));
        $this->assertCount(1, $order->items);
        $this->assertTrue($order->items->first()->relationLoaded('product'));
    }

    public function test_createFromCart_whenProductInactive_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => false, 'price' => 10.00]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 1],
        ];

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->orderService->createFromCart($user, $items);
    }

    public function test_createFromCart_whenProductInactive_doesNotCreateOrder(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => false, 'price' => 10.00]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 1],
        ];

        // Act
        try {
            $this->orderService->createFromCart($user, $items);
        } catch (ValidationException) {
            // expected
        }

        // Assert — no order created due to transaction rollback
        $this->assertEquals(0, Order::where('user_id', $user->id)->count());
    }

    public function test_createFromCart_whenInsufficientStock_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 3, 'active' => true, 'price' => 10.00]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 10],
        ];

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->orderService->createFromCart($user, $items);
    }

    public function test_createFromCart_whenInsufficientStock_doesNotDecrementStock(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 3, 'active' => true, 'price' => 10.00]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 10],
        ];

        // Act
        try {
            $this->orderService->createFromCart($user, $items);
        } catch (ValidationException) {
            // expected
        }

        // Assert — stock unchanged due to transaction rollback
        $this->assertEquals(3, $product->fresh()->stock);
    }

    // ── listForUser ────────────────────────────────────────────────

    public function test_listForUser_returnsOnlyUserOrders(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();

        Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Order::factory()->create(['user_id' => $otherUser->id, 'status' => 'pending']);

        // Act
        $orders = $this->orderService->listForUser($user);

        // Assert
        $this->assertCount(2, $orders);
        $orders->each(fn($order) => $this->assertEquals($user->id, $order->user_id));
    }

    public function test_listForUser_ordersFromNewestToOldest(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        $oldest = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(3)]);
        $middle = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(1)]);
        $newest = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        // Act
        $orders = $this->orderService->listForUser($user);

        // Assert — newest first
        $this->assertCount(3, $orders);
        $this->assertEquals($newest->id, $orders[0]->id);
        $this->assertEquals($middle->id, $orders[1]->id);
        $this->assertEquals($oldest->id, $orders[2]->id);
    }

    public function test_listForUser_loadsItemsAndProductRelationships(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        // Act
        $orders = $this->orderService->listForUser($user);

        // Assert
        $this->assertTrue($orders->first()->relationLoaded('items'));
        $this->assertTrue($orders->first()->items->first()->relationLoaded('product'));
    }

    // ── listAll ────────────────────────────────────────────────────

    public function test_listAll_returnsAllOrders(): void
    {
        // Arrange
        $user1 = $this->createRegularUser();
        $user2 = $this->createRegularUser();

        Order::factory()->create(['user_id' => $user1->id]);
        Order::factory()->create(['user_id' => $user2->id]);
        Order::factory()->create(['user_id' => $user1->id]);

        // Act
        $orders = $this->orderService->listAll();

        // Assert
        $this->assertCount(3, $orders);
    }

    public function test_listAll_loadsRelationships(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        // Act
        $orders = $this->orderService->listAll();

        // Assert — items.product and user loaded
        $first = $orders->first();
        $this->assertTrue($first->relationLoaded('items'));
        $this->assertTrue($first->items->first()->relationLoaded('product'));
        $this->assertTrue($first->relationLoaded('user'));
    }

    public function test_listAll_ordersFromNewestToOldest(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        $oldest = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(3)]);
        $newest = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        // Act
        $orders = $this->orderService->listAll();

        // Assert
        $this->assertEquals($newest->id, $orders[0]->id);
        $this->assertEquals($oldest->id, $orders[1]->id);
    }

    // ── updateStatus ───────────────────────────────────────────────

    public function test_updateStatus_updatesOrderStatus(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        // Act
        $result = $this->orderService->updateStatus($order->id, 'completed');

        // Assert
        $this->assertEquals('completed', $result->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_updateStatus_returnsOrderWithLoadedRelationships(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        // Act
        $result = $this->orderService->updateStatus($order->id, 'processing');

        // Assert
        $this->assertTrue($result->relationLoaded('items'));
        $this->assertTrue($result->items->first()->relationLoaded('product'));
        $this->assertTrue($result->relationLoaded('user'));
    }

    public function test_updateStatus_withDifferentStatuses(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        foreach ($statuses as $status) {
            $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

            // Act
            $result = $this->orderService->updateStatus($order->id, $status);

            // Assert
            $this->assertEquals($status, $result->status);
        }
    }

    // ── Property-Based Tests ───────────────────────────────────────

    // Feature: ai-unit-tests, Property 16: createFromCart cria pedido completo e atualiza estoque
    // **Validates: Requirements 5.1**

    #[\PHPUnit\Framework\Attributes\DataProvider('createFromCartValidProvider')]
    public function test_property_createFromCart_createsCompleteOrderAndUpdatesStock(
        int $numProducts,
        array $stocks,
        array $prices,
        array $quantities
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $products = [];
        $items = [];

        for ($i = 0; $i < $numProducts; $i++) {
            $product = Product::factory()->create([
                'stock' => $stocks[$i],
                'active' => true,
                'price' => $prices[$i],
            ]);
            $products[] = $product;

            // Create CartItems for the user so clear() has something to remove
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantities[$i],
            ]);

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $quantities[$i],
            ];
        }

        // Act
        $order = $this->orderService->createFromCart($user, $items);

        // Assert — order created with pending status
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($user->id, $order->user_id);

        // Assert — correct OrderItems created
        $this->assertCount($numProducts, $order->items);
        for ($i = 0; $i < $numProducts; $i++) {
            $this->assertDatabaseHas('order_items', [
                'order_id' => $order->id,
                'product_id' => $products[$i]->id,
                'quantity' => $quantities[$i],
                'price' => $prices[$i],
            ]);
        }

        // Assert — stock decremented
        for ($i = 0; $i < $numProducts; $i++) {
            $this->assertEquals(
                $stocks[$i] - $quantities[$i],
                $products[$i]->fresh()->stock
            );
        }

        // Assert — cart cleared
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    public static function createFromCartValidProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $numProducts = random_int(1, 4);
            $stocks = [];
            $prices = [];
            $quantities = [];
            for ($j = 0; $j < $numProducts; $j++) {
                $stock = random_int(5, 100);
                $stocks[] = $stock;
                $prices[] = round(random_int(100, 50000) / 100, 2);
                $quantities[] = random_int(1, min($stock, 10));
            }
            $cases["iteration_{$i}"] = [$numProducts, $stocks, $prices, $quantities];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 17: createFromCart rejeita itens com produto inativo ou estoque insuficiente
    // **Validates: Requirements 5.2, 5.3**

    #[\PHPUnit\Framework\Attributes\DataProvider('createFromCartRejectsProvider')]
    public function test_property_createFromCart_rejectsInactiveProductOrInsufficientStock(
        string $scenario,
        bool $active,
        int $stock,
        int $quantity
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create([
            'stock' => $stock,
            'active' => $active,
            'price' => round(random_int(100, 10000) / 100, 2),
        ]);

        $items = [
            ['product_id' => $product->id, 'quantity' => $quantity],
        ];

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->orderService->createFromCart($user, $items);
    }

    public static function createFromCartRejectsProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i % 2 === 0) {
                // Inactive product scenario
                $stock = random_int(1, 100);
                $quantity = random_int(1, $stock);
                $cases["inactive_product_{$i}"] = ['inactive_product', false, $stock, $quantity];
            } else {
                // Insufficient stock scenario
                $stock = random_int(0, 10);
                $quantity = $stock + random_int(1, 20);
                $cases["insufficient_stock_{$i}"] = ['insufficient_stock', true, $stock, $quantity];
            }
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 18: Listagem de pedidos retorna resultados ordenados cronologicamente
    // **Validates: Requirements 5.4, 5.5**

    #[\PHPUnit\Framework\Attributes\DataProvider('orderListingProvider')]
    public function test_property_orderListing_returnsChronologicallyOrderedResults(
        int $userOrderCount,
        int $otherOrderCount
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();

        $userOrders = [];
        for ($i = 0; $i < $userOrderCount; $i++) {
            $userOrders[] = Order::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subMinutes($userOrderCount - $i),
            ]);
        }

        $otherOrders = [];
        for ($i = 0; $i < $otherOrderCount; $i++) {
            $otherOrders[] = Order::factory()->create([
                'user_id' => $otherUser->id,
                'created_at' => now()->subMinutes($otherOrderCount - $i),
            ]);
        }

        // Act
        $forUser = $this->orderService->listForUser($user);
        $all = $this->orderService->listAll();

        // Assert — listForUser returns only user's orders
        $this->assertCount($userOrderCount, $forUser);
        $forUser->each(fn($order) => $this->assertEquals($user->id, $order->user_id));

        // Assert — listForUser ordered newest to oldest
        for ($i = 1; $i < $forUser->count(); $i++) {
            $this->assertTrue(
                $forUser[$i - 1]->created_at >= $forUser[$i]->created_at,
                'listForUser should return orders newest to oldest'
            );
        }

        // Assert — listAll returns all orders
        $this->assertCount($userOrderCount + $otherOrderCount, $all);

        // Assert — listAll ordered newest to oldest
        for ($i = 1; $i < $all->count(); $i++) {
            $this->assertTrue(
                $all[$i - 1]->created_at >= $all[$i]->created_at,
                'listAll should return orders newest to oldest'
            );
        }
    }

    public static function orderListingProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $userOrderCount = random_int(0, 8);
            $otherOrderCount = random_int(0, 5);
            $cases["iteration_{$i}"] = [$userOrderCount, $otherOrderCount];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 19: updateStatus atualiza o status do pedido
    // **Validates: Requirements 5.6**

    #[\PHPUnit\Framework\Attributes\DataProvider('updateStatusProvider')]
    public function test_property_updateStatus_updatesOrderStatusCorrectly(
        string $initialStatus,
        string $newStatus
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $initialStatus,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->orderService->updateStatus($order->id, $newStatus);

        // Assert — status updated
        $this->assertEquals($newStatus, $result->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $newStatus,
        ]);

        // Assert — relationships loaded
        $this->assertTrue($result->relationLoaded('items'));
        $this->assertTrue($result->items->first()->relationLoaded('product'));
        $this->assertTrue($result->relationLoaded('user'));
    }

    public static function updateStatusProvider(): array
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $initialStatus = $statuses[array_rand($statuses)];
            $newStatus = $statuses[array_rand($statuses)];
            $cases["iteration_{$i}"] = [$initialStatus, $newStatus];
        }
        return $cases;
    }
}
