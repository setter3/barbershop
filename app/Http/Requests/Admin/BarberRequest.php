<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BarberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barber = $this->route('barber');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash:ascii', Rule::unique('barbers', 'slug')->ignore($barber)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'slot_duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')],
            'schedules' => ['required', 'array', 'size:7'],
            'schedules.*.is_active' => ['nullable', 'boolean'],
            'schedules.*.starts_at' => ['required', 'date_format:H:i'],
            'schedules.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('schedules', []) as $weekday => $schedule) {
                if (($schedule['is_active'] ?? false) && ($schedule['starts_at'] ?? '') >= ($schedule['ends_at'] ?? '')) {
                    $validator->errors()->add("schedules.$weekday.ends_at", 'ساعت پایان باید بعد از ساعت شروع باشد.');
                }
            }
        }];
    }
}
