<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            ActivityLogger::log(
                action: 'login',
                description: 'User logged in',
                subject: $request->user(),
            );

            return redirect()->intended('/dashboard');
        }

        ActivityLogger::log(
            action: 'login_failed',
            description: 'Failed login attempt for ' . $credentials['email'],
            overrides: [
                'user_id' => null,
                'user_name' => null,
                'user_email' => $credentials['email'],
            ],
        );

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'logout',
            description: 'User logged out',
            subject: $user,
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
