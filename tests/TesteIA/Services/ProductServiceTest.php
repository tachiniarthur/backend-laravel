<?php

namespace Tests\TesteIA\Services;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TesteIA\TesteIATestCase;

class ProductServiceTest extends TesteIATestCase
{
    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = app(ProductService::class);
    }

    // ── list ───────────────────────────────────────────────────────

    public function test_list_returnsOnlyActiveProducts(): void
    {
        // Arrange
        $active1 = Product::factory()->create(['active' => true]);
        $active2 = Product::factory()->create(['active' => true]);
        Product::factory()->create(['active' => false]);
        Product::factory()->create(['active' => false]);

        // Act
        $result = $this->productService->list();

        // Assert
        $this->assertCount(2, $result);
        $ids = $result->pluck('id')->all();
        $this->assertContains($active1->id, $ids);
        $this->assertContains($active2->id, $ids);
    }

    public function test_list_withNoActiveProducts_returnsEmptyCollection(): void
    {
        // Arrange
        Product::factory()->create(['active' => false]);

        // Act
        $result = $this->productService->list();

        // Assert
        $this->assertCount(0, $result);
    }

    // ── find ───────────────────────────────────────────────────────

    public function test_find_withExistingId_returnsProduct(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $result = $this->productService->find($product->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_find_withNonExistingId_returnsNull(): void
    {
        // Arrange — no products

        // Act
        $result = $this->productService->find(99999);

        // Assert
        $this->assertNull($result);
    }

    // ── create ─────────────────────────────────────────────────────

    public function test_create_withAdminActor_createsProduct(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $data = [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 29.99,
            'stock' => 50,
            'active' => true,
            'image_url' => 'products/test.jpg',
        ];

        // Act
        $product = $this->productService->create($data, $admin);

        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_create_withNonAdminActor_throwsAuthorizationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $data = [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 29.99,
            'stock' => 50,
            'active' => true,
            'image_url' => 'products/test.jpg',
        ];

        // Assert & Act
        $this->expectException(AuthorizationException::class);
        $this->productService->create($data, $user);
    }

    public function test_create_withoutActor_createsProduct(): void
    {
        // Arrange
        $data = [
            'name' => 'No Actor Product',
            'description' => 'Created without actor',
            'price' => 15.00,
            'stock' => 10,
            'active' => true,
            'image_url' => 'products/no-actor.jpg',
        ];

        // Act
        $product = $this->productService->create($data);

        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('No Actor Product', $product->name);
        $this->assertDatabaseHas('products', ['name' => 'No Actor Product']);
    }

    public function test_create_withNonAdminActor_doesNotPersistProduct(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $data = [
            'name' => 'Should Not Exist',
            'description' => 'This product should not be created',
            'price' => 10.00,
            'stock' => 5,
            'active' => true,
            'image_url' => 'products/nope.jpg',
        ];

        // Act
        try {
            $this->productService->create($data, $user);
        } catch (AuthorizationException) {
            // expected
        }

        // Assert
        $this->assertDatabaseMissing('products', ['name' => 'Should Not Exist']);
    }

    // ── update ─────────────────────────────────────────────────────

    public function test_update_withAdminActor_updatesProduct(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $product = Product::factory()->create(['name' => 'Old Name']);

        // Act
        $result = $this->productService->update($product, ['name' => 'New Name'], $admin);

        // Assert
        $this->assertEquals('New Name', $result->name);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_update_withNonAdminActor_throwsAuthorizationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['name' => 'Original']);

        // Assert & Act
        $this->expectException(AuthorizationException::class);
        $this->productService->update($product, ['name' => 'Changed'], $user);
    }

    public function test_update_withNonAdminActor_doesNotModifyProduct(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['name' => 'Original']);

        // Act
        try {
            $this->productService->update($product, ['name' => 'Changed'], $user);
        } catch (AuthorizationException) {
            // expected
        }

        // Assert
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original']);
    }

    public function test_update_withoutActor_updatesProduct(): void
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Old Name']);

        // Act
        $result = $this->productService->update($product, ['name' => 'Updated Name']);

        // Assert
        $this->assertEquals('Updated Name', $result->name);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name']);
    }

    // ── delete ─────────────────────────────────────────────────────

    public function test_delete_withAdminActor_deletesProduct(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $product = Product::factory()->create();

        // Act
        $this->productService->delete($product, $admin);

        // Assert
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_delete_withNonAdminActor_throwsAuthorizationException(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create();

        // Assert & Act
        $this->expectException(AuthorizationException::class);
        $this->productService->delete($product, $user);
    }

    public function test_delete_withNonAdminActor_doesNotDeleteProduct(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create();
        $productId = $product->id;

        // Act
        try {
            $this->productService->delete($product, $user);
        } catch (AuthorizationException) {
            // expected
        }

        // Assert
        $this->assertDatabaseHas('products', ['id' => $productId]);
    }

    public function test_delete_withoutActor_deletesProduct(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $productId = $product->id;

        // Act
        $this->productService->delete($product);

        // Assert
        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    // ── Property-Based Tests ───────────────────────────────────────

    // Feature: ai-unit-tests, Property 20: ProductService.find retorna produto ou null
    // **Validates: Requirements 6.2**

    #[\PHPUnit\Framework\Attributes\DataProvider('findProductProvider')]
    public function test_property_find_returnsProductOrNull(bool $exists, int $idOffset): void
    {
        // Arrange
        if ($exists) {
            $product = Product::factory()->create();
            $searchId = $product->id;
        } else {
            // Use a high offset to ensure the ID doesn't exist
            $maxId = Product::max('id') ?? 0;
            $searchId = $maxId + $idOffset;
        }

        // Act
        $result = $this->productService->find($searchId);

        // Assert
        if ($exists) {
            $this->assertNotNull($result);
            $this->assertInstanceOf(Product::class, $result);
            $this->assertEquals($searchId, $result->id);
        } else {
            $this->assertNull($result);
        }
    }

    public static function findProductProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i % 2 === 0) {
                // Product exists
                $cases["existing_product_{$i}"] = [true, 0];
            } else {
                // Product does not exist — random offset to generate non-existing ID
                $cases["non_existing_product_{$i}"] = [false, random_int(1, 100000)];
            }
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 21: Operações admin-only de ProductService verificam autorização
    // **Validates: Requirements 6.3, 6.4, 6.6, 6.7, 6.8, 6.9**

    #[\PHPUnit\Framework\Attributes\DataProvider('adminOnlyOperationsProvider')]
    public function test_property_adminOnlyOperations_verifyAuthorization(
        string $operation,
        bool $isAdmin
    ): void {
        // Arrange
        $actor = $isAdmin ? $this->createAdminUser() : $this->createRegularUser();

        $productData = [
            'name' => 'Product ' . uniqid(),
            'description' => 'Test description',
            'price' => round(random_int(100, 50000) / 100, 2),
            'stock' => random_int(1, 200),
            'active' => true,
            'image_url' => 'products/test-' . uniqid() . '.jpg',
        ];

        // For update and delete, we need an existing product (created without actor)
        $existingProduct = ($operation === 'update' || $operation === 'delete')
            ? Product::factory()->create()
            : null;

        if ($isAdmin) {
            // Act — admin should succeed
            match ($operation) {
                'create' => $result = $this->productService->create($productData, $actor),
                'update' => $result = $this->productService->update($existingProduct, ['name' => 'Updated ' . uniqid()], $actor),
                'delete' => $this->productService->delete($existingProduct, $actor),
            };

            // Assert — operation succeeded
            match ($operation) {
                'create' => $this->assertDatabaseHas('products', ['name' => $productData['name']]),
                'update' => $this->assertDatabaseHas('products', ['id' => $existingProduct->id, 'name' => $result->name]),
                'delete' => $this->assertDatabaseMissing('products', ['id' => $existingProduct->id]),
            };
        } else {
            // Assert & Act — non-admin should throw AuthorizationException
            $this->expectException(AuthorizationException::class);

            match ($operation) {
                'create' => $this->productService->create($productData, $actor),
                'update' => $this->productService->update($existingProduct, ['name' => 'Should Fail'], $actor),
                'delete' => $this->productService->delete($existingProduct, $actor),
            };
        }
    }

    public static function adminOnlyOperationsProvider(): array
    {
        $operations = ['create', 'update', 'delete'];
        $cases = [];
        for ($i = 0; $i < 12; $i++) {
            $operation = $operations[$i % 3];
            $isAdmin = $i % 2 === 0;
            $role = $isAdmin ? 'admin' : 'non_admin';
            $cases["{$operation}_{$role}_{$i}"] = [$operation, $isAdmin];
        }
        return $cases;
    }
}
