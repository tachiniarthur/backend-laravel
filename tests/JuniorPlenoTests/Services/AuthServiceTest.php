<?php

namespace Tests\JuniorPlenoTests\Services;

use App\Models\User;
use App\Services\AuthService;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class AuthServiceTest extends JuniorPlenoTestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_login_returns_user_and_token_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create(['password' => 'password123']);

        // Act
        $result = $this->authService->login([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_returns_null_with_invalid_email(): void
    {
        // Arrange - no user created with this email

        // Act
        $result = $this->authService->login([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertNull($result);
    }

    public function test_login_returns_null_with_wrong_password(): void
    {
        // Arrange
        $user = User::factory()->create(['password' => 'correct-password']);

        // Act
        $result = $this->authService->login([
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertNull($result);
    }

    public function test_create_returns_user_and_token_and_persists(): void
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'phone' => '1234567890',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ];

        // Act
        $result = $this->authService->create($data);

        // Assert
        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('John Doe', $result['user']->name);
        $this->assertEquals('john@example.com', $result['user']->email);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }
}
