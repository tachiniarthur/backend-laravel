<?php

namespace Tests\JuniorPlenoTests\Services;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class ProductServiceTest extends JuniorPlenoTestCase
{
    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
    }

    public function test_create_product_with_admin_user(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $data = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'price' => 29.99,
            'stock' => 50,
            'image_url' => 'https://example.com/image.jpg',
            'active' => true,
        ];

        // Act
        $product = $this->productService->create($data, $admin);

        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertTrue($product->exists);
        $this->assertEquals('Test Product', $product->name);
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_create_product_without_admin_throws_exception(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $data = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'price' => 29.99,
            'stock' => 50,
            'image_url' => 'https://example.com/image.jpg',
            'active' => true,
        ];

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->productService->create($data, $user);
    }

    public function test_list_returns_only_active_products(): void
    {
        // Arrange
        Product::factory()->count(3)->create(['active' => true]);
        Product::factory()->count(2)->create(['active' => false]);

        // Act
        $products = $this->productService->list();

        // Assert
        $this->assertCount(3, $products);
        $products->each(fn($product) => $this->assertTrue($product->active));
    }
}
