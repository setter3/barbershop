<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);

        $admin = config('barbershop.admin_seed');

        if ($admin['name'] && $admin['email'] && $admin['password']) {
            User::query()->updateOrCreate(
                ['email' => $admin['email']],
                ['name' => $admin['name'], 'password' => $admin['password'], 'is_admin' => true],
            );
        }
    }
}
