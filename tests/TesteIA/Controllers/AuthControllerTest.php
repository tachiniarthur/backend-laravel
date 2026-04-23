<?php

namespace Tests\TesteIA\Controllers;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TesteIA\TesteIATestCase;

class AuthControllerTest extends TesteIATestCase
{
    // ==========================================
    // POST /api/login
    // ==========================================

    /**
     * Validates: Requirements 7.1
     */
    public function test_login_withValidCredentials_returnsUserAndToken(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.email', $user->email);
        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Validates: Requirements 7.2
     */
    public function test_login_withWrongPassword_returns401(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Credenciais invalidas');
    }

    /**
     * Validates: Requirements 7.2
     */
    public function test_login_withNonexistentEmail_returns401(): void
    {
        // Arrange — no user created

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ]);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Credenciais invalidas');
    }

    /**
     * Validates: Requirements 7.3
     */
    public function test_login_withMissingEmail_returns422(): void
    {
        // Arrange — no email field

        // Act
        $response = $this->postJson('/api/login', [
            'password' => 'secret123',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Validates: Requirements 7.3
     */
    public function test_login_withMissingPassword_returns422(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Validates: Requirements 7.3
     */
    public function test_login_withEmptyPayload_returns422(): void
    {
        // Act
        $response = $this->postJson('/api/login', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    // ==========================================
    // POST /api/create-account
    // ==========================================

    /**
     * Validates: Requirements 7.4
     */
    public function test_createAccount_withValidData_returns201WithUserAndToken(): void
    {
        // Arrange
        $data = [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'phone' => '11999999999',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
        $response->assertJsonPath('user.email', 'jane@example.com');
        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'username' => 'janedoe',
        ]);
    }

    /**
     * Validates: Requirements 7.5
     */
    public function test_createAccount_withDuplicateEmail_returns422(): void
    {
        // Arrange
        User::factory()->create(['email' => 'taken@example.com']);

        $data = [
            'name' => 'Another User',
            'username' => 'anotheruser',
            'phone' => '11888888888',
            'email' => 'taken@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Validates: Requirements 7.6
     */
    public function test_createAccount_withDuplicateUsername_returns422(): void
    {
        // Arrange
        User::factory()->create(['username' => 'takenuser']);

        $data = [
            'name' => 'Another User',
            'username' => 'takenuser',
            'phone' => '11888888888',
            'email' => 'unique@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }

    /**
     * Validates: Requirements 7.7
     */
    public function test_createAccount_withoutPasswordConfirmation_returns422(): void
    {
        // Arrange
        $data = [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'phone' => '11999999999',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Validates: Requirements 7.7
     */
    public function test_createAccount_withMismatchedPasswordConfirmation_returns422(): void
    {
        // Arrange
        $data = [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'phone' => '11999999999',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'different456',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    // ==========================================
    // POST /api/logout
    // ==========================================

    /**
     * Validates: Requirements 7.8
     */
    public function test_logout_authenticated_returns200AndInvalidatesToken(): void
    {
        // Arrange
        $user = $this->createRegularUser();
        $token = $user->createToken('api-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Validates: Requirements 7.8
     */
    public function test_logout_unauthenticated_returns401(): void
    {
        // Act
        $response = $this->postJson('/api/logout');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 7.3 — login with invalid email format returns 422
     */
    public function test_login_withInvalidEmailFormat_returns422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Validates: Requirements 7.7 — create account with short password returns 422
     */
    public function test_createAccount_withShortPassword_returns422(): void
    {
        $response = $this->postJson('/api/create-account', [
            'name' => 'Test User',
            'username' => 'testuser',
            'phone' => '11999999999',
            'email' => 'test@example.com',
            'password' => 'ab',
            'password_confirmation' => 'ab',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Validates: Requirements 7.4 — create account without phone still works (phone is required in validation)
     */
    public function test_createAccount_withMissingRequiredFields_returns422(): void
    {
        $response = $this->postJson('/api/create-account', [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'username', 'phone']);
    }

    /**
     * Validates: Coverage for AuthService::create returning null and AuthController 500 branch
     */
    public function test_createAccount_whenServiceReturnsNull_returns500(): void
    {
        // Arrange — mock AuthService to return null from create()
        $mockService = \Mockery::mock(\App\Services\AuthService::class);
        $mockService->shouldReceive('create')->once()->andReturn(null);
        $this->app->instance(\App\Services\AuthService::class, $mockService);

        // Act
        $response = $this->postJson('/api/create-account', [
            'name' => 'Test User',
            'username' => 'testuser500',
            'phone' => '11999999999',
            'email' => 'test500@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        // Assert
        $response->assertStatus(500);
        $response->assertJsonPath('message', 'Erro ao criar conta');
    }



    // ==========================================
    // Property-Based Tests
    // ==========================================

    // Feature: ai-unit-tests, Property 22: Endpoints de autenticação retornam status HTTP corretos
    // **Validates: Requirements 7.1, 7.2, 7.4, 7.8**

    #[\PHPUnit\Framework\Attributes\DataProvider('authEndpointStatusProvider')]
    public function test_property_authEndpoints_returnCorrectHttpStatus(
        string $scenario,
        string $method,
        string $endpoint,
        array $payload,
        int $expectedStatus,
        bool $createUser,
        ?string $userEmail,
        ?string $userPassword,
        bool $authenticate,
    ): void {
        // Arrange
        $user = null;
        if ($createUser && $userEmail && $userPassword) {
            $user = User::factory()->create([
                'email' => $userEmail,
                'password' => $userPassword,
            ]);
        }

        $request = $this;
        if ($authenticate && $user) {
            $token = $user->createToken('api-token')->plainTextToken;
            $request = $this->withHeader('Authorization', 'Bearer ' . $token);
        }

        // Act
        $response = $request->postJson($endpoint, $payload);

        // Assert
        $response->assertStatus($expectedStatus);
    }

    public static function authEndpointStatusProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 10; $i++) {
            $email = 'prop22_' . $i . '_' . self::randomAlphanumeric(6) . '@example.com';
            $password = self::randomString(6, 30);
            $name = self::randomString(3, 40);
            $username = 'prop22_' . $i . '_' . self::randomAlphanumeric(5);
            $phone = self::randomDigits(7, 15);

            $scenarioIndex = $i % 4;

            switch ($scenarioIndex) {
                case 0:
                    // Login with valid credentials → 200
                    $cases["iteration_{$i}_login_valid"] = [
                        'login with valid credentials',
                        'POST',
                        '/api/login',
                        ['email' => $email, 'password' => $password],
                        200,
                        true,
                        $email,
                        $password,
                        false,
                    ];
                    break;

                case 1:
                    // Login with invalid credentials → 401
                    $cases["iteration_{$i}_login_invalid"] = [
                        'login with invalid credentials',
                        'POST',
                        '/api/login',
                        ['email' => $email, 'password' => $password . '_wrong'],
                        401,
                        true,
                        $email,
                        $password,
                        false,
                    ];
                    break;

                case 2:
                    // Create account with valid data → 201
                    $cases["iteration_{$i}_create_valid"] = [
                        'create account with valid data',
                        'POST',
                        '/api/create-account',
                        [
                            'name' => $name,
                            'username' => $username,
                            'phone' => $phone,
                            'email' => $email,
                            'password' => $password,
                            'password_confirmation' => $password,
                        ],
                        201,
                        false,
                        null,
                        null,
                        false,
                    ];
                    break;

                case 3:
                    // Logout authenticated → 200
                    $cases["iteration_{$i}_logout_auth"] = [
                        'logout authenticated',
                        'POST',
                        '/api/logout',
                        [],
                        200,
                        true,
                        $email,
                        $password,
                        true,
                    ];
                    break;
            }
        }

        return $cases;
    }

    // Feature: ai-unit-tests, Property 23: Validação de unicidade rejeita duplicatas
    // **Validates: Requirements 7.5, 7.6**

    #[\PHPUnit\Framework\Attributes\DataProvider('uniquenessValidationProvider')]
    public function test_property_uniquenessValidation_rejectsDuplicates(
        string $scenario,
        string $existingEmail,
        string $existingUsername,
        string $attemptEmail,
        string $attemptUsername,
        string $expectedErrorField,
    ): void {
        // Arrange — create existing user
        User::factory()->create([
            'email' => $existingEmail,
            'username' => $existingUsername,
        ]);

        $password = self::randomString(6, 20);

        $payload = [
            'name' => self::randomString(3, 40),
            'username' => $attemptUsername,
            'phone' => self::randomDigits(7, 15),
            'email' => $attemptEmail,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        // Act
        $response = $this->postJson('/api/create-account', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$expectedErrorField]);
    }

    public static function uniquenessValidationProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 10; $i++) {
            $existingEmail = 'dup_' . $i . '_' . self::randomAlphanumeric(6) . '@example.com';
            $existingUsername = 'dup_' . $i . '_' . self::randomAlphanumeric(5);

            if ($i % 2 === 0) {
                // Duplicate email
                $uniqueUsername = 'unique_' . $i . '_' . self::randomAlphanumeric(5);
                $cases["iteration_{$i}_duplicate_email"] = [
                    'duplicate email',
                    $existingEmail,
                    $existingUsername,
                    $existingEmail,
                    $uniqueUsername,
                    'email',
                ];
            } else {
                // Duplicate username
                $uniqueEmail = 'unique_' . $i . '_' . self::randomAlphanumeric(6) . '@example.com';
                $cases["iteration_{$i}_duplicate_username"] = [
                    'duplicate username',
                    $existingEmail,
                    $existingUsername,
                    $uniqueEmail,
                    $existingUsername,
                    'username',
                ];
            }
        }

        return $cases;
    }

    // ── Random Data Generators ─────────────────────────────────────

    private static function randomString(int $minLen, int $maxLen): string
    {
        $length = random_int($minLen, $maxLen);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }

    private static function randomAlphanumeric(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
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
