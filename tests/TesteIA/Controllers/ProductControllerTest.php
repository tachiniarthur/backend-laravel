<?php

namespace Tests\TesteIA\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TesteIA\TesteIATestCase;

class ProductControllerTest extends TesteIATestCase
{
    // ==========================================
    // GET /api/products
    // ==========================================

    /**
     * Validates: Requirements 10.1
     */
    public function test_index_returnsActiveProductsWithAvailableStock(): void
    {
        // Arrange
        $activeProduct = Product::factory()->create(['stock' => 10, 'active' => true]);
        $inactiveProduct = Product::factory()->create(['stock' => 5, 'active' => false]);
        $user = $this->createRegularUser();
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $activeProduct->id, 'quantity' => 3]);

        // Act
        $response = $this->getJson('/api/products');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'products');
        $response->assertJsonPath('products.0.id', $activeProduct->id);
        $response->assertJsonPath('products.0.available_stock', 7);
    }

    // ==========================================
    // GET /api/products/{id}
    // ==========================================

    /**
     * Validates: Requirements 10.2
     */
    public function test_show_existingProduct_returns200WithAvailableStock(): void
    {
        // Arrange
        $product = Product::factory()->create(['stock' => 20, 'active' => true]);
        $user = $this->createRegularUser();
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);

        // Act
        $response = $this->getJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('product.id', $product->id);
        $response->assertJsonPath('product.available_stock', 15);
    }

    /**
     * Validates: Requirements 10.3
     */
    public function test_show_nonExistingProduct_returns404(): void
    {
        // Act
        $response = $this->getJson('/api/products/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Produto nao encontrado.');
    }

    // ==========================================
    // POST /api/products (store)
    // ==========================================

    /**
     * Validates: Requirements 10.4
     */
    public function test_store_asAdmin_withValidDataAndImage_returns201(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'description' => 'A valid description',
                'price' => 29.99,
                'stock' => 50,
                'image' => UploadedFile::fake()->image('product.jpg'),
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('name', 'Test Product');
        $this->assertNotNull($response->json('image_url'));
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
        Storage::disk('public')->assertExists('products/' . basename(parse_url($response->json('image_url'), PHP_URL_PATH)));
    }

    /**
     * Validates: Requirements 10.5
     */
    public function test_store_asNonAdmin_returns403(): void
    {
        // Arrange
        Storage::fake('public');
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'description' => 'A valid description',
                'price' => 29.99,
                'stock' => 50,
                'image' => UploadedFile::fake()->image('product.jpg'),
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Validates: Requirements 10.10
     */
    public function test_store_withInvalidData_returns422(): void
    {
        // Arrange
        $admin = $this->createAdminUser();

        // Act — name too short, price negative, no image
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'ab',
                'description' => 'ok',
                'price' => -5,
                'stock' => 10,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'description', 'price', 'image']);
    }

    // ==========================================
    // POST /api/products/{id} (update)
    // ==========================================

    /**
     * Validates: Requirements 10.6
     */
    public function test_update_asAdmin_withValidData_returns200(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();
        $product = Product::factory()->create(['active' => true]);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/products/{$product->id}", [
                'name' => 'Updated Name',
                'price' => 99.99,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('name', 'Updated Name');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name']);
    }

    /**
     * Validates: Requirements 10.7
     */
    public function test_update_asAdmin_withNewImage_replacesOldImage(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();

        // Create product with initial image
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = Product::factory()->create([
            'active' => true,
            'image_url' => url('storage/' . $oldPath),
        ]);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/products/{$product->id}", [
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]);

        // Assert
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($oldPath);
        $newImageUrl = $response->json('image_url');
        $this->assertNotNull($newImageUrl);
        $newPath = 'products/' . basename(parse_url($newImageUrl, PHP_URL_PATH));
        Storage::disk('public')->assertExists($newPath);
    }

    // ==========================================
    // DELETE /api/products/{id}
    // ==========================================

    /**
     * Validates: Requirements 10.8
     */
    public function test_destroy_asAdmin_returns200AndRemovesProductAndImage(): void
    {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();

        $imagePath = UploadedFile::fake()->image('product.jpg')->store('products', 'public');
        $product = Product::factory()->create([
            'active' => true,
            'image_url' => url('storage/' . $imagePath),
        ]);

        // Act
        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Produto removido.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    /**
     * Validates: Requirements 10.9
     */
    public function test_destroy_asNonAdmin_returns403(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /**
     * Validates: Requirements 10.6 — update non-admin returns 403
     */
    public function test_update_asNonAdmin_returns403(): void
    {
        Storage::fake('public');
        $user = $this->createRegularUser();
        $product = Product::factory()->create(['active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}", ['name' => 'Hacked']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
    }

    /**
     * Validates: Requirements 10.3 — update non-existing product returns 404
     */
    public function test_update_nonExistingProduct_returns404(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products/99999', ['name' => 'Ghost']);

        $response->assertStatus(404);
    }

    /**
     * Validates: Requirements 10.3 — delete non-existing product returns 404
     */
    public function test_destroy_nonExistingProduct_returns404(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/products/99999');

        $response->assertStatus(404);
    }

    /**
     * Validates: Requirements 10.4 — store without image when product has no image_url
     */
    public function test_update_asAdmin_withoutImage_keepsExistingImage(): void
    {
        Storage::fake('public');
        $admin = $this->createAdminUser();
        $product = Product::factory()->create(['active' => true, 'image_url' => 'http://example.com/old.jpg']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/products/{$product->id}", ['name' => 'Updated No Image']);

        $response->assertStatus(200);
        $response->assertJsonPath('name', 'Updated No Image');
    }

    /**
     * Validates: Requirements 10.8 — delete product without image_url
     */
    public function test_destroy_asAdmin_productWithoutImage_returns200(): void
    {
        Storage::fake('public');
        $admin = $this->createAdminUser();
        $product = Product::factory()->create(['active' => true, 'image_url' => '']);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /**
     * Validates: Requirements 10.4 — store product with active field
     */
    public function test_store_asAdmin_withActiveField_returns201(): void
    {
        Storage::fake('public');
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Active Product',
                'description' => 'A valid description',
                'price' => 10.00,
                'stock' => 5,
                'active' => false,
                'image' => UploadedFile::fake()->image('product.jpg'),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Active Product']);
    }


    // ==========================================
    // Property-Based Tests
    // ==========================================

    // Feature: ai-unit-tests, Property 28: Endpoints de produtos retornam dados com available_stock
    // **Validates: Requirements 10.1, 10.2**

    #[\PHPUnit\Framework\Attributes\DataProvider('productAvailableStockProvider')]
    public function test_property_productEndpoints_returnDataWithAvailableStock(
        int $stock,
        int $reservedQty,
    ): void {
        // Arrange
        $product = Product::factory()->create(['stock' => $stock, 'active' => true]);
        if ($reservedQty > 0) {
            $otherUser = $this->createRegularUser();
            CartItem::factory()->create([
                'user_id' => $otherUser->id,
                'product_id' => $product->id,
                'quantity' => $reservedQty,
            ]);
        }
        $expectedAvailableStock = max(0, $stock - $reservedQty);

        // Act & Assert — GET /api/products returns available_stock
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $products = collect($response->json('products'));
        $found = $products->firstWhere('id', $product->id);
        $this->assertNotNull($found);
        $this->assertEquals($expectedAvailableStock, $found['available_stock']);

        // Act & Assert — GET /api/products/{id} returns available_stock
        $response = $this->getJson("/api/products/{$product->id}");
        $response->assertStatus(200);
        $this->assertEquals($expectedAvailableStock, $response->json('product.available_stock'));
    }

    public static function productAvailableStockProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $stock = random_int(0, 100);
            $reserved = random_int(0, $stock);
            $cases["stock={$stock}_reserved={$reserved}"] = [$stock, $reserved];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 29: CRUD de produtos via API verifica autorização admin
    // **Validates: Requirements 10.4, 10.5, 10.6, 10.8, 10.9**

    #[\PHPUnit\Framework\Attributes\DataProvider('productCrudAuthorizationProvider')]
    public function test_property_productCrud_verifiesAdminAuthorization(
        string $productName,
        string $description,
        float $price,
        int $stock,
    ): void {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();
        $regularUser = $this->createRegularUser();

        // Act & Assert — Admin POST /api/products returns 201
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => $productName,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image' => UploadedFile::fake()->image('img.jpg'),
            ]);
        $response->assertStatus(201);
        $productId = $response->json('id');

        // Act & Assert — Non-admin POST /api/products returns 403
        $response = $this->actingAs($regularUser, 'sanctum')
            ->postJson('/api/products', [
                'name' => $productName . ' v2',
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image' => UploadedFile::fake()->image('img2.jpg'),
            ]);
        $response->assertStatus(403);

        // Act & Assert — Admin POST /api/products/{id} (update) returns 200
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/products/{$productId}", [
                'name' => $productName . ' Updated',
            ]);
        $response->assertStatus(200);
        $response->assertJsonPath('name', $productName . ' Updated');

        // Act & Assert — Admin DELETE /api/products/{id} returns 200
        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$productId}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $productId]);

        // Act & Assert — Non-admin DELETE /api/products/{id} returns 403
        $anotherProduct = Product::factory()->create(['active' => true]);
        $response = $this->actingAs($regularUser, 'sanctum')
            ->deleteJson("/api/products/{$anotherProduct->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $anotherProduct->id]);
    }

    public static function productCrudAuthorizationProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $name = 'Product ' . bin2hex(random_bytes(4));
            $desc = 'Description ' . bin2hex(random_bytes(6));
            $price = round(random_int(100, 99999) / 100, 2);
            $stock = random_int(1, 500);
            $cases["iteration_{$i}"] = [$name, $desc, $price, $stock];
        }
        return $cases;
    }

    // Feature: ai-unit-tests, Property 30: Upload de imagem é gerenciado corretamente no ciclo de vida do produto
    // **Validates: Requirements 10.7, 10.8**

    #[\PHPUnit\Framework\Attributes\DataProvider('productImageLifecycleProvider')]
    public function test_property_imageUpload_managedCorrectlyInProductLifecycle(
        string $productName,
        float $price,
        int $stock,
    ): void {
        // Arrange
        Storage::fake('public');
        $admin = $this->createAdminUser();

        // Act — Create product with image
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => $productName,
                'description' => 'Lifecycle test description',
                'price' => $price,
                'stock' => $stock,
                'image' => UploadedFile::fake()->image('create.jpg'),
            ]);
        $response->assertStatus(201);
        $productId = $response->json('id');
        $createdImageUrl = $response->json('image_url');
        $this->assertNotNull($createdImageUrl);
        $createdImagePath = 'products/' . basename(parse_url($createdImageUrl, PHP_URL_PATH));
        Storage::disk('public')->assertExists($createdImagePath);

        // Act — Update product with new image (old should be removed)
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/products/{$productId}", [
                'image' => UploadedFile::fake()->image('update.jpg'),
            ]);
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($createdImagePath);
        $updatedImageUrl = $response->json('image_url');
        $this->assertNotNull($updatedImageUrl);
        $updatedImagePath = 'products/' . basename(parse_url($updatedImageUrl, PHP_URL_PATH));
        Storage::disk('public')->assertExists($updatedImagePath);

        // Act — Delete product (image should be removed)
        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$productId}");
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($updatedImagePath);
        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public static function productImageLifecycleProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $name = 'ImgProduct ' . bin2hex(random_bytes(4));
            $price = round(random_int(100, 99999) / 100, 2);
            $stock = random_int(1, 200);
            $cases["iteration_{$i}"] = [$name, $price, $stock];
        }
        return $cases;
    }
}
