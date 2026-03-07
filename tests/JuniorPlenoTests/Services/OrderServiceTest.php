<?php

namespace Tests\JuniorPlenoTests\Services;

use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class OrderServiceTest extends JuniorPlenoTestCase
{
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    public function test_create_from_cart_creates_order_with_pending_status(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true, 'stock' => 10]);
        $items = [['product_id' => $product->id, 'quantity' => 3]];

        // Act
        $order = $this->orderService->createFromCart($user, $items);

        // Assert
        $this->assertEquals('pending', $order->status);
        $this->assertCount(1, $order->items);
        $product->refresh();
        $this->assertEquals(7, $product->stock);
    }

    public function test_create_from_cart_throws_exception_for_insufficient_stock(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true, 'stock' => 2]);
        $items = [['product_id' => $product->id, 'quantity' => 5]];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->orderService->createFromCart($user, $items);
    }
}
