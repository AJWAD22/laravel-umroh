<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function edit(Request $request): View
    {
        Gate::authorize('system-settings.manage');

        return view('settings.system', [
            'settings' => $this->settings->grouped(),
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated());

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }
}
