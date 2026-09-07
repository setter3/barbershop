<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => ['value' => 'آرشام باربرشاپ', 'type' => 'string', 'is_public' => true],
            'currency' => ['value' => config('barbershop.currency'), 'type' => 'string', 'is_public' => true],
            'base_price' => ['value' => config('barbershop.base_price'), 'type' => 'integer', 'is_public' => true],
            'deposit_percentage' => ['value' => config('barbershop.deposit_percentage'), 'type' => 'integer', 'is_public' => true],
            'slot_duration_minutes' => ['value' => config('barbershop.slot_duration_minutes'), 'type' => 'integer', 'is_public' => false],
            'hold_minutes' => ['value' => config('barbershop.hold_minutes'), 'type' => 'integer', 'is_public' => false],
            'booking_mode' => ['value' => config('barbershop.booking_mode'), 'type' => 'string', 'is_public' => true],
            'contact_phone' => ['value' => '', 'type' => 'string', 'is_public' => true],
            'address' => ['value' => '', 'type' => 'string', 'is_public' => true],
        ];

        foreach ($settings as $key => $attributes) {
            Setting::query()->updateOrCreate(['key' => $key], $attributes);
        }
    }
}
