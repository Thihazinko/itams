<?php

namespace App\Http\Controllers;

use App\Mail\UserCredentialsMail;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const PERM_FIELDS = [
        'can_view_pc_assets',
        'can_edit_pc_assets',
        'can_view_subscriptions',
        'can_edit_subscriptions',
        'can_view_licenses_contracts',
        'can_edit_licenses_contracts',
        'can_view_devices',
        'can_edit_devices',
        'can_view_email_master',
        'can_edit_email_master',
    ];

    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $kpis = [
            'total'  => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'users'  => User::where('role', 'user')->count(),
        ];

        return view('users.index', compact('users', 'kpis'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role' => 'required|in:admin,user',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
        foreach (self::PERM_FIELDS as $field) {
            $rules[$field] = 'sometimes|boolean';
        }

        $data = $request->validate($rules);

        // Capture the plain-text password before User::create hashes it via the model cast,
        // so we can include it in the welcome email.
        $plainPassword = $data['password'];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $this->applyPermissions($request, $data);

        $user = User::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created user {$user->name} ({$user->email}) with role {$user->role}",
            subject: $user,
        );

        $emailStatus = $this->sendCredentialsEmail($user, $plainPassword);

        $flash = $emailStatus === true
            ? "User created. Login details sent to {$user->email}."
            : 'User created, but the welcome email could not be sent: ' . $emailStatus;

        return redirect()->route('users.index')->with($emailStatus === true ? 'success' : 'warning', $flash);
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => ['nullable', Password::min(6)],
            'role' => 'required|in:admin,user',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
        foreach (self::PERM_FIELDS as $field) {
            $rules[$field] = 'sometimes|boolean';
        }

        $data = $request->validate($rules);

        $this->applyPermissions($request, $data);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $original = $user->only(array_keys($data));
        $user->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->reject(fn ($v, $k) => $k === 'password')
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated user {$user->name} ({$user->email})",
            subject: $user,
            properties: ['changed_fields' => $changes, 'password_changed' => isset($data['password'])],
        );

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $label = "{$user->name} ({$user->email})";

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted user {$label}",
            subject: $user,
        );

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    /**
     * Attempt to email login credentials to the newly-created user.
     * Returns true on success, or the error message string on failure.
     */
    private function sendCredentialsEmail(User $user, string $plainPassword): bool|string
    {
        try {
            Mail::to($user->email)->send(new UserCredentialsMail(
                user: $user,
                plainPassword: $plainPassword,
                loginUrl: route('login'),
            ));

            ActivityLogger::log(
                action: 'mail_sent',
                description: "Sent welcome email with login details to {$user->email}",
                subject: $user,
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to send user credentials email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return $e->getMessage();
        }
    }

    private function applyPermissions(Request $request, array &$data): void
    {
        foreach (array_keys(User::MODULES) as $module) {
            $editKey = "can_edit_{$module}";
            $viewKey = "can_view_{$module}";
            $edit = $request->boolean($editKey);
            $view = $request->boolean($viewKey) || $edit;
            $data[$viewKey] = $view;
            $data[$editKey] = $edit;
        }
    }
}
