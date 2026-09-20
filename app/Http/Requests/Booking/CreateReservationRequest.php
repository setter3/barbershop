<?php

namespace App\Http\Requests\Booking;

use App\Services\Booking\MobileNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barber_id' => ['required', 'integer', Rule::exists('barbers', 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date', 'after:now'],
            'service_ids' => ['required', 'array', 'min:1', 'max:20'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'full_name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'regex:/^\+989\d{9}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge([
                'mobile' => app(MobileNormalizer::class)->normalize((string) $this->input('mobile')),
            ]);
        }
    }
}
