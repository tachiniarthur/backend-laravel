<?php

namespace Tests\TesteIA\Controllers;

use App\Models\User;
use Tests\TesteIA\TesteIATestCase;

class UserControllerTest extends TesteIATestCase
{
    // ==========================================
    // GET /api/user
    // ==========================================

    /**
     * Validates: Requirements 11.1
     */
    public function test_show_authenticated_returns200WithUserData(): void
    {
        // Arrange
        $user = $this->createRegularUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('id', $user->id);
        $response->assertJsonPath('name', 'John Doe');
        $response->assertJsonPath('email', 'john@example.com');
    }

    /**
     * Validates: Requirements 11.2
     */
    public function test_show_unauthenticated_returns401(): void
    {
        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // PUT /api/user
    // ==========================================

    /**
     * Validates: Requirements 11.3
     */
    public function test_update_withValidName_returns200WithUpdatedData(): void
    {
        // Arrange
        $user = $this->createRegularUser(['name' => 'Old Name']);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['name' => 'New Name']);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('name', 'New Name');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Validates: Requirements 11.3
     */
    public function test_update_withValidPhone_returns200WithUpdatedData(): void
    {
        // Arrange
        $user = $this->createRegularUser(['phone' => '11999999999']);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['phone' => '11888888888']);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('phone', '11888888888');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '11888888888',
        ]);
    }

    /**
     * Validates: Requirements 11.3
     */
    public function test_update_withNullPhone_returns200(): void
    {
        // Arrange
        $user = $this->createRegularUser(['phone' => '11999999999']);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['phone' => null]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('phone', null);
    }

    /**
     * Validates: Requirements 11.4
     */
    public function test_update_withShortName_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['name' => 'AB']);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Validates: Requirements 11.4
     */
    public function test_update_withEmptyName_returns422(): void
    {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['name' => '']);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Validates: Requirements 11.2 — update unauthenticated returns 401
     */
    public function test_update_unauthenticated_returns401(): void
    {
        $response = $this->putJson('/api/user', ['name' => 'Test']);

        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 11.3 — update with name and phone together
     */
    public function test_update_withNameAndPhone_returns200WithBothUpdated(): void
    {
        $user = $this->createRegularUser(['name' => 'Old', 'phone' => '111']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['name' => 'New Name', 'phone' => '222']);

        $response->assertStatus(200);
        $response->assertJsonPath('name', 'New Name');
        $response->assertJsonPath('phone', '222');
    }

    /**
     * Validates: Requirements 11.4 — update with name too long returns 422
     */
    public function test_update_withNameTooLong_returns422(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['name' => str_repeat('a', 256)]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Validates: Requirements 11.4 — update with phone too long returns 422
     */
    public function test_update_withPhoneTooLong_returns422(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['phone' => str_repeat('1', 21)]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    /**
     * Validates: Requirements 11.1 — show returns user without hidden fields
     */
    public function test_show_authenticated_doesNotExposePassword(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonMissingPath('password');
        $response->assertJsonMissingPath('remember_token');
    }

    /**
     * Validates: Requirements 11.3 — update with empty payload returns 200 (no changes)
     */
    public function test_update_withEmptyPayload_returns200NoChanges(): void
    {
        $user = $this->createRegularUser(['name' => 'Original']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', []);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Original']);
    }


    // ==========================================
    // Property-Based Tests
    // ==========================================

    // Feature: ai-unit-tests, Property 31: Endpoints de perfil do usuário retornam status corretos
    // **Validates: Requirements 11.1, 11.3**

    #[\PHPUnit\Framework\Attributes\DataProvider('userProfileEndpointProvider')]
    public function test_property_userProfileEndpoints_returnCorrectStatus(
        string $scenario,
        string $method,
        string $endpoint,
        ?array $payload,
        int $expectedStatus,
        bool $authenticate,
    ): void {
        // Arrange
        $user = $this->createRegularUser();

        // Act
        $request = $authenticate
            ? $this->actingAs($user, 'sanctum')
            : $this;

        $response = $method === 'GET'
            ? $request->getJson($endpoint)
            : $request->putJson($endpoint, $payload ?? []);

        // Assert
        $response->assertStatus($expectedStatus);

        if ($authenticate && $method === 'GET' && $expectedStatus === 200) {
            $response->assertJsonPath('id', $user->id);
        }

        if ($authenticate && $method === 'PUT' && $expectedStatus === 200 && isset($payload['name'])) {
            $response->assertJsonPath('name', $payload['name']);
        }
    }

    public static function userProfileEndpointProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 10; $i++) {
            $scenarioIndex = $i % 3;

            switch ($scenarioIndex) {
                case 0:
                    // GET /api/user authenticated → 200
                    $cases["iteration_{$i}_get_user_auth"] = [
                        'GET /api/user authenticated',
                        'GET',
                        '/api/user',
                        null,
                        200,
                        true,
                    ];
                    break;

                case 1:
                    // PUT /api/user with valid name → 200
                    $name = self::randomString(3, 40);
                    $cases["iteration_{$i}_put_user_valid"] = [
                        'PUT /api/user with valid data',
                        'PUT',
                        '/api/user',
                        ['name' => $name],
                        200,
                        true,
                    ];
                    break;

                case 2:
                    // PUT /api/user with valid name and phone → 200
                    $name = self::randomString(3, 40);
                    $phone = self::randomDigits(7, 15);
                    $cases["iteration_{$i}_put_user_name_phone"] = [
                        'PUT /api/user with name and phone',
                        'PUT',
                        '/api/user',
                        ['name' => $name, 'phone' => $phone],
                        200,
                        true,
                    ];
                    break;
            }
        }

        return $cases;
    }

    // ── Random Data Generators ─────────────────────────────────────

    private static function randomString(int $minLen, int $maxLen): string
    {
        $length = random_int($minLen, $maxLen);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }

    private static function randomDigits(int $minLen, int $maxLen): string
    {
        $length = random_int($minLen, $maxLen);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= (string) random_int(0, 9);
        }
        return $result;
    }
}
