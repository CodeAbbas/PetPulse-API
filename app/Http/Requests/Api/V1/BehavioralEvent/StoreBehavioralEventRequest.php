<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\BehavioralEvent;

use App\Enums\BehavioralEventSeverity;
use App\Enums\BehavioralEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreBehavioralEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorisation handled by webhook.secret middleware.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'uuid'],
            'pet_id' => ['required', 'uuid', Rule::exists('pets', 'id')->whereNull('deleted_at')],
            'event_type' => ['required', new Enum(BehavioralEventType::class)],
            'severity' => ['required', new Enum(BehavioralEventSeverity::class)],
            'confidence_score' => ['required', 'numeric', 'between:0,1'],
            'recorded_at' => ['required', 'date'],
        ];
    }
}