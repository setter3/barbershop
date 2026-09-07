<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash:ascii', Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
