<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    private function authorizeSuperadmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active' && DB::table('roles')->where('id', $user->role_id)->value('slug') === 'superadmin', 403);
    }

    private function directory(): string
    {
        return storage_path('app/private/database-backups');
    }

    private function validPath(string $backup): string
    {
        abort_unless((bool) preg_match('/\Abrewbox_\d{8}_\d{6}_[a-f0-9]{12}\.sql\z/D', $backup), 404);
        $path = $this->directory() . DIRECTORY_SEPARATOR . $backup;
        abort_unless(is_file($path) && !is_link($path), 404);
        return $path;
    }

    public function index(): View
    {
        $this->authorizeSuperadmin();
        $directory = $this->directory();
        $backups = collect(is_dir($directory) ? File::files($directory) : [])
            ->filter(fn ($file) => preg_match('/\Abrewbox_\d{8}_\d{6}_[a-f0-9]{12}\.sql\z/D', $file->getFilename()))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'bytes' => $file->getSize(),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')->values();

        return view('superadmin.backups.index', compact('backups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        if (($config['driver'] ?? null) !== 'mysql') {
            return back()->with('error', 'Database backups require a MySQL connection.');
        }

        $directory = $this->directory();
        File::ensureDirectoryExists($directory, 0700, true);
        $name = 'brewbox_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.sql';
        $output = $directory . DIRECTORY_SEPARATOR . $name;
        $optionFile = tempnam(sys_get_temp_dir(), 'brewbox_db_');
        if ($optionFile === false) {
            return back()->with('error', 'Could not prepare a secure temporary configuration file.');
        }
        try {
            // MySQL reads option files before all other options. Never pass passwords in command-line arguments.
            $quote = static fn ($value) => '"' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value) . '"';
            $lines = ["[client]", 'user=' . $quote($config['username'] ?? ''), 'password=' . $quote($config['password'] ?? '')];
            if (!empty($config['unix_socket'])) {
                $lines[] = 'socket=' . $quote($config['unix_socket']);
            } else {
                $lines[] = 'host=' . $quote($config['host'] ?? '127.0.0.1');
                $lines[] = 'port=' . (int) ($config['port'] ?? 3306);
            }
            File::put($optionFile, implode("\n", $lines) . "\n");
            @chmod($optionFile, 0600);
            $binary = (string) config('services.brewbox.mysqldump_path', env('MYSQLDUMP_PATH', 'mysqldump'));
            $process = new Process([
                $binary, '--defaults-extra-file=' . $optionFile,
                '--single-transaction', '--quick', '--routines', '--triggers', '--events',
                '--default-character-set=utf8mb4', '--result-file=' . $output,
                '--', (string) ($config['database'] ?? ''),
            ]);
            $process->setTimeout(300);
            $process->run();
            if (!$process->isSuccessful() || !is_file($output) || filesize($output) === 0) {
                throw new \RuntimeException('mysqldump failed: ' . trim($process->getErrorOutput()));
            }
            @chmod($output, 0600);
            Log::info('Database backup created', ['actor_id' => Auth::id(), 'filename' => $name]);
            return back()->with('success', 'Database backup created successfully.');
        } catch (\Throwable $e) {
            if (is_file($output)) @unlink($output);
            Log::error('Database backup failed', ['actor_id' => Auth::id(), 'error' => $e->getMessage()]);
            return back()->with('error', 'Backup failed. Check the Laravel log and confirm mysqldump is installed and configured.');
        } finally {
            if (is_file($optionFile)) @unlink($optionFile);
        }
    }

    public function download(string $backup): BinaryFileResponse
    {
        $this->authorizeSuperadmin();
        return response()->download($this->validPath($backup), $backup, [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(string $backup): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $path = $this->validPath($backup);
        File::delete($path);
        Log::warning('Database backup deleted', ['actor_id' => Auth::id(), 'filename' => $backup]);
        return back()->with('success', 'Backup deleted successfully.');
    }
}
