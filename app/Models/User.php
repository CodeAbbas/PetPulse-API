<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property float|null $latitude
 * @property float|null $longitude
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasUuidPrimaryKey;
    use Notifiable;

    /**
     * The model's default values for attributes.
     * * Ensures an in-memory instance matches schema-level fallbacks before persistence.
     */
    protected $attributes = [
        'role' => 'owner',
    ];

    /**
     * Mass-assignable attributes.
     *
     * Note: `role` is deliberately omitted — role assignment is a
     * privileged operation handled via dedicated admin endpoints, never
     * via owner-facing registration or profile-update requests.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'latitude',
        'longitude',
    ];

    /**
     * Attributes hidden from JSON serialisation.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute type casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Pets owned by this user.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'owner_user_id');
    }

    /**
     * Clinic associated with this user (vet role only; null otherwise).
     */
    public function clinic(): HasOne
    {
        return $this->hasOne(Clinic::class, 'vet_user_id');
    }

    /**
     * Health records authored by this user (vet role typically).
     */
    public function recordedHealthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class, 'recorded_by_user_id');
    }

    /**
     * EHR tokens issued by this user (owner role typically).
     */
    public function issuedEhrTokens(): HasMany
    {
        return $this->hasMany(EhrToken::class, 'issued_by_user_id');
    }

    /**
     * Role-check helpers.
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isVet(): bool
    {
        return $this->role === UserRole::Vet;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}