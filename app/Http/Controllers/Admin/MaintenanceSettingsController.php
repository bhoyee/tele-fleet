<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceSettingsController extends Controller
{
    public function edit(): View
    {
        $target = (int) AppSetting::getValue('maintenance_mileage_target', '5000');

        return view('admin.maintenance-settings', compact('target'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'maintenance_mileage_target' => ['required', 'integer', 'min:1000', 'max:50000'],
        ]);

        AppSetting::setValue('maintenance_mileage_target', (string) $data['maintenance_mileage_target']);

        return redirect()
            ->route('admin.maintenance-settings.edit')
            ->with('success', 'Maintenance target updated.');
    }
}
