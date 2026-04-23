<?php

namespace Tests\TesteIA\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Tests\TesteIA\TesteIATestCase;

class OrderControllerTest extends TesteIATestCase
{
    // ==========================================
    // POST /api/orders
    // ==========================================

    /**
     * Validates: Requirements 9.1
     */
    public function test_store_withValidItems_returns201WithOrder(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product1 = Product::factory()->create(['stock' => 20, 'active' => true, 'price' => 15.50]);
        $product2 = Product::factory()->create(['stock' => 10, 'active' => true, 'price' => 30.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 1]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 1],
                ],
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('status', 'pending');
        $response->assertJsonPath('user_id', $user->id);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        $this->assertEquals(18, $product1->fresh()->stock);
        $this->assertEquals(9, $product2->fresh()->stock);
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    /**
     * Validates: Requirements 9.2
     */
    public function test_store_withInvalidData_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act — missing items
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * Validates: Requirements 9.2
     */
    public function test_store_withEmptyItemsArray_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [],
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * Validates: Requirements 9.2
     */
    public function test_store_withMissingProductId_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['quantity' => 1],
                ],
            ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * Validates: Requirements 9.2
     */
    public function test_store_withNonExistentProductId_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => 99999, 'quantity' => 1],
                ],
            ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * Validates: Requirements 9.3
     */
    public function test_store_withStockError_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 2, 'active' => true, 'price' => 10.00]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 50],
                ],
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Validates: Requirements 9.3
     */
    public function test_store_withInactiveProduct_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => false, 'price' => 10.00]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    // ==========================================
    // GET /api/orders
    // ==========================================

    /**
     * Validates: Requirements 9.4
     */
    public function test_index_authenticated_returns200WithUserOrders(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();

        $product = Product::factory()->create(['active' => true]);
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => $otherUser->id]);

        OrderItem::factory()->create(['order_id' => $order1->id, 'product_id' => $product->id]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'product_id' => $product->id]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /**
     * Validates: Requirements 9.4
     */
    public function test_index_unauthenticated_returns401(): void
    {
        // Act
        $response = $this->getJson('/api/orders');

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // GET /api/admin/orders
    // ==========================================

    /**
     * Validates: Requirements 9.5
     */
    public function test_indexAll_asAdmin_returns200WithAllOrders(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        Order::factory()->create(['user_id' => $admin->id]);
        Order::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/orders');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /**
     * Validates: Requirements 9.6
     */
    public function test_indexAll_asNonAdmin_returns403(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/orders');

        // Assert
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Acesso negado.');
    }

    // ==========================================
    // PATCH /api/admin/orders/{id}/status
    // ==========================================

    /**
     * Validates: Requirements 9.7
     */
    public function test_updateStatus_asAdmin_withValidStatus_returns200(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $order = Order::factory()->create(['user_id' => $admin->id, 'status' => 'pending']);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'processing');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    /**
     * Validates: Requirements 9.8
     */
    public function test_updateStatus_asNonAdmin_returns403(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Acesso negado.');
    }

    /**
     * Validates: Requirements 9.9
     */
    public function test_updateStatus_asAdmin_withInvalidStatus_returns422(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $order = Order::factory()->create(['user_id' => $admin->id, 'status' => 'pending']);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'invalid_status',
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /**
     * Validates: Requirements 9.9
     */
    public function test_updateStatus_asAdmin_withMissingStatus_returns422(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $order = Order::factory()->create(['user_id' => $admin->id, 'status' => 'pending']);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /**
     * Validates: Requirements 9.1 — store unauthenticated returns 401
     */
    public function test_store_unauthenticated_returns401(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 9.5 — admin orders unauthenticated returns 401
     */
    public function test_indexAll_unauthenticated_returns401(): void
    {
        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 9.7 — updateStatus unauthenticated returns 401
     */
    public function test_updateStatus_unauthenticated_returns401(): void
    {
        $response = $this->patchJson('/api/admin/orders/1/status', [
            'status' => 'processing',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 9.4 — index returns empty when user has no orders
     */
    public function test_index_authenticated_withNoOrders_returns200WithEmptyArray(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }


    // ==========================================
    // Property-Based Tests
    // ==========================================

    // Feature: ai-unit-tests, Property 26: Endpoints de pedidos retornam status HTTP corretos
    // **Validates: Requirements 9.1, 9.3, 9.4**

    #[\PHPUnit\Framework\Attributes\DataProvider('orderEndpointsStatusProvider')]
    public function test_property_orderEndpoints_returnCorrectHttpStatusCodes(
        string $scenario,
        int $numProducts,
        array $stocks,
        array $prices,
        array $quantities,
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

            // Create cart items so createFromCart can clear them
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

        // Act & Assert — POST /api/orders with valid items returns 201
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', ['items' => $items]);
        $response->assertStatus(201);
        $response->assertJsonPath('status', 'pending');

        // Verify stock was decremented
        for ($i = 0; $i < $numProducts; $i++) {
            $this->assertEquals(
                $stocks[$i] - $quantities[$i],
                $products[$i]->fresh()->stock
            );
        }

        // Verify cart was cleared
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());

        // Act & Assert — GET /api/orders returns 200 with user's orders
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');
        $response->assertStatus(200);
        $response->assertJsonCount(1);

        // Act & Assert — POST /api/orders with stock error returns 422
        $lowStockProduct = Product::factory()->create([
            'stock' => 1,
            'active' => true,
            'price' => 5.00,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $lowStockProduct->id, 'quantity' => 100],
                ],
            ]);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public static function orderEndpointsStatusProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $numProducts = random_int(1, 3);
            $stocks = [];
            $prices = [];
            $quantities = [];
            for ($j = 0; $j < $numProducts; $j++) {
                $stock = random_int(10, 100);
                $stocks[] = $stock;
                $prices[] = round(random_int(100, 50000) / 100, 2);
                $quantities[] = random_int(1, min($stock, 5));
            }
            $cases["iteration_{$i}"] = [
                "products={$numProducts}",
                $numProducts,
                $stocks,
                $prices,
                $quantities,
            ];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 27: Endpoints admin de pedidos verificam autorização
    // **Validates: Requirements 9.5, 9.6, 9.7, 9.8**

    #[\PHPUnit\Framework\Attributes\DataProvider('adminOrderAuthorizationProvider')]
    public function test_property_adminOrderEndpoints_verifyAuthorization(
        string $scenario,
        string $status,
    ): void {
        // Arrange
        $admin = $this->createAdminUser();
        $regularUser = $this->createRegularUser();
        $order = Order::factory()->create(['user_id' => $regularUser->id, 'status' => 'pending']);

        // Act & Assert — Admin GET /api/admin/orders returns 200
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/orders');
        $response->assertStatus(200);

        // Act & Assert — Non-admin GET /api/admin/orders returns 403
        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/admin/orders');
        $response->assertStatus(403);

        // Act & Assert — Admin PATCH /api/admin/orders/{id}/status returns 200
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => $status,
            ]);
        $response->assertStatus(200);
        $response->assertJsonPath('status', $status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $status]);

        // Act & Assert — Non-admin PATCH /api/admin/orders/{id}/status returns 403
        $anotherOrder = Order::factory()->create(['user_id' => $regularUser->id, 'status' => 'pending']);
        $response = $this->actingAs($regularUser, 'sanctum')
            ->patchJson("/api/admin/orders/{$anotherOrder->id}/status", [
                'status' => $status,
            ]);
        $response->assertStatus(403);

        // Verify the order status was NOT changed by non-admin
        $this->assertDatabaseHas('orders', ['id' => $anotherOrder->id, 'status' => 'pending']);
    }

    public static function adminOrderAuthorizationProvider(): array
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $status = $statuses[$i % count($statuses)];
            $cases["iteration_{$i}_{$status}"] = [
                "status={$status}",
                $status,
            ];
        }
        return $cases;
    }
}
