<?php

namespace Tests\TesteIA\Models;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Tests\TesteIA\TesteIATestCase;

class ModelAttributesPropertyTest extends TesteIATestCase
{
    // ── Property-Based Test: Relationships ──────────────────────────

    // Feature: ai-unit-tests, Property 1: Relacionamentos de Models retornam registros corretos
    // **Validates: Requirements 2.1**

    #[\PHPUnit\Framework\Attributes\DataProvider('relationshipProvider')]
    public function test_property_relationships_returnCorrectRecords(string $relationship, int $relatedCount): void
    {
        match ($relationship) {
            'User→orders' => $this->assertHasManyRelationship(
                User::factory()->create(),
                'orders',
                Order::class,
                'user_id',
                $relatedCount
            ),
            'User→cartItems' => $this->assertHasManyRelationship(
                User::factory()->create(),
                'cartItems',
                CartItem::class,
                'user_id',
                $relatedCount
            ),
            'Product→cartItems' => $this->assertHasManyRelationship(
                Product::factory()->create(),
                'cartItems',
                CartItem::class,
                'product_id',
                $relatedCount
            ),
            'Product→orderItems' => $this->assertHasManyRelationship(
                Product::factory()->create(),
                'orderItems',
                OrderItem::class,
                'product_id',
                $relatedCount
            ),
            'Order→user' => $this->assertBelongsToRelationship(
                fn() => Order::factory()->create(),
                'user',
                User::class
            ),
            'Order→items' => $this->assertHasManyRelationship(
                Order::factory()->create(),
                'items',
                OrderItem::class,
                'order_id',
                $relatedCount
            ),
            'OrderItem→order' => $this->assertBelongsToRelationship(
                fn() => OrderItem::factory()->create(),
                'order',
                Order::class
            ),
            'OrderItem→product' => $this->assertBelongsToRelationship(
                fn() => OrderItem::factory()->create(),
                'product',
                Product::class
            ),
            'CartItem→user' => $this->assertBelongsToRelationship(
                fn() => CartItem::factory()->create(),
                'user',
                User::class
            ),
            'CartItem→product' => $this->assertBelongsToRelationship(
                fn() => CartItem::factory()->create(),
                'product',
                Product::class
            ),
        };
    }

    private function assertHasManyRelationship(
        mixed $parent,
        string $relationName,
        string $relatedClass,
        string $foreignKey,
        int $count
    ): void {
        // Create related records for this parent
        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $created[] = $relatedClass::factory()->create([$foreignKey => $parent->id]);
        }

        // Create a decoy record not belonging to this parent
        $relatedClass::factory()->create();

        // Act
        $result = $parent->fresh()->{$relationName};

        // Assert
        $this->assertCount($count, $result);
        foreach ($created as $record) {
            $this->assertTrue($result->contains('id', $record->id));
        }
    }

    private function assertBelongsToRelationship(
        \Closure $factory,
        string $relationName,
        string $relatedClass
    ): void {
        // Arrange
        $child = $factory();

        // Act
        $related = $child->{$relationName};

        // Assert
        $this->assertNotNull($related);
        $this->assertInstanceOf($relatedClass, $related);
    }

    public static function relationshipProvider(): array
    {
        $relationships = [
            'User→orders',
            'User→cartItems',
            'Product→cartItems',
            'Product→orderItems',
            'Order→user',
            'Order→items',
            'OrderItem→order',
            'OrderItem→product',
            'CartItem→user',
            'CartItem→product',
        ];

        $hasManyRelationships = [
            'User→orders',
            'User→cartItems',
            'Product→cartItems',
            'Product→orderItems',
            'Order→items',
        ];

        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $rel = $relationships[array_rand($relationships)];
            $count = in_array($rel, $hasManyRelationships) ? random_int(1, 5) : 1;
            $cases["iteration_{$i}_{$rel}"] = [$rel, $count];
        }
        return $cases;
    }

    // ── Property-Based Test: Attribute Configuration ────────────────

    // Feature: ai-unit-tests, Property 2: Configuração de atributos dos Models é consistente
    // **Validates: Requirements 2.2, 2.3, 2.9**

    #[\PHPUnit\Framework\Attributes\DataProvider('attributeConfigurationProvider')]
    public function test_property_attributeConfiguration_isConsistent(string $modelClass, array $data): void
    {
        match ($modelClass) {
            User::class => $this->assertUserAttributeConfiguration($data),
            Product::class => $this->assertProductAttributeConfiguration($data),
            Order::class => $this->assertOrderAttributeConfiguration($data),
            OrderItem::class => $this->assertOrderItemAttributeConfiguration($data),
            CartItem::class => $this->assertCartItemAttributeConfiguration($data),
        };
    }

    private function assertUserAttributeConfiguration(array $data): void
    {
        // Test fillable - mass assignment works
        $user = User::factory()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        $fresh = $user->fresh();

        // Fillable fields are persisted
        $this->assertEquals($data['name'], $fresh->name);
        $this->assertEquals($data['email'], $fresh->email);
        $this->assertEquals($data['username'], $fresh->username);
        $this->assertEquals($data['phone'], $fresh->phone);

        // Casts: is_admin is boolean
        $this->assertIsBool($fresh->is_admin);

        // Casts: password is hashed (not stored as plain text)
        $this->assertNotEquals($data['password'], $fresh->getAttributes()['password']);

        // Hidden: password and remember_token not in serialization
        $serialized = $fresh->toArray();
        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('remember_token', $serialized);
    }

    private function assertProductAttributeConfiguration(array $data): void
    {
        $product = Product::factory()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'active' => $data['active'],
            'image_url' => $data['image_url'],
        ]);

        $fresh = $product->fresh();

        // Fillable fields are persisted
        $this->assertEquals($data['name'], $fresh->name);
        $this->assertEquals($data['description'], $fresh->description);
        $this->assertEquals($data['image_url'], $fresh->image_url);

        // Casts: price is decimal:2 (string)
        $this->assertIsString($fresh->price);
        $this->assertEquals(number_format($data['price'], 2, '.', ''), $fresh->price);

        // Casts: stock is integer
        $this->assertIsInt($fresh->stock);
        $this->assertSame($data['stock'], $fresh->stock);

        // Casts: active is boolean
        $this->assertIsBool($fresh->active);
        $this->assertSame($data['active'], $fresh->active);
    }

    private function assertOrderAttributeConfiguration(array $data): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $data['status'],
        ]);

        $fresh = $order->fresh();

        // Fillable fields are persisted
        $this->assertEquals($user->id, $fresh->user_id);
        $this->assertEquals($data['status'], $fresh->status);
    }

    private function assertOrderItemAttributeConfiguration(array $data): void
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
            'price' => $data['price'],
        ]);

        $fresh = $orderItem->fresh();

        // Fillable fields are persisted
        $this->assertEquals($order->id, $fresh->order_id);
        $this->assertEquals($product->id, $fresh->product_id);

        // Casts: price is decimal:2 (string)
        $this->assertIsString($fresh->price);
        $this->assertEquals(number_format($data['price'], 2, '.', ''), $fresh->price);

        // Casts: quantity is integer
        $this->assertIsInt($fresh->quantity);
        $this->assertSame($data['quantity'], $fresh->quantity);
    }

    private function assertCartItemAttributeConfiguration(array $data): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
        ]);

        $fresh = $cartItem->fresh();

        // Fillable fields are persisted
        $this->assertEquals($user->id, $fresh->user_id);
        $this->assertEquals($product->id, $fresh->product_id);

        // Casts: quantity is integer
        $this->assertIsInt($fresh->quantity);
        $this->assertSame($data['quantity'], $fresh->quantity);
    }

    public static function attributeConfigurationProvider(): array
    {
        $models = [
            User::class,
            Product::class,
            Order::class,
            OrderItem::class,
            CartItem::class,
        ];

        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $modelClass = $models[array_rand($models)];

            $data = match ($modelClass) {
                User::class => [
                    'name' => 'User_' . $i . '_' . bin2hex(random_bytes(4)),
                    'email' => 'user_' . $i . '_' . bin2hex(random_bytes(4)) . '@test.com',
                    'username' => 'usr_' . $i . '_' . bin2hex(random_bytes(4)),
                    'phone' => '+1' . str_pad((string) random_int(1000000000, 9999999999), 10, '0'),
                    'password' => 'Pass_' . bin2hex(random_bytes(6)),
                ],
                Product::class => [
                    'name' => 'Product_' . $i . '_' . bin2hex(random_bytes(3)),
                    'description' => 'Description for product ' . $i,
                    'price' => round(random_int(100, 99999) / 100, 2),
                    'stock' => random_int(0, 1000),
                    'active' => (bool) random_int(0, 1),
                    'image_url' => 'https://example.com/img_' . $i . '.jpg',
                ],
                Order::class => [
                    'status' => $statuses[array_rand($statuses)],
                ],
                OrderItem::class => [
                    'quantity' => random_int(1, 100),
                    'price' => round(random_int(100, 99999) / 100, 2),
                ],
                CartItem::class => [
                    'quantity' => random_int(1, 100),
                ],
            };

            $cases["iteration_{$i}_{$modelClass}"] = [$modelClass, $data];
        }
        return $cases;
    }
}
