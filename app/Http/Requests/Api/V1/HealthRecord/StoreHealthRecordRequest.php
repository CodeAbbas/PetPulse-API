<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HealthRecord;

use App\Enums\HealthRecordType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pet_id' => ['required', 'uuid', Rule::exists('pets', 'id')->whereNull('deleted_at')],
            'record_type' => ['required', new Enum(HealthRecordType::class)],
            'weight_kg' => ['required', 'numeric', 'between:0.1,500.0'],
            'height_cm' => ['nullable', 'numeric', 'between:5.0,250.0'],
            'temperature_c' => ['nullable', 'numeric', 'between:30.0,45.0'],
            'heart_rate_bpm' => ['nullable', 'integer', 'between:20,400'],
            'summary' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // calculated_bmi and bmr_kcal are NEVER accepted from input.
            // They are computed server-side by BiometricCalculator and
            // assigned in the controller, preventing payload injection.
        ];
    }

    /**
     * Map the public 'notes' input to the schema's 'detail' column.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        if (array_key_exists('notes', $validated)) {
            $validated['detail'] = $validated['notes'];
            unset($validated['notes']);
        }

        return $validated;
    }
}