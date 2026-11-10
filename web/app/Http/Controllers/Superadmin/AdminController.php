<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private function authorizeSuperadmin(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->status === 'active', 403);
        $slug = DB::table('roles')->where('id', $user->role_id)->value('slug');
        abort_unless($slug === 'superadmin', 403);
    }

    public function create()
    {
        $this->authorizeSuperadmin();
        return view('superadmin.admins.create');
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'alpha_dash', 'max:80', 'unique:users,username'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if (!$adminRoleId) {
            throw ValidationException::withMessages(['name' => 'Admin role is missing from the roles table.']);
        }

        DB::table('users')->insert([
            'role_id' => $adminRoleId,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('superadmin.admins.index')
            ->with('success', 'Administrator account created successfully.');
    }

    public function toggleStatus(Request $request, int $admin)
    {
        $this->authorizeSuperadmin();

        $record = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.slug', 'admin')
            ->where('users.id', $admin)
            ->whereNull('users.deleted_at')
            ->select('users.id', 'users.name', 'users.status')
            ->first();

        abort_unless($record, 404, 'Administrator not found.');
        abort_unless(in_array($record->status, ['active', 'inactive'], true), 422, 'Suspended accounts cannot be toggled.');

        $next = $record->status === 'active' ? 'inactive' : 'active';
        DB::table('users')->where('id', $record->id)->update([
            'status' => $next,
            'updated_at' => now(),
        ]);

        return redirect()->route('superadmin.admins.index')
            ->with('success', $record->name . ' is now ' . $next . '.');
    }
    public function destroy(int $admin)
    {
        $this->authorizeSuperadmin();

        $record = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.slug', 'admin')
            ->where('users.id', $admin)
            ->whereNull('users.deleted_at')
            ->select('users.id', 'users.name')
            ->first();

        abort_unless($record, 404, 'Administrator not found.');

        DB::table('users')->where('id', $record->id)->update([
            'deleted_at' => now(),
            'status' => 'inactive',
            'updated_at' => now(),
        ]);

        return redirect()->route('superadmin.admins.index')
            ->with('success', $record->name . ' was deleted successfully.');
    }
}
