<?php

namespace Tests\JuniorPlenoTests\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class OrderModelTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 4.5
     */
    public function test_total_returns_sum_of_price_times_quantity(): void
    {
        // Arrange
        $order = Order::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 25.50,
            'quantity' => 2,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 10.00,
            'quantity' => 3,
        ]);

        // Act
        $total = $order->total;

        // Assert
        $expectedTotal = (25.50 * 2) + (10.00 * 3); // 51.00 + 30.00 = 81.00
        $this->assertEquals($expectedTotal, $total);
    }
}
