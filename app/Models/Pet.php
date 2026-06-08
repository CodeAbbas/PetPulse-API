<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetSex;
use App\Enums\PetSpecies;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $owner_user_id
 * @property string $name
 * @property PetSpecies $species
 * @property string|null $breed
 * @property PetSex $sex
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string|null $microchip_number
 * @property float|null $current_weight_kg
 * @property float|null $current_bmi
 * @property float|null $current_bmr_kcal
 */
final class Pet extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'name',
        'species',
        'breed',
        'sex',
        'date_of_birth',
        'microchip_number',
        'current_weight_kg',
        'current_bmi',
        'current_bmr_kcal',
    ];

    protected function casts(): array
    {
        return [
            'species' => PetSpecies::class,
            'sex' => PetSex::class,
            'date_of_birth' => 'date',
            'current_weight_kg' => 'float',
            'current_bmi' => 'float',
            'current_bmr_kcal' => 'float',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class)->orderByDesc('recorded_at');
    }

    public function behavioralEvents(): HasMany
    {
        return $this->hasMany(BehavioralEvent::class)->orderByDesc('logged_at');
    }

    public function ehrTokens(): HasMany
    {
        return $this->hasMany(EhrToken::class);
    }

    /**
     * Age in years, computed from date_of_birth.
     */
    public function getAgeYearsAttribute(): ?int
    {
        return $this->date_of_birth?->diffInYears(now());
    }
}