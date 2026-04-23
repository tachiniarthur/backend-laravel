<?php

namespace Tests\TesteIA\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;
use Tests\TesteIA\TesteIATestCase;

class CartServiceTest extends TesteIATestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    // ── availableStockForUser ──────────────────────────────────────

    public function test_availableStockForUser_subtractsReservationsFromOtherUsers(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser1 = $this->createRegularUser();
        $otherUser2 = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 20, 'active' => true]);

        // Other users reserve stock
        CartItem::factory()->create(['user_id' => $otherUser1->id, 'product_id' => $product->id, 'quantity' => 3]);
        CartItem::factory()->create(['user_id' => $otherUser2->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Current user also has items — should NOT be subtracted
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        // Act
        $available = $this->cartService->availableStockForUser($product, $user);

        // Assert — stock(20) - otherReservations(3+5) = 12
        $this->assertEquals(12, $available);
    }

    public function test_availableStockForUser_returnsZeroWhenOthersReserveAll(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);

        CartItem::factory()->create(['user_id' => $otherUser->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Act
        $available = $this->cartService->availableStockForUser($product, $user);

        // Assert
        $this->assertEquals(0, $available);
    }

    public function test_availableStockForUser_returnsZeroWhenOthersExceedStock(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 3, 'active' => true]);

        CartItem::factory()->create(['user_id' => $otherUser->id, 'product_id' => $product->id, 'quantity' => 10]);

        // Act
        $available = $this->cartService->availableStockForUser($product, $user);

        // Assert — max(0, 3 - 10) = 0
        $this->assertEquals(0, $available);
    }

    // ── getItems ───────────────────────────────────────────────────

    public function test_getItems_returnsItemsWithProductDataAndAvailableStock(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true, 'price' => 25.50]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        // Act
        $items = $this->cartService->getItems($user);

        // Assert
        $this->assertCount(1, $items);
        $item = $items->first();

        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals(3, $item['quantity']);
        $this->assertArrayHasKey('product', $item);
        $this->assertEquals($product->id, $item['product']['id']);
        $this->assertEquals($product->name, $item['product']['name']);
        $this->assertEquals($product->price, $item['product']['price']);
        $this->assertArrayHasKey('available_stock', $item['product']);
        $this->assertArrayHasKey('available_stock', $item);
    }

    public function test_getItems_calculatesAvailableStockCorrectly(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);

        CartItem::factory()->create(['user_id' => $otherUser->id, 'product_id' => $product->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        // Act
        $items = $this->cartService->getItems($user);

        // Assert
        $item = $items->first();
        // availableStockForUser = max(0, 10 - 2) = 8 (only other users' reservations)
        $this->assertEquals(8, $item['product']['available_stock']);
        // item-level available_stock = max(0, 8 - 3) = 5
        $this->assertEquals(5, $item['available_stock']);
    }

    // ── addItem ────────────────────────────────────────────────────

    public function test_addItem_withActiveProductAndStock_createsNewCartItem(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);

        // Act
        $result = $this->cartService->addItem($user, $product->id, 3);

        // Assert
        $this->assertInstanceOf(CartItem::class, $result);
        $this->assertEquals(3, $result->quantity);
        $this->assertEquals($product->id, $result->product_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_addItem_withExistingItem_incrementsQuantity(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 20, 'active' => true]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Act
        $result = $this->cartService->addItem($user, $product->id, 3);

        // Assert — 5 + 3 = 8
        $this->assertEquals(8, $result->quantity);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 8,
        ]);
        // Should still be only one cart item
        $this->assertEquals(1, CartItem::where('user_id', $user->id)->where('product_id', $product->id)->count());
    }

    public function test_addItem_whenProductInactive_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => false]);

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 1);
    }

    public function test_addItem_whenStockZeroForUser_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);

        // Other user reserves all stock
        CartItem::factory()->create(['user_id' => $otherUser->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 1);
    }

    public function test_addItem_whenQuantityExceedsStock_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 10);
    }

    public function test_addItem_whenIncrementExceedsStock_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 8]);

        // Assert & Act — existing 8 + 5 = 13 > 10
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 5);
    }

    // ── updateItem ─────────────────────────────────────────────────

    public function test_updateItem_withValidQuantity_updatesQuantity(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartItem = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        // Act
        $result = $this->cartService->updateItem($user, $cartItem->id, 7);

        // Assert
        $this->assertEquals(7, $result->quantity);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 7]);
    }

    public function test_updateItem_appliesMinimumOfOne(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartItem = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Act — quantity 0 should become max(1, 0) = 1
        $result = $this->cartService->updateItem($user, $cartItem->id, 0);

        // Assert
        $this->assertEquals(1, $result->quantity);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 1]);
    }

    public function test_updateItem_withNegativeQuantity_setsToOne(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 10, 'active' => true]);
        $cartItem = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Act — quantity -5 should become max(1, -5) = 1
        $result = $this->cartService->updateItem($user, $cartItem->id, -5);

        // Assert
        $this->assertEquals(1, $result->quantity);
    }

    public function test_updateItem_whenQuantityExceedsStock_throwsValidationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => 5, 'active' => true]);
        $cartItem = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->cartService->updateItem($user, $cartItem->id, 10);
    }

    // ── removeItem ─────────────────────────────────────────────────

    public function test_removeItem_removesSpecificItem(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product1 = Product::factory()->create(['active' => true]);
        $product2 = Product::factory()->create(['active' => true]);
        $item1 = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        $item2 = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 3]);

        // Act
        $this->cartService->removeItem($user, $item1->id);

        // Assert
        $this->assertDatabaseMissing('cart_items', ['id' => $item1->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $item2->id]);
    }

    // ── clear ──────────────────────────────────────────────────────

    public function test_clear_removesAllItemsForUser(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $product1 = Product::factory()->create(['active' => true]);
        $product2 = Product::factory()->create(['active' => true]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 3]);
        CartItem::factory()->create(['user_id' => $otherUser->id, 'product_id' => $product1->id, 'quantity' => 1]);

        // Act
        $this->cartService->clear($user);

        // Assert — user's items removed, other user's items remain
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
        $this->assertEquals(1, CartItem::where('user_id', $otherUser->id)->count());
    }

    // ── Property-Based Tests ───────────────────────────────────────

    // Feature: ai-unit-tests, Property 10: Estoque disponível para usuário exclui reservas de outros
    // **Validates: Requirements 4.1**

    #[\PHPUnit\Framework\Attributes\DataProvider('availableStockForUserProvider')]
    public function test_property_availableStockForUser_excludesOtherUsersReservations(
        int $stock,
        array $otherQuantities,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => $stock, 'active' => true]);

        $totalOtherReservations = 0;
        foreach ($otherQuantities as $qty) {
            $otherUser = $this->createRegularUser();
            CartItem::factory()->create([
                'user_id' => $otherUser->id,
                'product_id' => $product->id,
                'quantity' => $qty,
            ]);
            $totalOtherReservations += $qty;
        }

        // Also add an item for the current user — should NOT be subtracted
        if (random_int(0, 1)) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => random_int(1, 5),
            ]);
        }

        // Act
        $available = $this->cartService->availableStockForUser($product, $user);

        // Assert
        $expected = max(0, $stock - $totalOtherReservations);
        $this->assertEquals($expected, $available);
    }

    public static function availableStockForUserProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(0, 100);
            $numOthers = random_int(0, 5);
            $otherQuantities = [];
            for ($j = 0; $j < $numOthers; $j++) {
                $otherQuantities[] = random_int(1, 20);
            }
            $cases["iteration_{$i}"] = [$stock, $otherQuantities];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 11: getItems retorna itens do carrinho com dados calculados
    // **Validates: Requirements 4.2**

    #[\PHPUnit\Framework\Attributes\DataProvider('getItemsProvider')]
    public function test_property_getItems_returnsCartItemsWithCalculatedData(
        int $numItems,
        array $stocks,
        array $quantities,
        array $otherReservations,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();

        $products = [];
        for ($i = 0; $i < $numItems; $i++) {
            $product = Product::factory()->create(['stock' => $stocks[$i], 'active' => true]);
            $products[] = $product;

            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantities[$i],
            ]);

            if ($otherReservations[$i] > 0) {
                CartItem::factory()->create([
                    'user_id' => $otherUser->id,
                    'product_id' => $product->id,
                    'quantity' => $otherReservations[$i],
                ]);
            }
        }

        // Act
        $items = $this->cartService->getItems($user);

        // Assert
        $this->assertCount($numItems, $items);

        foreach ($items as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('product_id', $item);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertArrayHasKey('product', $item);
            $this->assertArrayHasKey('available_stock', $item);

            // Verify product data is present
            $this->assertArrayHasKey('id', $item['product']);
            $this->assertArrayHasKey('name', $item['product']);
            $this->assertArrayHasKey('price', $item['product']);
            $this->assertArrayHasKey('available_stock', $item['product']);

            // Find the matching product to verify calculations
            $productIndex = array_search($item['product_id'], array_map(fn($p) => $p->id, $products));
            $this->assertNotFalse($productIndex);

            $expectedAvailableForUser = max(0, $stocks[$productIndex] - $otherReservations[$productIndex]);
            $this->assertEquals($expectedAvailableForUser, $item['product']['available_stock']);

            $expectedItemAvailable = max(0, $expectedAvailableForUser - $item['quantity']);
            $this->assertEquals($expectedItemAvailable, $item['available_stock']);
        }
    }

    public static function getItemsProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $numItems = random_int(1, 4);
            $stocks = [];
            $quantities = [];
            $otherReservations = [];
            for ($j = 0; $j < $numItems; $j++) {
                $stock = random_int(5, 50);
                $stocks[] = $stock;
                $quantities[] = random_int(1, max(1, $stock - 1));
                $otherReservations[] = random_int(0, max(0, (int) ($stock / 2)));
            }
            $cases["iteration_{$i}"] = [$numItems, $stocks, $quantities, $otherReservations];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 12: addItem cria ou incrementa item no carrinho
    // **Validates: Requirements 4.3, 4.4**

    #[\PHPUnit\Framework\Attributes\DataProvider('addItemProvider')]
    public function test_property_addItem_createsOrIncrementsCartItem(
        int $stock,
        int $quantity,
        ?int $existingQuantity,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => $stock, 'active' => true]);

        if ($existingQuantity !== null) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $existingQuantity,
            ]);
        }

        // Act
        $result = $this->cartService->addItem($user, $product->id, $quantity);

        // Assert
        $this->assertInstanceOf(CartItem::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($product->id, $result->product_id);

        $expectedQuantity = ($existingQuantity ?? 0) + $quantity;
        $this->assertEquals($expectedQuantity, $result->quantity);

        // Only one cart item should exist for this user+product
        $this->assertEquals(
            1,
            CartItem::where('user_id', $user->id)->where('product_id', $product->id)->count()
        );

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $expectedQuantity,
        ]);
    }

    public static function addItemProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(10, 100);
            $hasExisting = random_int(0, 1) === 1;

            if ($hasExisting) {
                $existingQuantity = random_int(1, (int) ($stock / 2));
                $maxAdd = $stock - $existingQuantity;
                $quantity = random_int(1, max(1, $maxAdd));
            } else {
                $existingQuantity = null;
                $quantity = random_int(1, $stock);
            }

            $cases["iteration_{$i}"] = [$stock, $quantity, $existingQuantity];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 13: addItem rejeita produtos indisponíveis ou sem estoque
    // **Validates: Requirements 4.5, 4.7**

    #[\PHPUnit\Framework\Attributes\DataProvider('addItemRejectsProvider')]
    public function test_property_addItem_rejectsUnavailableProductsOrInsufficientStock(
        string $scenario,
        bool $active,
        int $stock,
        int $quantity,
        int $otherReservation,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => $stock, 'active' => $active]);

        if ($otherReservation > 0) {
            $otherUser = $this->createRegularUser();
            CartItem::factory()->create([
                'user_id' => $otherUser->id,
                'product_id' => $product->id,
                'quantity' => $otherReservation,
            ]);
        }

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, $quantity);
    }

    public static function addItemRejectsProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i % 3 === 0) {
                // Scenario: inactive product
                $stock = random_int(1, 50);
                $cases["iteration_{$i}_inactive"] = [
                    'inactive product',
                    false,
                    $stock,
                    random_int(1, $stock),
                    0,
                ];
            } elseif ($i % 3 === 1) {
                // Scenario: quantity exceeds available stock
                $stock = random_int(5, 50);
                $cases["iteration_{$i}_exceeds_stock"] = [
                    'quantity exceeds stock',
                    true,
                    $stock,
                    $stock + random_int(1, 20),
                    0,
                ];
            } else {
                // Scenario: other users reserved all stock
                $stock = random_int(3, 30);
                $otherReservation = $stock + random_int(0, 5);
                $cases["iteration_{$i}_no_stock_for_user"] = [
                    'no stock available for user',
                    true,
                    $stock,
                    random_int(1, 5),
                    $otherReservation,
                ];
            }
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 14: updateItem atualiza quantidade com mínimo de 1
    // **Validates: Requirements 4.8, 4.9, 4.10**

    #[\PHPUnit\Framework\Attributes\DataProvider('updateItemProvider')]
    public function test_property_updateItem_updatesQuantityWithMinimumOfOne(
        string $scenario,
        int $stock,
        int $requestedQuantity,
        bool $shouldThrow,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['stock' => $stock, 'active' => true]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => random_int(1, max(1, $stock)),
        ]);

        if ($shouldThrow) {
            // Assert & Act — should throw ValidationException
            $this->expectException(ValidationException::class);
            $this->cartService->updateItem($user, $cartItem->id, $requestedQuantity);
        } else {
            // Act
            $result = $this->cartService->updateItem($user, $cartItem->id, $requestedQuantity);

            // Assert
            $expectedQuantity = max(1, $requestedQuantity);
            $this->assertEquals($expectedQuantity, $result->quantity);
            $this->assertDatabaseHas('cart_items', [
                'id' => $cartItem->id,
                'quantity' => $expectedQuantity,
            ]);
        }
    }

    public static function updateItemProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(5, 50);

            if ($i % 4 === 0) {
                // Scenario: valid quantity within stock
                $quantity = random_int(1, $stock);
                $cases["iteration_{$i}_valid"] = ['valid quantity', $stock, $quantity, false];
            } elseif ($i % 4 === 1) {
                // Scenario: zero or negative quantity — should apply max(1, qty)
                $quantity = random_int(-10, 0);
                $cases["iteration_{$i}_min_clamp"] = ['min clamp to 1', $stock, $quantity, false];
            } elseif ($i % 4 === 2) {
                // Scenario: quantity exceeds stock — should throw
                $quantity = $stock + random_int(1, 20);
                $cases["iteration_{$i}_exceeds"] = ['exceeds stock', $stock, $quantity, true];
            } else {
                // Scenario: quantity exactly at stock — should succeed
                $cases["iteration_{$i}_exact"] = ['exact stock', $stock, $stock, false];
            }
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 15: removeItem e clear removem itens do carrinho
    // **Validates: Requirements 4.11, 4.12**

    #[\PHPUnit\Framework\Attributes\DataProvider('removeAndClearProvider')]
    public function test_property_removeItem_and_clear_removeCartItems(
        string $operation,
        int $numItems,
        int $removeIndex,
    ): void {
        // Arrange
        $user = $this->createRegularUser();
        $otherUser = $this->createRegularUser();
        $items = [];

        for ($i = 0; $i < $numItems; $i++) {
            $product = Product::factory()->create(['active' => true]);
            $items[] = CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => random_int(1, 10),
            ]);
        }

        // Other user's item — should never be affected
        $otherProduct = Product::factory()->create(['active' => true]);
        $otherItem = CartItem::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $otherProduct->id,
            'quantity' => random_int(1, 5),
        ]);

        if ($operation === 'removeItem') {
            $targetItem = $items[$removeIndex];

            // Act
            $this->cartService->removeItem($user, $targetItem->id);

            // Assert — specific item removed
            $this->assertDatabaseMissing('cart_items', ['id' => $targetItem->id]);

            // Other items of the same user remain
            foreach ($items as $idx => $item) {
                if ($idx !== $removeIndex) {
                    $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
                }
            }
        } else {
            // operation === 'clear'

            // Act
            $this->cartService->clear($user);

            // Assert — all user items removed
            $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
        }

        // Assert — other user's items are never affected
        $this->assertDatabaseHas('cart_items', ['id' => $otherItem->id]);
    }

    public static function removeAndClearProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $numItems = random_int(1, 5);

            if ($i % 2 === 0) {
                // removeItem — pick a random item to remove
                $removeIndex = random_int(0, $numItems - 1);
                $cases["iteration_{$i}_remove"] = ['removeItem', $numItems, $removeIndex];
            } else {
                // clear — removeIndex is unused but required by signature
                $cases["iteration_{$i}_clear"] = ['clear', $numItems, 0];
            }
        }
        return $cases;
    }
}
