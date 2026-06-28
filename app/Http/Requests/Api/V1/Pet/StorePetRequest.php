<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Pet;

use App\Enums\PetSex;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'species' => ['required', Rule::enum(PetSpecies::class)],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', Rule::enum(PetSex::class)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'microchip_number' => ['nullable', 'string', 'max:20', 'unique:pets,microchip_number'],
        ];
    }
}