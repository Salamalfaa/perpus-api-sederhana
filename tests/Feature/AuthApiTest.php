<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_registration_rejects_duplicate_emails_and_unconfirmed_passwords(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'berbeda123',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_user_can_login_but_not_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);

        $this->postJson('/api/login', ['email' => 'admin@example.com', 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['message', 'token', 'token_type', 'user']);
    }

    public function test_login_is_throttled_after_six_attempts(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/login', ['email' => 'admin@example.com', 'password' => 'salah'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'admin@example.com', 'password' => 'salah'])
            ->assertTooManyRequests();
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logout berhasil']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
