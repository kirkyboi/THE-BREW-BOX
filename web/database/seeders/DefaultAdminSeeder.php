
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = DB::table('roles')
            ->where('slug', 'admin')
            ->value('id');

        if ($adminRoleId === null) {
            throw new RuntimeException(
                'Admin role not found. Create the admin role first.'
            );
        }

        // Never overwrite an existing account.
        if (User::where('username', 'admin')->exists()) {
            $this->command->warn(
                'Username admin already exists. No changes made.'
            );

            return;
        }

        $user = new User();
        $user->role_id = $adminRoleId;
        $user->name = 'System Administrator';
        $user->username = 'admin';
        $user->email = 'admin@brewbox.test';
        $user->password = Hash::make('admin123');
        $user->status = 'active';
        $user->save();

        $this->command->info(
            'Development administrator account created.'
        );
    }
}
