<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Default Superadmin seeding is allowed only in the local environment.');
        }

        DB::transaction(function (): void {
            $role = DB::table('roles')->where('slug', 'superadmin')->first();

            if (! $role) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => 'Super Administrator',
                    'slug' => 'superadmin',
                    'description' => 'Full system access',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $roleId = $role->id;
            }

            $existing = DB::table('users')->where('username', 'superadmin')->first();
            if ($existing) {
                if ((int) $existing->role_id !== (int) $roleId) {
                    throw new RuntimeException('Username superadmin already belongs to a different role. No changes were made.');
                }
                $this->command?->info('Superadmin already exists. Password was not changed.');
                return;
            }

            DB::table('users')->insert([
                'role_id' => $roleId,
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'superadmin@thebrewbox.test',
                'password' => Hash::make('superadmin123'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info('Local Superadmin account created. Change its password before deployment.');
        });
    }
}
