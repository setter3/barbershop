<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReservationStatus::class)],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
