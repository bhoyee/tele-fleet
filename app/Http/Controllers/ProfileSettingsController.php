<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.settings', [
            'appName' => AppSetting::getValue('app_name', config('app.name', 'Tele-Fleet')),
            'orgName' => AppSetting::getValue('org_name', 'Lagos Island State Administration'),
            'orgAddress' => AppSetting::getValue('org_address', '17B, Awolowo Road, Ikoyi, Lagos'),
            'supportEmail' => AppSetting::getValue('support_email'),
            'logoPath' => AppSetting::getValue('app_logo_path'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'org_name' => ['nullable', 'string', 'max:120'],
            'org_address' => ['nullable', 'string', 'max:160'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        AppSetting::setValue('app_name', $validated['app_name']);
        AppSetting::setValue('org_name', $validated['org_name'] ?? null);
        AppSetting::setValue('org_address', $validated['org_address'] ?? null);
        AppSetting::setValue('support_email', $validated['support_email'] ?? null);

        if ($request->hasFile('logo')) {
            $old = AppSetting::getValue('app_logo_path');
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'logo-' . Str::random(12) . '.' . $extension;

            $targetDir = public_path('branding');
            File::ensureDirectoryExists($targetDir);

            $file->move($targetDir, $filename);
            $path = 'branding/' . $filename;
            AppSetting::setValue('app_logo_path', $path);

            if (is_string($old) && $old !== '' && $old !== $path) {
                $oldPublic = public_path(str_replace('\\', '/', $old));
                if (str_starts_with(str_replace('\\', '/', $old), 'branding/') && File::exists($oldPublic)) {
                    File::delete($oldPublic);
                }
            }
        }

        return back()->with('success', 'App settings updated.');
    }
}
