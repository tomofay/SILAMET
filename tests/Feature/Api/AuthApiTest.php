<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_nis_nip_returns_token(): void
    {
        $user = User::factory()->create([
            'nis_nip' => '1234567890',
            'role' => 'siswa',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'nis_nip' => '1234567890',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'nis_nip', 'role'],
                ],
            ])
            ->assertJsonPath('data.user.nis_nip', '1234567890')
            ->assertJsonPath('data.user.role', 'siswa');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_with_unknown_nis_nip_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nis_nip' => '9999999999',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_login_without_nis_nip_fails_validation(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('nis_nip');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $token = $user->createToken('kiosk-test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
