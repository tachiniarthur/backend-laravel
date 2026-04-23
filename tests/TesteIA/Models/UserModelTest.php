<?php

namespace Tests\TesteIA\Models;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Tests\TesteIA\TesteIATestCase;

class UserModelTest extends TesteIATestCase
{
    // ── Relationships ──────────────────────────────────────────────

    public function test_orders_relationship_returnsAssociatedOrders(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(); // another user's order

        // Act
        $orders = $user->orders;

        // Assert
        $this->assertCount(2, $orders);
        $this->assertTrue($orders->contains($order1));
        $this->assertTrue($orders->contains($order2));
    }

    public function test_cartItems_relationship_returnsAssociatedCartItems(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create();
        $cartItem1 = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);
        $cartItem2 = CartItem::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create(); // another user's cart item

        // Act
        $cartItems = $user->cartItems;

        // Assert
        $this->assertCount(2, $cartItems);
        $this->assertTrue($cartItems->contains($cartItem1));
        $this->assertTrue($cartItems->contains($cartItem2));
    }

    // ── Fillable (mass assignment) ─────────────────────────────────

    public function test_fillable_allowsMassAssignmentOfDefinedFields(): void
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'phone' => '555-1234',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ];

        // Act
        $user = User::create($data);

        // Assert
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'phone' => '555-1234',
            'email' => 'john@example.com',
        ]);
    }

    public function test_fillable_preventsNonFillableFieldsFromMassAssignment(): void
    {
        // Arrange & Act
        $user = User::create([
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'is_admin' => true, // not in fillable
        ]);

        // Assert — is_admin is not in $fillable, so mass assignment ignores it;
        // the DB default (false) is applied instead.
        $fresh = $user->fresh();
        $this->assertFalse($fresh->is_admin);
    }

    // ── Casts ──────────────────────────────────────────────────────

    public function test_cast_password_isHashedAutomatically(): void
    {
        // Arrange
        $plainPassword = 'my-secret-password';

        // Act
        $user = User::factory()->create(['password' => $plainPassword]);

        // Assert
        $this->assertNotEquals($plainPassword, $user->getRawOriginal('password'));
        $this->assertTrue(password_verify($plainPassword, $user->getRawOriginal('password')));
    }

    public function test_cast_isAdmin_isBooleanTrue(): void
    {
        // Arrange & Act
        $user = $this->createAdminUser();

        // Assert
        $this->assertIsBool($user->is_admin);
        $this->assertTrue($user->is_admin);
    }

    public function test_cast_isAdmin_isBooleanFalse(): void
    {
        // Arrange & Act
        $user = $this->createRegularUser();

        // Assert
        $this->assertIsBool($user->is_admin);
        $this->assertFalse($user->is_admin);
    }

    // ── Hidden attributes ──────────────────────────────────────────

    public function test_hidden_passwordNotVisibleInSerialization(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $array = $user->toArray();

        // Assert
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_hidden_rememberTokenNotVisibleInSerialization(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $array = $user->toArray();

        // Assert
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_hidden_nonHiddenFieldsAreVisibleInSerialization(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Visible User',
            'email' => 'visible@example.com',
        ]);

        // Act
        $array = $user->toArray();

        // Assert
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertEquals('Visible User', $array['name']);
        $this->assertEquals('visible@example.com', $array['email']);
    }
}
