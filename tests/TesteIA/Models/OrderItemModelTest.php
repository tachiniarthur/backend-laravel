<?php

namespace Tests\TesteIA\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Tests\TesteIA\TesteIATestCase;

class OrderItemModelTest extends TesteIATestCase
{
    // ── Relationships ──────────────────────────────────────────────

    public function test_order_relationship_returnsAssociatedOrder(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        // Act
        $relatedOrder = $orderItem->order;

        // Assert
        $this->assertInstanceOf(Order::class, $relatedOrder);
        $this->assertEquals($order->id, $relatedOrder->id);
    }

    public function test_product_relationship_returnsAssociatedProduct(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

        // Act
        $relatedProduct = $orderItem->product;

        // Assert
        $this->assertInstanceOf(Product::class, $relatedProduct);
        $this->assertEquals($product->id, $relatedProduct->id);
    }

    // ── Fillable (mass assignment) ─────────────────────────────────

    public function test_fillable_allowsMassAssignmentOfDefinedFields(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $data = [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 49.99,
        ];

        // Act
        $orderItem = OrderItem::create($data);

        // Assert
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    // ── Casts ──────────────────────────────────────────────────────

    public function test_cast_price_isDecimalWithTwoPlaces(): void
    {
        // Arrange & Act
        $orderItem = OrderItem::factory()->create(['price' => 19.5]);

        // Assert
        $fresh = $orderItem->fresh();
        $this->assertIsString($fresh->price);
        $this->assertEquals('19.50', $fresh->price);
    }

    public function test_cast_quantity_isInteger(): void
    {
        // Arrange & Act
        $orderItem = OrderItem::factory()->create(['quantity' => 7]);

        // Assert
        $fresh = $orderItem->fresh();
        $this->assertIsInt($fresh->quantity);
        $this->assertSame(7, $fresh->quantity);
    }
}
