<?php

namespace Database\Seeders;

use App\Models\BarberSchedule;
use Illuminate\Database\Seeder;

class BarberScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BarberSchedule::factory()->create();
    }
}
