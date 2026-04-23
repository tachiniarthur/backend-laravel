<?php

namespace Tests\TesteIA;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TesteIATestCase extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => true,
        ], $overrides));
    }

    protected function createRegularUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => false,
        ], $overrides));
    }
}
