<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\UserRole;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_a_uuid_v4_primary_key_on_creation(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($user->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $user->id,
            'Primary key must be a valid UUID v4',
        );
    }

    #[Test]
    public function it_hashes_the_password_on_assignment(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext_password_12345',
        ]);

        $this->assertNotSame('plaintext_password_12345', $user->password);
        $this->assertStringStartsWith('$argon2id$', $user->password);
    }

    #[Test]
    public function it_hides_the_password_from_json_serialisation(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $json = $user->toJson();

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('argon2id', $json);
    }

    #[Test]
    public function role_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'admin', // attacker's payload
        ]);

        $this->assertSame(UserRole::Owner, $user->role);
    }

    #[Test]
    public function it_defaults_to_owner_role(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertSame(UserRole::Owner, $user->role);
        $this->assertTrue($user->isOwner());
        $this->assertFalse($user->isVet());
        $this->assertFalse($user->isAdmin());
    }

    #[Test]
    public function it_has_pets_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertCount(0, $user->pets);

        Pet::create([
            'owner_user_id' => $user->id,
            'name' => 'Test Pet',
            'species' => 'dog',
        ]);

        $this->assertCount(1, $user->fresh()->pets);
    }
}