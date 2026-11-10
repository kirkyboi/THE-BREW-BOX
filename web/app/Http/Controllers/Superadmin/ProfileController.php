<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    private function authorizedUser(): object
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active', 403, 'Your account is not active.');
        $role = DB::table('roles')->where('id', $user->role_id)->value('slug');
        abort_unless($role === 'superadmin', 403, 'Superadmin access required.');
        return $user;
    }

    public function index(): View
    {
        $this->authorizedUser();
        return view('superadmin.profile.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->authorizedUser();
        $id = $user->getAuthIdentifier();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
        ];
        if (Schema::hasColumn('users', 'username')) {
            $updates['username'] = trim($data['username']);
        }
        $photoColumn = Schema::hasColumn('users', 'profile_photo') ? 'profile_photo'
            : (Schema::hasColumn('users', 'avatar') ? 'avatar' : null);
        $oldPhoto = $photoColumn ? $user->{$photoColumn} : null;
        $newPhoto = null;
        if ($request->hasFile('profile_photo')) {
            abort_unless($photoColumn, 422, 'The users table needs a profile_photo column.');
            $newPhoto = $request->file('profile_photo')->store('profiles', 'public');
            $updates[$photoColumn] = $newPhoto;
        } elseif ($request->boolean('remove_photo') && $photoColumn) {
            $updates[$photoColumn] = null;
        }
        if (Schema::hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }
        try {
            DB::table('users')->where('id', $id)->update($updates);
        } catch (\Throwable $exception) {
            if ($newPhoto) Storage::disk('public')->delete($newPhoto);
            throw $exception;
        }
        if ($photoColumn && array_key_exists($photoColumn, $updates) && $oldPhoto && $oldPhoto !== $newPhoto) {
            $normalized = preg_replace('#^(?:/?storage/|/?public/)#', '', (string) $oldPhoto);
            if ($normalized && !str_contains($normalized, '://')) {
                Storage::disk('public')->delete($normalized);
            }
        }
        return redirect()->route('superadmin.profile.index')->with('success', 'Profile information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->authorizedUser();
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        DB::table('users')->where('id', $user->getAuthIdentifier())->update([
            'password' => Hash::make($request->input('password')),
            'updated_at' => now(),
        ]);
        return redirect()->route('superadmin.profile.index')->with('success', 'Password updated successfully.');
    }
}
