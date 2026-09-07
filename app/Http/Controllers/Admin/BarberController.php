<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BarberRequest;
use App\Models\Barber;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BarberController extends Controller
{
    public function index(): View
    {
        $barbers = Barber::query()
            ->withCount(['services', 'reservations'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.barbers.index', compact('barbers'));
    }

    public function create(): View
    {
        return view('admin.barbers.create', [
            'barber' => new Barber,
            'services' => Service::query()->orderBy('sort_order')->orderBy('name')->get(),
            'schedules' => $this->scheduleRows(),
        ]);
    }

    public function store(BarberRequest $request): RedirectResponse
    {
        $barber = DB::transaction(function () use ($request): Barber {
            $data = $request->safe()->except(['service_ids', 'schedules', 'avatar']);
            $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name']);
            $data['is_active'] = $request->boolean('is_active');
            $data['sort_order'] ??= 0;

            if ($request->hasFile('avatar')) {
                $data['avatar_path'] = $request->file('avatar')->store('barbers', 'public');
            }

            $barber = Barber::query()->create($data);
            $this->syncRelations($barber, $request->validated());

            return $barber;
        });

        return redirect()->route('admin.barbers.edit', $barber)->with('success', 'آرایشگر با موفقیت ساخته شد.');
    }

    public function edit(Barber $barber): View
    {
        $barber->load(['services', 'schedules', 'timeOffs' => fn ($query) => $query->latest('starts_at')]);

        return view('admin.barbers.edit', [
            'barber' => $barber,
            'services' => Service::query()->orderBy('sort_order')->orderBy('name')->get(),
            'schedules' => $this->scheduleRows($barber),
        ]);
    }

    public function update(BarberRequest $request, Barber $barber): RedirectResponse
    {
        DB::transaction(function () use ($request, $barber): void {
            $data = $request->safe()->except(['service_ids', 'schedules', 'avatar']);
            $data['slug'] = $data['slug'] ?: $barber->slug;
            $data['is_active'] = $request->boolean('is_active');
            $data['sort_order'] ??= 0;

            if ($request->hasFile('avatar')) {
                $data['avatar_path'] = $request->file('avatar')->store('barbers', 'public');
            }

            $barber->update($data);
            $this->syncRelations($barber, $request->validated());
        });

        return back()->with('success', 'اطلاعات آرایشگر به‌روزرسانی شد.');
    }

    private function syncRelations(Barber $barber, array $data): void
    {
        $barber->services()->sync($data['service_ids'] ?? []);
        $barber->schedules()->delete();

        foreach ($data['schedules'] as $weekday => $schedule) {
            if (! filter_var($schedule['is_active'] ?? false, FILTER_VALIDATE_BOOL)) {
                continue;
            }

            $barber->schedules()->create([
                'weekday' => $weekday,
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
                'is_active' => true,
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'barber';
        $slug = $base;
        $counter = 2;

        while (Barber::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function scheduleRows(?Barber $barber = null): array
    {
        $existing = $barber?->schedules->keyBy('weekday') ?? collect();

        return collect(range(0, 6))->mapWithKeys(function (int $weekday) use ($existing): array {
            $schedule = $existing->get($weekday);

            return [$weekday => [
                'is_active' => (bool) $schedule,
                'starts_at' => $schedule ? substr((string) $schedule->starts_at, 0, 5) : '10:00',
                'ends_at' => $schedule ? substr((string) $schedule->ends_at, 0, 5) : '21:00',
            ]];
        })->all();
    }
}
