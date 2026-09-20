<?php

namespace App\Http\Requests\Admin;

use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class TimeOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'string'],
            'starts_time' => ['required', 'date_format:H:i'],
            'ends_on' => ['required', 'string'],
            'ends_time' => ['required', 'date_format:H:i'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        try {
            $this->merge([
                'starts_at' => JalaliDate::parse((string) $this->input('starts_on'), (string) $this->input('starts_time'))->format('Y-m-d H:i:s'),
                'ends_at' => JalaliDate::parse((string) $this->input('ends_on'), (string) $this->input('ends_time'))->format('Y-m-d H:i:s'),
            ]);
        } catch (InvalidArgumentException) {
            // The regular validation response will identify the invalid field.
        }
    }
}
