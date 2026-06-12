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
 * @property float|null $height_cm
 * @property float|null $bmi
 * @property float|null $bmr_kcal
 * @property float|null $temperature_c
 * @property int|null $heart_rate_bpm
 * @property string $summary
 * @property string|null $detail
 * @property \Illuminate\Support\Carbon $recorded_at
 */
final class HealthRecord extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'pet_id',
        'recorded_by_user_id',
        'record_type',
        'weight_kg',
        'height_cm',
        'bmi',
        'bmr_kcal',
        'temperature_c',
        'heart_rate_bpm',
        'summary',
        'detail',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'record_type' => HealthRecordType::class,
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'bmi' => 'float',
            'bmr_kcal' => 'float',
            'temperature_c' => 'float',
            'heart_rate_bpm' => 'integer',
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