<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Registration ───────────────────────────────────────────

    #[Test]
    public function a_user_can_register_and_receives_a_bearer_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Owner',
            'email' => 'new.owner@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'device_name' => 'test-device',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
                'meta' => ['token_type'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new.owner@example.com',
        ]);
    }

    #[Test]
    public function registration_forces_owner_role_even_if_admin_is_requested(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Privilege Escalator',
            'email' => 'attacker@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'admin', // attacker's injection attempt
        ]);

        $response->assertCreated();

        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertSame(UserRole::Owner, $user->role);
    }

    #[Test]
    public function registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrorFor('email');
    }

    #[Test]
    public function registration_rejects_a_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Weak Password',
            'email' => 'weak@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrorFor('password');
    }

    #[Test]
    public function registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'No Confirm',
            'email' => 'noconfirm@example.com',
            'password' => 'Password123',
            // password_confirmation deliberately omitted
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrorFor('password');
    }

    // ─── Login ──────────────────────────────────────────────────

    #[Test]
    public function a_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'valid@example.com',
            'password' => 'Password123',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token'],
                'meta' => ['token_type'],
            ]);
    }

    #[Test]
    public function login_fails_with_an_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'valid@example.com',
            'password' => 'WrongPassword456',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrorFor('email');
    }

    #[Test]
    public function login_does_not_reveal_whether_an_email_exists(): void
    {
        // No user with this email exists.
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'AnyPassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrorFor('email');

        // The error message must be identical to the wrong-password case,
        // not "no account found" — to prevent account enumeration.
        $this->assertStringContainsString(
            'credentials are incorrect',
            $response->json('errors.email.0'),
        );
    }

    // ─── Protected routes & token contract ──────────────────────

    #[Test]
    public function the_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    #[Test]
    public function an_authenticated_user_can_access_the_me_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email);
    }

    #[Test]
    public function a_bearer_token_authenticates_a_request(): void
    {
        $user = User::factory()->create([
            'email' => 'token.user@example.com',
            'password' => 'Password123',
        ]);

        // Login to obtain a real token
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'token.user@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        // Use the token as a bearer credential
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'token.user@example.com');
    }

    #[Test]
    public function logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout.user@example.com',
            'password' => 'Password123',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout.user@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        // 1. Execute the logout request with the current token
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // 2. Force a clean application reboot to clear the feature test container's guard memory
        $this->refreshApplication();

        // 3. Make a fresh request; it must hit the DB, find the token missing, and fail
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    #[Test]
    public function a_token_is_persisted_in_the_database_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'persist@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'persist@example.com',
            'password' => 'Password123',
            'device_name' => 'iPhone-test',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone-test',
        ]);
    }
}