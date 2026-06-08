<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string|null $vet_user_id
 * @property string $name
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $city
 * @property string $postcode
 * @property string $country_code
 * @property float $latitude
 * @property float $longitude
 * @property string $phone_e164
 * @property bool $is_emergency_24_7
 * @property float|null $rating
 */
final class Clinic extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'vet_user_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'country_code',
        'latitude',
        'longitude',
        'phone_e164',
        'is_emergency_24_7',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_emergency_24_7' => 'boolean',
            'rating' => 'float',
        ];
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_user_id');
    }
}