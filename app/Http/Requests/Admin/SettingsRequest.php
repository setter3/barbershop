<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:120'],
            'base_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'hold_minutes' => ['required', 'integer', 'min:3', 'max:120'],
            'booking_mode' => ['required', Rule::in(['manual_confirmation', 'online_deposit'])],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
