<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserStatusController extends Controller
{
    public function toggleStatus(Request $request, int $user)
    {
        $viewer = $request->user();
        abort_unless($viewer && $viewer->status === 'active', 403);
        abort_unless(DB::table('roles')->where('id', $viewer->role_id)->value('slug') === 'superadmin', 403);
        abort_if((int) $viewer->id === $user, 403, 'You cannot change your own status.');

        $target = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', $user)
            ->whereNull('users.deleted_at')
            ->select('users.id', 'users.name', 'users.status', 'roles.slug as role_slug')
            ->first();

        abort_unless($target, 404);
        abort_if($target->role_slug === 'superadmin', 403, 'Superadmin accounts are protected.');
        abort_unless(in_array($target->status, ['active', 'inactive'], true), 422, 'Only active or inactive accounts can be toggled.');

        $next = $target->status === 'active' ? 'inactive' : 'active';
        DB::table('users')->where('id', $user)->where('status', $target->status)->update([
            'status' => $next,
            'updated_at' => now(),
        ]);

        return redirect()->route('superadmin.users.index')
            ->with('success', $target->name . ' is now ' . $next . '.');
    }
}
