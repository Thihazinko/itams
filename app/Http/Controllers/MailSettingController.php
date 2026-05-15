<?php

namespace App\Http\Controllers;

use App\Models\MailSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailSettingController extends Controller
{
    public function edit()
    {
        $settings = MailSetting::current();
        return view('mail_settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mailer' => 'required|string|in:smtp,log,sendmail',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'auth_mode' => 'nullable|in:plain,login,cram-md5',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reminder_recipients' => 'nullable|string|max:2000',
            'reminder_days_before' => 'required|integer|min:1|max:365',
            'enabled' => 'sometimes|boolean',
        ]);

        if (! empty($data['reminder_recipients'])) {
            $emails = preg_split('/[\s,;]+/', $data['reminder_recipients']);
            foreach ($emails as $email) {
                $email = trim($email);
                if ($email === '') continue;
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return back()
                        ->withInput()
                        ->withErrors(['reminder_recipients' => "Invalid email address: {$email}"]);
                }
            }
        }

        $settings = MailSetting::current();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['enabled'] = $request->boolean('enabled');

        $original = $settings->only(array_keys($data));
        $settings->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->reject(fn ($v, $k) => $k === 'password')
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: 'Updated mail settings',
            subject: $settings,
            properties: ['changed_fields' => $changes, 'password_changed' => isset($data['password'])],
        );

        return redirect()->route('mail-settings.edit')->with('success', 'Mail settings saved.');
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $settings = MailSetting::current();

        if ($settings->enabled) {
            config([
                'mail.default' => $settings->mailer ?: 'smtp',
                'mail.mailers.smtp.host' => $settings->host,
                'mail.mailers.smtp.port' => $settings->port,
                'mail.mailers.smtp.encryption' => $settings->encryption,
                'mail.mailers.smtp.auth_mode' => $settings->auth_mode,
                'mail.mailers.smtp.username' => $settings->username,
                'mail.mailers.smtp.password' => $settings->password,
                'mail.from.address' => $settings->from_address ?: config('mail.from.address'),
                'mail.from.name' => $settings->from_name ?: config('mail.from.name'),
            ]);

            Mail::purge($settings->mailer ?: 'smtp');
            Mail::purge('smtp');
        }

        try {
            Mail::raw(
                "This is a test email from your ITAMS application.\n\nIf you received this, your SMTP configuration is working correctly.\n\nSent at: " . now()->toDateTimeString(),
                function ($message) use ($request) {
                    $message->to($request->test_email)
                            ->subject('[ITAMS] Test Email');
                }
            );

            ActivityLogger::log(
                action: 'mail_test',
                description: "Sent test email to {$request->test_email}",
            );

            return back()->with('success', "Test email sent to {$request->test_email}. Check inbox (or storage/logs/laravel.log if using log driver).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }
}
