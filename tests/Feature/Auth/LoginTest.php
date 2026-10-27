<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_with_valid_credentials_returns_a_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'member@example.com')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_with_invalid_credentials_does_not_create_a_token(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Kredensial tidak valid.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
