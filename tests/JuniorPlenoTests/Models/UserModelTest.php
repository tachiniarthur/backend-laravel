<?php

namespace Tests\JuniorPlenoTests\Models;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class UserModelTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 4.6
     */
    public function test_user_has_orders_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);
        Order::factory()->create(); // order belonging to another user

        // Act
        $orders = $user->orders;

        // Assert
        $this->assertCount(3, $orders);
        $this->assertTrue($orders->every(fn($order) => $order->user_id === $user->id));
    }

    /**
     * Validates: Requirements 4.6
     */
    public function test_user_has_cart_items_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();
        CartItem::factory()->count(2)->create(['user_id' => $user->id]);
        CartItem::factory()->create(); // cart item belonging to another user

        // Act
        $cartItems = $user->cartItems;

        // Assert
        $this->assertCount(2, $cartItems);
        $this->assertTrue($cartItems->every(fn($item) => $item->user_id === $user->id));
    }
}
