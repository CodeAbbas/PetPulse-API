<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Pet;

use App\Enums\PetSex;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route-level auth:sanctum handles authentication.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:80'],
            'species' => ['sometimes', Rule::enum(PetSpecies::class)],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', Rule::enum(PetSex::class)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'microchip_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('pets', 'microchip_number')->ignore($this->route('pet')),
            ],
        ];
    }
}