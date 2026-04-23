<?php

namespace Tests\TesteIA\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Tests\TesteIA\TesteIATestCase;

class CartControllerTest extends TesteIATestCase
{
    // ==========================================
    // GET /api/cart
    // ==========================================

    /**
     * Validates: Requirements 8.1
     */
    public function test_index_authenticated_returns200WithCartItems(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 20, 'active' => true]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/cart');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'items' => [
                ['id', 'product_id', 'quantity', 'product', 'available_stock'],
            ],
        ]);
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.product_id', $product->id);
        $response->assertJsonPath('items.0.quantity', 3);
    }

    /**
     * Validates: Requirements 8.2
     */
    public function test_index_unauthenticated_returns401(): void
    {
        // Act
        $response = $this->getJson('/api/cart');

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/cart
    // ==========================================

    /**
     * Validates: Requirements 8.3
     */
    public function test_store_withValidData_returns200WithItem(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'item' => ['id', 'product_id', 'quantity'],
        ]);
        $response->assertJsonPath('item.product_id', $product->id);
        $response->assertJsonPath('item.quantity', 2);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Validates: Requirements 8.4
     */
    public function test_store_withNonExistentProductId_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'product_id' => 99999,
                'quantity' => 1,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id']);
    }

    /**
     * Validates: Requirements 8.5
     */
    public function test_store_withStockError_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 2, 'active' => true]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Validates: Requirements 8.5
     */
    public function test_store_withInactiveProduct_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => false]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    // ==========================================
    // PUT /api/cart/{id}
    // ==========================================

    /**
     * Validates: Requirements 8.6
     */
    public function test_update_withValidQuantity_returns200WithUpdatedItem(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 20, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/cart/{$cartItem->id}", [
                'quantity' => 5,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'item' => ['id', 'product_id', 'quantity'],
        ]);
        $response->assertJsonPath('item.quantity', 5);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Validates: Requirements 8.7
     */
    public function test_update_withQuantityExceedingStock_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/cart/{$cartItem->id}", [
                'quantity' => 100,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    // ==========================================
    // DELETE /api/cart/{id}
    // ==========================================

    /**
     * Validates: Requirements 8.8
     */
    public function test_destroy_existingItem_returns200AndRemovesItem(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/cart/{$cartItem->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Item removido do carrinho.');
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Validates: Requirements 8.3 — store with missing quantity returns 422
     */
    public function test_store_withMissingQuantity_returns422(): void
    {
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', ['product_id' => $product->id]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Validates: Requirements 8.3 — store with missing product_id returns 422
     */
    public function test_store_withMissingProductId_returns422(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', ['quantity' => 1]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id']);
    }

    /**
     * Validates: Requirements 8.6 — update with missing quantity returns 422
     */
    public function test_update_withMissingQuantity_returns422(): void
    {
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/cart/{$cartItem->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Validates: Requirements 8.2 — store unauthenticated returns 401
     */
    public function test_store_unauthenticated_returns401(): void
    {
        $response = $this->postJson('/api/cart', [
            'product_id' => 1,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 8.2 — update unauthenticated returns 401
     */
    public function test_update_unauthenticated_returns401(): void
    {
        $response = $this->putJson('/api/cart/1', ['quantity' => 1]);

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 8.2 — delete unauthenticated returns 401
     */
    public function test_destroy_unauthenticated_returns401(): void
    {
        $response = $this->deleteJson('/api/cart/1');

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 8.1 — index returns empty when no items
     */
    public function test_index_authenticated_withNoItems_returns200WithEmptyItems(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/cart');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'items');
    }


    // ==========================================
    // Property-Based Tests
    // ==========================================

    // Feature: ai-unit-tests, Property 24: Endpoints do carrinho retornam status HTTP corretos para operações válidas
    // **Validates: Requirements 8.1, 8.3, 8.6, 8.8**

    #[\PHPUnit\Framework\Attributes\DataProvider('cartValidOperationsProvider')]
    public function test_property_cartEndpoints_returnCorrectHttpStatusForValidOperations(
        string $scenario,
        int $stock,
        int $addQuantity,
        ?int $updateQuantity,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create([
            'stock' => $stock,
            'active' => true,
        ]);

        // Act & Assert — GET /api/cart returns 200
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/cart');
        $response->assertStatus(200);
        $response->assertJsonStructure(['items']);

        // Act & Assert — POST /api/cart with valid data returns 200
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart', [
                'product_id' => $product->id,
                'quantity' => $addQuantity,
            ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'item']);

        $cartItemId = $response->json('item.id');

        // Act & Assert — PUT /api/cart/{id} with valid quantity returns 200
        if ($updateQuantity !== null) {
            $response = $this->actingAs($user, 'sanctum')
                ->putJson("/api/cart/{$cartItemId}", [
                    'quantity' => $updateQuantity,
                ]);
            $response->assertStatus(200);
            $response->assertJsonStructure(['message', 'item']);
        }

        // Act & Assert — DELETE /api/cart/{id} returns 200
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/cart/{$cartItemId}");
        $response->assertStatus(200);

        // Verify item was removed
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    public static function cartValidOperationsProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(10, 100);
            $addQuantity = random_int(1, (int) floor($stock / 2));
            $updateQuantity = random_int(1, $stock);

            $cases["iteration_{$i}"] = [
                "stock={$stock}, add={$addQuantity}, update={$updateQuantity}",
                $stock,
                $addQuantity,
                $updateQuantity,
            ];
        }

        return $cases;
    }

    // Feature: ai-unit-tests, Property 25: Endpoints do carrinho propagam erros de validação como 422
    // **Validates: Requirements 8.5, 8.7**

    #[\PHPUnit\Framework\Attributes\DataProvider('cartValidationErrorsProvider')]
    public function test_property_cartEndpoints_propagateValidationErrorsAs422(
        string $scenario,
        string $errorType,
        int $stock,
        int $quantity,
    ): void {
        // Arrange
        $user = $this->createRegularUser();

        if ($errorType === 'stock_exceeded_on_add') {
            $product = Product::factory()->create([
                'stock' => $stock,
                'active' => true,
            ]);

            // Act
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/cart', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);

            // Assert
            $response->assertStatus(422);
            $response->assertJsonStructure(['message', 'errors']);
        } elseif ($errorType === 'inactive_product') {
            $product = Product::factory()->create([
                'stock' => $stock,
                'active' => false,
            ]);

            // Act
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/cart', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);

            // Assert
            $response->assertStatus(422);
            $response->assertJsonStructure(['message', 'errors']);
        } elseif ($errorType === 'stock_exceeded_on_update') {
            $product = Product::factory()->create([
                'stock' => $stock,
                'active' => true,
            ]);
            $cartItem = CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

            // Act
            $response = $this->actingAs($user, 'sanctum')
                ->putJson("/api/cart/{$cartItem->id}", [
                    'quantity' => $quantity,
                ]);

            // Assert
            $response->assertStatus(422);
            $response->assertJsonStructure(['message', 'errors']);
        }
    }

    public static function cartValidationErrorsProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 10; $i++) {
            $scenarioIndex = $i % 3;

            switch ($scenarioIndex) {
                case 0:
                    // Stock exceeded on add
                    $stock = random_int(1, 5);
                    $quantity = $stock + random_int(1, 10);
                    $cases["iteration_{$i}_stock_exceeded_add"] = [
                        "add qty={$quantity} exceeds stock={$stock}",
                        'stock_exceeded_on_add',
                        $stock,
                        $quantity,
                    ];
                    break;

                case 1:
                    // Inactive product
                    $stock = random_int(1, 50);
                    $quantity = random_int(1, 5);
                    $cases["iteration_{$i}_inactive_product"] = [
                        "inactive product stock={$stock}",
                        'inactive_product',
                        $stock,
                        $quantity,
                    ];
                    break;

                case 2:
                    // Stock exceeded on update
                    $stock = random_int(1, 5);
                    $quantity = $stock + random_int(1, 10);
                    $cases["iteration_{$i}_stock_exceeded_update"] = [
                        "update qty={$quantity} exceeds stock={$stock}",
                        'stock_exceeded_on_update',
                        $stock,
                        $quantity,
                    ];
                    break;
            }
        }

        return $cases;
    }
}
