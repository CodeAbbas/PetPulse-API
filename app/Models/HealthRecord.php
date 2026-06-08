<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HealthRecordType;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $pet_id
 * @property string|null $recorded_by_user_id
 * @property HealthRecordType $record_type
 * @property float|null $weight_kg
 * @property float|null $bmi
 * @property float|null $bmr_kcal
 * @property string $summary
 * @property string|null $detail
 * @property \Illuminate\Support\Carbon $recorded_at
 */
final class HealthRecord extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'pet_id',
        'recorded_by_user_id',
        'record_type',
        'weight_kg',
        'bmi',
        'bmr_kcal',
        'summary',
        'detail',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'record_type' => HealthRecordType::class,
            'weight_kg' => 'float',
            'bmi' => 'float',
            'bmr_kcal' => 'float',
            'recorded_at' => 'date',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}