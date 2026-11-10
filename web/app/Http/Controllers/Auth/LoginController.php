<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['login']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $identifier,
            'password' => $validated['password'],
            'status' => 'active',
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'Invalid credentials or your account is not active.',
            ]);
        }

        $user = Auth::user();
        $roleSlug = DB::table('roles')
            ->where('id', $user->role_id)
            ->value('slug');

        $dashboardRoutes = [
            'superadmin' => 'superadmin.dashboard',
            'admin' => 'admin.dashboard',
            'manager' => 'manager.dashboard',
            'cashier' => 'cashier.dashboard',
            'barista' => 'barista.dashboard',
            'staff' => 'staff.dashboard',
            'customer' => 'customer.dashboard',
        ];

        if (! isset($dashboardRoutes[$roleSlug])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'This account does not have a valid role. Contact an administrator.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route($dashboardRoutes[$roleSlug])
            ->with('success', 'Welcome to THE BREW BOX!');
    }
}
