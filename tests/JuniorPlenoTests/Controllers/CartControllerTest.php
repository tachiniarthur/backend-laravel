<?php

namespace Tests\JuniorPlenoTests\Controllers;

use App\Models\Product;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class CartControllerTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 3.7
     */
    public function test_store_returns_200_for_authenticated_user(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create([
            'active' => true,
            'stock' => 10,
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'item']);
    }
}
