<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EhrToken;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EhrTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function is_active_returns_true_for_a_fresh_token(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $token = EhrToken::create([
            'pet_id' => $pet->id,
            'issued_by_user_id' => $owner->id,
            'jwt_hash' => hash('sha256', 'test_jwt'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue($token->isActive());
    }

    #[Test]
    public function is_active_returns_false_for_an_expired_token(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $token = EhrToken::create([
            'pet_id' => $pet->id,
            'issued_by_user_id' => $owner->id,
            'jwt_hash' => hash('sha256', 'expired_jwt'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($token->isActive());
    }

    #[Test]
    public function is_active_returns_false_for_a_revoked_token(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $token = EhrToken::create([
            'pet_id' => $pet->id,
            'issued_by_user_id' => $owner->id,
            'jwt_hash' => hash('sha256', 'revoked_jwt'),
            'expires_at' => now()->addMinutes(15),
        ]);
        $token->revoked_at = now();
        $token->save();

        $this->assertFalse($token->isActive());
    }

    #[Test]
    public function jwt_hash_is_hidden_from_serialisation(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $token = EhrToken::create([
            'pet_id' => $pet->id,
            'issued_by_user_id' => $owner->id,
            'jwt_hash' => hash('sha256', 'secret'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $json = $token->toJson();

        $this->assertStringNotContainsString('jwt_hash', $json);
        $this->assertStringNotContainsString(hash('sha256', 'secret'), $json);
    }
}