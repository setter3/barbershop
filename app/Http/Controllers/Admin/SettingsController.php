<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::query()->pluck('value', 'key');

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $types = [
            'base_price' => 'integer',
            'slot_duration_minutes' => 'integer',
            'hold_minutes' => 'integer',
        ];
        $private = ['slot_duration_minutes', 'hold_minutes'];

        foreach ($request->validated() as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $value ?? '',
                'type' => $types[$key] ?? 'string',
                'is_public' => ! in_array($key, $private, true),
            ]);
        }

        Setting::query()->updateOrCreate(['key' => 'deposit_percentage'], [
            'value' => '100',
            'type' => 'integer',
            'is_public' => true,
        ]);

        return back()->with('success', 'تنظیمات سایت ذخیره شد.');
    }
}
