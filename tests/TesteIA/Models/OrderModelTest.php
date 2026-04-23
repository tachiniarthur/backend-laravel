<?php

namespace Tests\TesteIA\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Tests\TesteIA\TesteIATestCase;

class OrderModelTest extends TesteIATestCase
{
    // ── Relationships ──────────────────────────────────────────────

    public function test_user_relationship_returnsAssociatedUser(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $relatedUser = $order->user;

        // Assert
        $this->assertNotNull($relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
    }

    public function test_items_relationship_returnsAssociatedOrderItems(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $item1 = OrderItem::factory()->create(['order_id' => $order->id]);
        $item2 = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItem::factory()->create(); // another order's item

        // Act
        $items = $order->items;

        // Assert
        $this->assertCount(2, $items);
        $this->assertTrue($items->contains($item1));
        $this->assertTrue($items->contains($item2));
    }

    // ── Fillable (mass assignment) ─────────────────────────────────

    public function test_fillable_allowsMassAssignmentOfDefinedFields(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $data = [
            'user_id' => $user->id,
            'status' => 'processing',
        ];

        // Act
        $order = Order::create($data);

        // Assert
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'processing',
        ]);
    }

    // ── Accessor: total ────────────────────────────────────────────

    public function test_total_returnsSumOfPriceTimesQuantityForAllItems(): void
    {
        // Arrange
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 10.00,
            'quantity' => 2,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 25.50,
            'quantity' => 3,
        ]);

        // Act
        $total = $order->fresh()->total;

        // Assert — (10.00 * 2) + (25.50 * 3) = 20.00 + 76.50 = 96.50
        $this->assertEqualsWithDelta(96.50, $total, 0.01);
    }

    public function test_total_returnsZeroWhenNoItems(): void
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $total = $order->fresh()->total;

        // Assert
        $this->assertEqualsWithDelta(0.0, $total, 0.01);
    }

    public function test_total_handlesSingleItem(): void
    {
        // Arrange
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 49.99,
            'quantity' => 1,
        ]);

        // Act
        $total = $order->fresh()->total;

        // Assert
        $this->assertEqualsWithDelta(49.99, $total, 0.01);
    }

    // ── Property-Based Test: Total do pedido ───────────────────────

    // Feature: ai-unit-tests, Property 6: Total do pedido é a soma dos itens
    // **Validates: Requirements 2.8**

    #[\PHPUnit\Framework\Attributes\DataProvider('orderTotalProvider')]
    public function test_property_order_total_equals_sum_of_items(array $items): void
    {
        // Arrange
        $order = Order::factory()->create();
        foreach ($items as $item) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'price' => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        // Act
        $total = $order->fresh()->total;

        // Assert
        $expected = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        $this->assertEqualsWithDelta($expected, $total, 0.01);
    }

    public static function orderTotalProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $numItems = random_int(1, 5);
            $items = [];
            for ($j = 0; $j < $numItems; $j++) {
                $items[] = [
                    'price' => round(random_int(100, 100000) / 100, 2),
                    'quantity' => random_int(1, 10),
                ];
            }
            $cases["iteration_{$i}"] = [$items];
        }
        return $cases;
    }
}
