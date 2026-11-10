<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Display the registration page.
     */
    public function index()
    {
        return view('auth.register');
    }

    /**
     * Register a new customer.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'required',
                'email',
                'max:191',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        // Use the role slug defined in THE BREW BOX schema.
        $customerRoleId = DB::table('roles')
            ->where('slug', 'customer')
            ->value('id');

        if ($customerRoleId === null) {
            throw ValidationException::withMessages([
                'email' => 'Customer registration is not configured. Please contact the administrator.',
            ]);
        }

        // Create the user and customer profile together.
        // If either operation fails, both are rolled back.
        $user = DB::transaction(function () use (
            $validated,
            $customerRoleId
        ) {
            // Generate a unique username.
            // The random suffix avoids duplicate names.
            $baseUsername = Str::slug(
                $validated['name'],
                ''
            );

            if ($baseUsername === '') {
                $baseUsername = 'customer';
            }

            $baseUsername = Str::limit(
                $baseUsername,
                60,
                ''
            );

            $username = $baseUsername
                . Str::lower(Str::random(16));

            $user = new User();

            $user->role_id = $customerRoleId;
            $user->name = $validated['name'];
            $user->username = $username;
            $user->email = $validated['email'];
            $user->password = Hash::make(
                $validated['password']
            );
            $user->status = 'active';

            $user->save();

            // Create the matching customers record.
            DB::table('customers')->insert([
                'user_id' => $user->id,
                'customer_code' => 'CUST-'
                    . Str::upper(Str::random(20)),
                'loyalty_points' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        // Log in only after successful database commit.
        Auth::login($user);

        $request->session()->regenerate();

        return redirect('/')
            ->with(
                'success',
                'Welcome to THE BREW BOX! Your account has been created successfully.'
            );
    }
}
