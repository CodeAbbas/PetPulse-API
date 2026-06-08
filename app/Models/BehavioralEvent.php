<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BehavioralEventSeverity;
use App\Enums\BehavioralEventType;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $pet_id
 * @property BehavioralEventType $event_type
 * @property BehavioralEventSeverity $severity
 * @property float $confidence_score
 * @property string|null $zone_name
 * @property int|null $duration_seconds
 * @property string|null $snapshot_path
 * @property bool $is_read
 * @property bool $owner_notified
 * @property \Illuminate\Support\Carbon $logged_at
 */
final class BehavioralEvent extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'id',
        'pet_id',
        'event_type',
        'severity',
        'confidence_score',
        'zone_name',
        'duration_seconds',
        'snapshot_path',
        'is_read',
        'owner_notified',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => BehavioralEventType::class,
            'severity' => BehavioralEventSeverity::class,
            'confidence_score' => 'float',
            'duration_seconds' => 'integer',
            'is_read' => 'boolean',
            'owner_notified' => 'boolean',
            'logged_at' => 'datetime',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}