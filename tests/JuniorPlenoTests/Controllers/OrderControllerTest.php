<?php

namespace Tests\JuniorPlenoTests\Controllers;

use App\Models\Order;
use App\Models\Product;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class OrderControllerTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 3.8
     */
    public function test_store_returns_201_for_valid_order(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create([
            'active' => true,
            'stock' => 10,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /**
     * Validates: Requirements 3.9
     */
    public function test_update_status_returns_200_for_admin(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $order = Order::factory()->create();

        // Act
        $response = $this->actingAs($admin, 'sanctum')->patchJson(
            "/api/admin/orders/{$order->id}/status",
            ['status' => 'processing']
        );

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Validates: Requirements 3.10
     */
    public function test_update_status_returns_403_for_non_admin(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order = Order::factory()->create();

        // Act
        $response = $this->actingAs($user, 'sanctum')->patchJson(
            "/api/admin/orders/{$order->id}/status",
            ['status' => 'processing']
        );

        // Assert
        $response->assertStatus(403);
    }
}
