<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    /**
     * Generic message shown when an email belongs to a non-admin OR doesn't
     * exist at all. Same wording either way so we don't leak account existence.
     */
    private const CONTACT_ADMIN_MESSAGE = 'Only admin accounts can reset their password by email. Please contact your system administrator to reset your password.';

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password', [
            'adminEmails' => $this->adminEmails(),
        ]);
    }

    /**
     * Returns admin email addresses for the "contact your administrator"
     * mailto link on the forgot-password page. Internal tool — exposing the
     * IT admin's address is acceptable; users could already find them in any
     * sent email from the app.
     */
    private function adminEmails(): array
    {
        return User::query()
            ->where('role', 'admin')
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    public function sendResetLinkEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! $user->isAdmin()) {
            ActivityLogger::log(
                action: 'password_reset_denied',
                description: "Password reset denied (non-admin or unknown) for {$data['email']}",
                overrides: [
                    'user_id' => null,
                    'user_name' => null,
                    'user_email' => $data['email'],
                ],
            );

            return back()
                ->with('contact_admin', self::CONTACT_ADMIN_MESSAGE)
                ->with('requested_email', $data['email']);
        }

        // Admin: generate a token via Laravel's broker and send our styled email.
        $token = Password::broker()->createToken($user);
        $expireMinutes = (int) config('auth.passwords.users.expire', 60);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        try {
            Mail::to($user->email)->send(new PasswordResetMail(
                user: $user,
                resetUrl: $resetUrl,
                expireMinutes: $expireMinutes,
            ));

            ActivityLogger::log(
                action: 'password_reset_requested',
                description: "Sent password reset link to admin {$user->email}",
                subject: $user,
            );

            return back()
                ->with('reset_sent_to', $user->email)
                ->with('reset_expire_minutes', $expireMinutes);
        } catch (\Throwable $e) {
            Log::warning('Failed to send password reset email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['email' => 'Could not send reset email: ' . $e->getMessage()]);
        }
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ]);

        // Hard guard: even if the token is valid, only allow admins to use it.
        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! $user->isAdmin()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => self::CONTACT_ADMIN_MESSAGE]);
        }

        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            ActivityLogger::log(
                action: 'password_reset',
                description: "Admin {$user->email} reset their password via email link",
                subject: $user,
            );

            return redirect()->route('login')->with('success', 'Password reset. You can sign in with your new password.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($status)]);
    }
}
