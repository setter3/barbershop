<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Setting::query()->updateOrCreate(['key' => 'base_price'], ['value' => '2500000', 'type' => 'integer']);
            Setting::query()->updateOrCreate(['key' => 'deposit_percentage'], ['value' => '30', 'type' => 'integer']);

            $services = collect([
                ['slug' => 'beard-design', 'name' => 'طراحی و اصلاح ریش', 'description' => 'فرم‌دهی حرفه‌ای متناسب با چهره', 'price_amount' => 900000, 'duration_minutes' => 20],
                ['slug' => 'face-cleanup', 'name' => 'پاکسازی صورت', 'description' => 'پاکسازی و آبرسانی پوست', 'price_amount' => 1400000, 'duration_minutes' => 30],
                ['slug' => 'hair-styling', 'name' => 'استایل مو', 'description' => 'حالت‌دهی نهایی با محصولات حرفه‌ای', 'price_amount' => 600000, 'duration_minutes' => 15],
            ])->map(fn (array $service): Service => Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                [...$service, 'is_active' => true],
            ));

            $barbers = [
                ['slug' => 'arsham', 'name' => 'آرشام', 'bio' => 'متخصص فید، طراحی ریش و استایل کلاسیک'],
                ['slug' => 'saman', 'name' => 'سامان', 'bio' => 'متخصص استایل مدرن و فرم‌دهی متناسب با چهره'],
            ];

            foreach ($barbers as $index => $attributes) {
                $barber = Barber::query()->updateOrCreate(
                    ['slug' => $attributes['slug']],
                    [...$attributes, 'slot_duration_minutes' => 40, 'is_active' => true, 'sort_order' => $index],
                );
                $barber->services()->sync($services->pluck('id')->all());

                foreach ([6, 0, 1, 2, 3, 4] as $weekday) {
                    BarberSchedule::query()->updateOrCreate(
                        [
                            'barber_id' => $barber->getKey(),
                            'weekday' => $weekday,
                            'starts_at' => '10:00:00',
                            'ends_at' => '21:00:00',
                        ],
                        ['is_active' => true],
                    );
                }
            }
        });
    }
}
