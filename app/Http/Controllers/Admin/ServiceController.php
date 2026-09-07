<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::query()->withCount(['barbers', 'reservations'])->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('admin.services.create', ['service' => new Service]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $this->attributes($request);
        $service = Service::query()->create($data);

        return redirect()->route('admin.services.edit', $service)->with('success', 'خدمت جدید ساخته شد.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($this->attributes($request, $service));

        return back()->with('success', 'خدمت به‌روزرسانی شد.');
    }

    private function attributes(ServiceRequest $request, ?Service $service = null): array
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: ($service?->slug ?? $this->uniqueSlug($data['name']));
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] ??= 0;

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'service';
        $slug = $base;
        $counter = 2;

        while (Service::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
