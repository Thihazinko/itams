<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Lets the currently-authenticated user manage THEIR OWN account:
 * name, email, password, avatar. Does NOT expose role or module
 * permissions — those remain admin-only via UserController.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(6)],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'current_password.required_with'   => 'Enter your current password to set a new one.',
        ]);

        $changes = [];

        if ($data['name'] !== $user->name) {
            $user->name = $data['name'];
            $changes[] = 'name';
        }

        if ($data['email'] !== $user->email) {
            $user->email = $data['email'];
            $changes[] = 'email';
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $changes[] = 'password';
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $changes[] = 'avatar';
        }

        if (empty($changes)) {
            return back()->with('warning', 'No changes were made.');
        }

        $user->save();

        ActivityLogger::log(
            action: 'profile_updated',
            description: "Updated own profile",
            subject: $user,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('profile.edit')->with('success', 'Profile updated.');
    }
}
