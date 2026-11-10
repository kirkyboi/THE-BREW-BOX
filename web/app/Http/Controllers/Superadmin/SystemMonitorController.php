<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SystemMonitorController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active' && DB::table('roles')->where('id', $user->role_id)->value('slug') === 'superadmin', 403);

        $databaseOk = false;
        $latency = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = (microtime(true) - $start) * 1000;
            $databaseOk = true;
        } catch (Throwable $e) {
            report($e);
        }

        $cacheOk = false;
        try {
            $key = 'system_monitor_check_' . $user->getAuthIdentifier();
            Cache::put($key, 'ok', 30);
            $cacheOk = Cache::get($key) === 'ok';
            Cache::forget($key);
        } catch (Throwable $e) {
            report($e);
        }

        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        $monitor = [
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'server' => (string) request()->server('SERVER_SOFTWARE', 'CLI / Unknown'),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'timezone' => (string) config('app.timezone'),
            'memory_limit' => (string) ini_get('memory_limit'),
            'upload_limit' => (string) ini_get('upload_max_filesize'),
            'database_driver' => (string) config('database.default'),
            'database_ok' => $databaseOk,
            'db_latency_ms' => $latency,
            'storage_ok' => is_writable(storage_path()),
            'disk_free' => $free === false ? null : $free,
            'disk_total' => $total === false ? null : $total,
            'cache_driver' => (string) config('cache.default'),
            'cache_ok' => $cacheOk,
            'checked_at' => now()->format('M d, Y h:i:s A'),
        ];

        return view('superadmin.system-monitor.index', compact('monitor'));
    }
}
