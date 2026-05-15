<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function edit()
    {
        $settings = collect(NotificationSetting::MODULES)
            ->mapWithKeys(fn ($label, $module) => [$module => NotificationSetting::forModule($module)])
            ->all();

        return view('notification_settings.edit', compact('settings'));
    }

    public function update(Request $request, string $module)
    {
        if (! array_key_exists($module, NotificationSetting::MODULES)) {
            abort(404);
        }

        $data = $request->validate([
            'enabled'     => 'sometimes|boolean',
            'days_before' => 'required|integer|min:1|max:365',
            'recipients'  => 'nullable|string|max:2000',
        ]);

        if (! empty($data['recipients'])) {
            foreach (preg_split('/[\s,;]+/', $data['recipients']) as $email) {
                $email = trim($email);
                if ($email === '') continue;
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return back()
                        ->withInput()
                        ->withErrors(["recipients_{$module}" => "Invalid email address: {$email}"])
                        ->with('active_tab', $module);
                }
            }
        }

        $data['enabled'] = $request->boolean('enabled');

        $setting = NotificationSetting::forModule($module);
        $original = $setting->only(array_keys($data));
        $setting->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: 'Updated notification settings for ' . (NotificationSetting::MODULES[$module]),
            subject: $setting,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('notification-settings.edit')
            ->with('success', NotificationSetting::MODULES[$module] . ' notification settings saved.')
            ->with('active_tab', $module);
    }
}
