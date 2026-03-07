<?php

namespace Tests\JuniorPlenoTests\Controllers;

use App\Models\User;
use Tests\JuniorPlenoTests\JuniorPlenoTestCase;

class AuthControllerTest extends JuniorPlenoTestCase
{
    /**
     * Validates: Requirements 3.1
     */
    public function test_login_with_valid_credentials_returns_200(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
    }

    /**
     * Validates: Requirements 3.2
     */
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Validates: Requirements 3.3
     */
    public function test_create_account_with_valid_data_returns_201(): void
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'phone' => '11999999999',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        // Act
        $response = $this->postJson('/api/create-account', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['user', 'token']);
    }
}
