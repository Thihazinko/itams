<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role' => 'required|in:admin,user',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'can_pc_assets' => 'sometimes|boolean',
            'can_subscriptions' => 'sometimes|boolean',
            'can_licenses_contracts' => 'sometimes|boolean',
            'can_devices' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['can_pc_assets'] = $request->boolean('can_pc_assets');
        $data['can_subscriptions'] = $request->boolean('can_subscriptions');
        $data['can_licenses_contracts'] = $request->boolean('can_licenses_contracts');
        $data['can_devices'] = $request->boolean('can_devices');

        $user = User::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created user {$user->name} ({$user->email}) with role {$user->role}",
            subject: $user,
        );

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => ['nullable', Password::min(6)],
            'role' => 'required|in:admin,user',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'can_pc_assets' => 'sometimes|boolean',
            'can_subscriptions' => 'sometimes|boolean',
            'can_licenses_contracts' => 'sometimes|boolean',
            'can_devices' => 'sometimes|boolean',
        ]);

        $data['can_pc_assets'] = $request->boolean('can_pc_assets');
        $data['can_subscriptions'] = $request->boolean('can_subscriptions');
        $data['can_licenses_contracts'] = $request->boolean('can_licenses_contracts');
        $data['can_devices'] = $request->boolean('can_devices');

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
}
