<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_homepage_uses_the_approved_rtl_foundation(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('lang="fa"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('استایل شما');
    }

    public function test_default_business_settings_are_seeded(): void
    {
        $this->seed(SettingSeeder::class);

        $deposit = Setting::query()->where('key', 'deposit_percentage')->firstOrFail();

        $this->assertSame(30, $deposit->typedValue());
        $this->assertDatabaseHas('settings', ['key' => 'currency', 'value' => 'IRR']);
    }
}
