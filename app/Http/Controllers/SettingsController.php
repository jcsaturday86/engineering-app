<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value ?? '']);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function users()
    {
        $users = User::with('roles')->latest()->paginate(20);
        return view('settings.users', compact('users'));
    }

    public function createUser()
    {
        $roles = Role::all();
        return view('settings.user-form', ['user' => null, 'roles' => $roles]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'department' => $validated['department'],
            'position' => $validated['position'],
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('settings.users')->with('success', "User {$user->full_name} created. Default password: password123");
    }

    public function editUser(User $user)
    {
        $roles = Role::all();
        return view('settings.user-form', compact('user', 'roles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'role' => 'required|exists:roles,name',
        ]);

        $user->update([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'department' => $validated['department'],
            'position' => $validated['position'],
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('settings.users')->with('success', 'User updated.');
    }

    public function toggleUser(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->full_name} {$status}.");
    }

    public function resetUserPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password reset for {$user->full_name}. New password: password123");
    }

    public function roles()
    {
        $roles = Role::with('permissions')->get();
        return view('settings.roles', compact('roles'));
    }

    public function fees()
    {
        return app(\App\Http\Controllers\FeeScheduleController::class)->index();
    }

    public function signatories()
    {
        $signatories = Signatory::all();
        return view('settings.signatories', compact('signatories'));
    }

    public function updateSignatory(Request $request, Signatory $signatory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:50',
        ]);

        $signatory->update($validated);

        return back()->with('success', 'Signatory updated.');
    }
}
