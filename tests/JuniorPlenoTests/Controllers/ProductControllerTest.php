<?php

namespace Tests\JuniorPlenoTests\Controllers;

use App\Models\Product;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class ProductControllerTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 3.4
     */
    public function test_index_returns_200_with_active_products(): void
    {
        // Arrange
        $active1 = Product::factory()->create(['active' => true]);
        $active2 = Product::factory()->create(['active' => true]);
        Product::factory()->create(['active' => false]);

        // Act
        $response = $this->getJson('/api/products');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['products']);
        $response->assertJsonCount(2, 'products');
    }

    /**
     * Validates: Requirements 3.5
     */
    public function test_show_returns_200_for_existing_product(): void
    {
        // Arrange
        $product = Product::factory()->create(['active' => true]);

        // Act
        $response = $this->getJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['product']);
        $response->assertJsonPath('product.id', $product->id);
    }

    /**
     * Validates: Requirements 3.6
     */
    public function test_show_returns_404_for_nonexistent_product(): void
    {
        // Act
        $response = $this->getJson('/api/products/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Produto nao encontrado.']);
    }
}
