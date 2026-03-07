<?php

namespace Tests\JuniorPlenoTests\Models;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class ProductModelTest extends JuniorPlenoTestCase
{
    public function test_available_stock_returns_stock_minus_reserved(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 20]);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        CartItem::factory()->create(['product_id' => $product->id, 'user_id' => $userA->id, 'quantity' => 3]);
        CartItem::factory()->create(['product_id' => $product->id, 'user_id' => $userB->id, 'quantity' => 5]);

        // Act
        $availableStock = $product->available_stock;

        // Assert
        $this->assertEquals(12, $availableStock);
    }

    public function test_reserved_quantity_returns_sum_of_cart_items(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 50]);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        CartItem::factory()->create(['product_id' => $product->id, 'user_id' => $userA->id, 'quantity' => 2]);
        CartItem::factory()->create(['product_id' => $product->id, 'user_id' => $userB->id, 'quantity' => 4]);

        // Act
        $reserved = $product->reserved_quantity;

        // Assert
        $this->assertEquals(6, $reserved);
    }

    public function test_decrease_stock_decrements_stock(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 30]);

        // Act
        $product->decreaseStock(7);
        $product->refresh();

        // Assert
        $this->assertEquals(23, $product->stock);
    }

    public function test_scope_active_returns_only_active_products(): void
    {
        // Arrange
        Product::factory()->create(['active' => true, 'name' => 'Active Product']);
        Product::factory()->create(['active' => false, 'name' => 'Inactive Product']);
        Product::factory()->create(['active' => true, 'name' => 'Another Active']);

        // Act
        $activeProducts = Product::active()->get();

        // Assert
        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->every(fn($p) => $p->active === true));
    }
}
