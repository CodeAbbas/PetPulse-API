<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $pet_id
 * @property string $issued_by_user_id
 * @property string $jwt_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $first_accessed_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property string|null $accessed_by_ip
 * @property string|null $accessed_by_user_agent
 */
final class EhrToken extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'pet_id',
        'issued_by_user_id',
        'jwt_hash',
        'expires_at',
        'revoked_at',
    ];

    /**
     * Audit-trail fields are deliberately NOT mass-assignable.
     * They are populated server-side at token-decode time only.
     */
    protected $hidden = [
        'jwt_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'first_accessed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /**
     * Returns true if the token is currently usable —
     * not expired, not revoked.
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture();
    }
}