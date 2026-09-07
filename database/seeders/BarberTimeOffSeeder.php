<?php

namespace Database\Seeders;

use App\Models\BarberTimeOff;
use Illuminate\Database\Seeder;

class BarberTimeOffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BarberTimeOff::factory()->create();
    }
}
