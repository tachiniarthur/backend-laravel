<?php

namespace Tests\JuniorPlenoTests\Services;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class CartServiceTest extends JuniorPlenoTestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    public function test_add_item_creates_cart_item_for_active_product(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true, 'stock' => 10]);

        // Act
        $cartItem = $this->cartService->addItem($user, $product->id, 2);

        // Assert
        $this->assertNotNull($cartItem);
        $this->assertEquals($product->id, $cartItem->product_id);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals($user->id, $cartItem->user_id);
    }

    public function test_add_item_throws_exception_for_inactive_product(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => false, 'stock' => 10]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 1);
    }

    public function test_add_item_throws_exception_when_stock_exceeded(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true, 'stock' => 2]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->cartService->addItem($user, $product->id, 5);
    }
}
