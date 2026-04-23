<?php

namespace Tests\TesteIA\Models;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Tests\TesteIA\TesteIATestCase;

class CartItemModelTest extends TesteIATestCase
{
    // ── Relationships ──────────────────────────────────────────────

    public function test_user_relationship_returnsAssociatedUser(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $cartItem = CartItem::factory()->create(['user_id' => $user->id]);

        // Act
        $relatedUser = $cartItem->user;

        // Assert
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
    }

    public function test_product_relationship_returnsAssociatedProduct(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create(['product_id' => $product->id]);

        // Act
        $relatedProduct = $cartItem->product;

        // Assert
        $this->assertInstanceOf(Product::class, $relatedProduct);
        $this->assertEquals($product->id, $relatedProduct->id);
    }

    // ── Fillable (mass assignment) ─────────────────────────────────

    public function test_fillable_allowsMassAssignmentOfDefinedFields(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create();
        $data = [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ];

        // Act
        $cartItem = CartItem::create($data);

        // Assert
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    // ── Casts ──────────────────────────────────────────────────────

    public function test_cast_quantity_isInteger(): void
    {
        // Arrange & Act
        $cartItem = CartItem::factory()->create(['quantity' => 3]);

        // Assert
        $fresh = $cartItem->fresh();
        $this->assertIsInt($fresh->quantity);
        $this->assertSame(3, $fresh->quantity);
    }
}
