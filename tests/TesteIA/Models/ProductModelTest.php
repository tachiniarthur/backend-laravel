<?php

namespace Tests\TesteIA\Models;

use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use Tests\TesteIA\TesteIATestCase;

class ProductModelTest extends TesteIATestCase
{
    // ── Relationships ──────────────────────────────────────────────

    public function test_cartItems_relationship_returnsAssociatedCartItems(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $cartItem1 = CartItem::factory()->create(['product_id' => $product->id]);
        $cartItem2 = CartItem::factory()->create(['product_id' => $product->id]);
        CartItem::factory()->create(); // another product's cart item

        // Act
        $cartItems = $product->cartItems;

        // Assert
        $this->assertCount(2, $cartItems);
        $this->assertTrue($cartItems->contains($cartItem1));
        $this->assertTrue($cartItems->contains($cartItem2));
    }

    public function test_orderItems_relationship_returnsAssociatedOrderItems(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $orderItem1 = OrderItem::factory()->create(['product_id' => $product->id]);
        $orderItem2 = OrderItem::factory()->create(['product_id' => $product->id]);
        OrderItem::factory()->create(); // another product's order item

        // Act
        $orderItems = $product->orderItems;

        // Assert
        $this->assertCount(2, $orderItems);
        $this->assertTrue($orderItems->contains($orderItem1));
        $this->assertTrue($orderItems->contains($orderItem2));
    }

    // ── Fillable (mass assignment) ─────────────────────────────────

    public function test_fillable_allowsMassAssignmentOfDefinedFields(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'description' => 'A test description',
            'price' => 29.99,
            'image_url' => 'https://example.com/image.jpg',
            'stock' => 50,
            'active' => true,
        ];

        // Act
        $product = Product::create($data);

        // Assert
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'A test description',
            'stock' => 50,
            'image_url' => 'https://example.com/image.jpg',
        ]);
    }

    // ── Casts ──────────────────────────────────────────────────────

    public function test_cast_price_isDecimalWithTwoPlaces(): void
    {
        // Arrange & Act
        $product = Product::factory()->create(['price' => 19.5]);

        // Assert
        $fresh = $product->fresh();
        $this->assertIsString($fresh->price);
        $this->assertEquals('19.50', $fresh->price);
    }

    public function test_cast_stock_isInteger(): void
    {
        // Arrange & Act
        $product = Product::factory()->create(['stock' => 42]);

        // Assert
        $fresh = $product->fresh();
        $this->assertIsInt($fresh->stock);
        $this->assertSame(42, $fresh->stock);
    }

    public function test_cast_active_isBoolean(): void
    {
        // Arrange & Act
        $productActive = Product::factory()->create(['active' => true]);
        $productInactive = Product::factory()->create(['active' => false]);

        // Assert
        $this->assertIsBool($productActive->fresh()->active);
        $this->assertTrue($productActive->fresh()->active);
        $this->assertIsBool($productInactive->fresh()->active);
        $this->assertFalse($productInactive->fresh()->active);
    }

    // ── Scope: scopeActive ─────────────────────────────────────────

    public function test_scopeActive_returnsOnlyActiveProducts(): void
    {
        // Arrange
        $active1 = Product::factory()->create(['active' => true]);
        $active2 = Product::factory()->create(['active' => true]);
        Product::factory()->create(['active' => false]);
        Product::factory()->create(['active' => false]);

        // Act
        $activeProducts = Product::active()->get();

        // Assert
        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->contains($active1));
        $this->assertTrue($activeProducts->contains($active2));
    }

    public function test_scopeActive_returnsEmptyWhenNoActiveProducts(): void
    {
        // Arrange
        Product::factory()->create(['active' => false]);
        Product::factory()->create(['active' => false]);

        // Act
        $activeProducts = Product::active()->get();

        // Assert
        $this->assertCount(0, $activeProducts);
    }

    // ── Accessor: reserved_quantity ────────────────────────────────

    public function test_reservedQuantity_returnsSumOfCartItemQuantities(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 100]);
        CartItem::factory()->create(['product_id' => $product->id, 'quantity' => 3]);
        CartItem::factory()->create(['product_id' => $product->id, 'quantity' => 5]);

        // Act
        $reserved = $product->reserved_quantity;

        // Assert
        $this->assertSame(8, $reserved);
    }

    public function test_reservedQuantity_returnsZeroWhenNoCartItems(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 50]);

        // Act
        $reserved = $product->reserved_quantity;

        // Assert
        $this->assertSame(0, $reserved);
    }

    // ── Accessor: available_stock ──────────────────────────────────

    public function test_availableStock_returnsStockMinusReservedQuantity(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 20]);
        CartItem::factory()->create(['product_id' => $product->id, 'quantity' => 7]);

        // Act
        $available = $product->available_stock;

        // Assert
        $this->assertSame(13, $available);
    }

    public function test_availableStock_returnsZeroWhenReservedExceedsStock(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 5]);
        CartItem::factory()->create(['product_id' => $product->id, 'quantity' => 10]);

        // Act
        $available = $product->available_stock;

        // Assert
        $this->assertSame(0, $available);
    }

    public function test_availableStock_equalsStockWhenNoReservations(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 30]);

        // Act
        $available = $product->available_stock;

        // Assert
        $this->assertSame(30, $available);
    }

    // ── Method: decreaseStock ──────────────────────────────────────

    public function test_decreaseStock_decrementsStockByGivenQuantity(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 50]);

        // Act
        $product->decreaseStock(15);

        // Assert
        $this->assertSame(35, $product->fresh()->stock);
    }

    public function test_decreaseStock_decrementsToZero(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 10]);

        // Act
        $product->decreaseStock(10);

        // Assert
        $this->assertSame(0, $product->fresh()->stock);
    }

    // ── Property-Based Test: Scope Active ──────────────────────────

    // Feature: ai-unit-tests, Property 3: Scope active filtra apenas produtos ativos
    // **Validates: Requirements 2.4, 6.1**

    /**
     * @dataProvider scopeActiveProvider
     */
    public function test_property_scopeActive_returnsOnlyActiveProducts(int $activeCount, int $inactiveCount): void
    {
        // Arrange
        $activeProducts = Product::factory()->count($activeCount)->create(['active' => true]);
        Product::factory()->count($inactiveCount)->create(['active' => false]);

        // Act
        $result = Product::active()->get();

        // Assert
        $this->assertCount($activeCount, $result);
        foreach ($activeProducts as $activeProduct) {
            $this->assertTrue($result->contains('id', $activeProduct->id));
        }
        foreach ($result as $product) {
            $this->assertTrue($product->active);
        }
    }

    public static function scopeActiveProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $activeCount = random_int(0, 10);
            $inactiveCount = random_int(0, 10);
            $cases["iteration_{$i}"] = [$activeCount, $inactiveCount];
        }
        return $cases;
    }

    // ── Property-Based Test: Available Stock ────────────────────────

    // Feature: ai-unit-tests, Property 4: Estoque disponível é calculado corretamente
    // **Validates: Requirements 2.5, 2.6**

    #[\PHPUnit\Framework\Attributes\DataProvider('availableStockProvider')]
    public function test_property_availableStock_isCalculatedCorrectly(int $stock, array $cartQuantities): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => $stock]);
        foreach ($cartQuantities as $quantity) {
            CartItem::factory()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        // Act
        $fresh = $product->fresh();
        $reservedQuantity = $fresh->reserved_quantity;
        $availableStock = $fresh->available_stock;

        // Assert
        $expectedReserved = array_sum($cartQuantities);
        $expectedAvailable = max(0, $stock - $expectedReserved);

        $this->assertSame($expectedReserved, $reservedQuantity);
        $this->assertSame($expectedAvailable, $availableStock);
    }

    public static function availableStockProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(0, 1000);
            $numItems = random_int(0, 5);
            $quantities = [];
            for ($j = 0; $j < $numItems; $j++) {
                $quantities[] = random_int(1, 100);
            }
            $cases["iteration_{$i}"] = [$stock, $quantities];
        }
        return $cases;
    }

    // ── Property-Based Test: decreaseStock ──────────────────────────

    // Feature: ai-unit-tests, Property 5: decreaseStock decrementa o estoque corretamente
    // **Validates: Requirements 2.7**

    #[\PHPUnit\Framework\Attributes\DataProvider('decreaseStockProvider')]
    public function test_property_decreaseStock_decrementsStockCorrectly(int $stock, int $quantity): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => $stock]);

        // Act
        $product->decreaseStock($quantity);

        // Assert
        $this->assertSame($stock - $quantity, $product->fresh()->stock);
    }

    public static function decreaseStockProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(1, 1000);
            $quantity = random_int(1, $stock);
            $cases["iteration_{$i}"] = [$stock, $quantity];
        }
        return $cases;
    }

}
