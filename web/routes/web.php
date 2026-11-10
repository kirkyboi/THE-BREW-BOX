<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Superadmin\AdminController;
use App\Http\Controllers\Superadmin\UserStatusController;
use App\Http\Controllers\Superadmin\AnnouncementController;
use App\Http\Controllers\Superadmin\ProfileController;
use App\Http\Controllers\Superadmin\SettingsController;
use App\Http\Controllers\Superadmin\BackupController;
use App\Http\Controllers\Superadmin\SystemMonitorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;

/* THE BREW BOX — Laravel 12 routes */
Route::get('/', fn () => view('customer.home'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

$roleModules = [
    'superadmin' => [
        'admins' => 'Manage Admins', 'users' => 'All Users', 'roles' => 'Roles',
        'permissions' => 'Permissions', 'announcements' => 'Announcements',
        'settings' => 'System Settings', 'audit-logs' => 'Audit Logs',
        'backups' => 'Backups & Restore', 'system-monitor' => 'System Monitor',
        'reports' => 'Reports', 'profile' => 'My Profile',
    ],
    'admin' => [
        'orders' => 'Orders', 'tables' => 'Table Management',
        'reservations' => 'Reservations', 'products' => 'Products',
        'categories' => 'Categories', 'inventory' => 'Inventory',
        'recipes' => 'Recipes', 'suppliers' => 'Suppliers',
        'purchases' => 'Purchases', 'customers' => 'Customers',
        'staff' => 'Staff Management', 'payments' => 'Payments',
        'expenses' => 'Expenses', 'reports' => 'Reports',
        'promotions' => 'Promotions', 'announcements' => 'Announcements',
        'notifications' => 'Notifications', 'users' => 'Users',
        'profile' => 'My Profile', 'settings' => 'Settings',
    ],
    'manager' => [
        'orders' => 'Orders', 'inventory' => 'Inventory', 'staff' => 'Staff',
        'suppliers' => 'Suppliers', 'purchases' => 'Purchases',
        'expenses' => 'Expenses', 'reports' => 'Sales Reports',
        'notifications' => 'Notifications', 'profile' => 'My Profile',
    ],
    'cashier' => [
        'pos' => 'Point of Sale', 'orders' => 'Orders', 'payments' => 'Payments',
        'receipts' => 'Receipts', 'cash-register' => 'Cash Register',
        'transactions' => 'Transactions', 'profile' => 'My Profile',
    ],
    'barista' => [
        'preparation-queue' => 'Preparation Queue', 'orders' => 'Orders',
        'product-availability' => 'Product Availability',
        'notifications' => 'Notifications', 'profile' => 'My Profile',
    ],
    'staff' => [
        'tasks' => 'Tasks', 'orders' => 'Orders', 'reservations' => 'Reservations',
        'tables' => 'Tables', 'notifications' => 'Notifications',
        'profile' => 'My Profile',
    ],
    'customer' => [
        'menu' => 'Menu', 'cart' => 'Cart', 'checkout' => 'Checkout',
        'orders' => 'My Orders', 'reservations' => 'Reservations',
        'loyalty' => 'Loyalty Rewards', 'reviews' => 'Reviews',
        'announcements' => 'Announcements', 'notifications' => 'Notifications',
        'profile' => 'My Profile',
    ],
];

$authorizeRole = static function (string $role): void {
    $user = Auth::user();
    abort_unless($user && $user->status === 'active', 403, 'Your account is not active.');
    $slug = DB::table('roles')->where('id', $user->role_id)->value('slug');
    abort_unless($slug === $role, 403, 'You are not authorized to access this page.');
};

Route::middleware('auth')->group(function () use ($roleModules, $authorizeRole) {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'You have logged out successfully.');
    })->name('logout');

    // Admin Dashboard and notification API. DashboardController checks the active admin role.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/notifications', [DashboardController::class, 'notifications'])
            ->name('dashboard.notifications');
        Route::patch('/dashboard/notifications/read-all', [DashboardController::class, 'readAllNotifications'])
            ->name('dashboard.notifications.read-all');
        Route::patch('/dashboard/notifications/{id}/read', [DashboardController::class, 'readNotification'])
            ->whereUuid('id')->name('dashboard.notifications.read');
    });

    // Product CRUD — handled by ProductController, not the generic Blade placeholder.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])
            ->whereNumber('product')->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->whereNumber('product')->name('products.destroy');
    });

    // Category CRUD — controller provides categories and counts to its Blade view.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->whereNumber('category')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->whereNumber('category')->name('categories.destroy');
    });

    // Dedicated controller endpoints. Each controller must also authorize the actor.
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::patch('/users/{user}/toggle-status', [UserStatusController::class, 'toggleStatus'])
            ->whereNumber('user')->name('users.toggle-status');
        Route::get('/admins/create', [AdminController::class, 'create'])->name('admins.create');
        Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
        Route::patch('/admins/{admin}/toggle-status', [AdminController::class, 'toggleStatus'])
            ->whereNumber('admin')->name('admins.toggle-status');
        Route::delete('/admins/{admin}', [AdminController::class, 'destroy'])
            ->whereNumber('admin')->name('admins.destroy');

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        });

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])
                ->whereNumber('announcement')->name('edit');
            Route::patch('/{announcement}/publish', [AnnouncementController::class, 'publish'])
                ->whereNumber('announcement')->name('publish');
            Route::get('/{announcement}', [AnnouncementController::class, 'show'])
                ->whereNumber('announcement')->name('show');
            Route::put('/{announcement}', [AnnouncementController::class, 'update'])
                ->whereNumber('announcement')->name('update');
            Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])
                ->whereNumber('announcement')->name('destroy');
        });

        // System Settings: these routes must not be duplicated by placeholder routes.
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Database backup management.
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

        // Live application and server health diagnostics.
        Route::get('/system-monitor', [SystemMonitorController::class, 'index'])
            ->name('system-monitor.index');
    });

    foreach ($roleModules as $role => $modules) {
        Route::prefix($role)->name($role . '.')->group(function () use ($role, $modules, $authorizeRole) {
            $placeholder = static function (string $title, string $viewName) use ($role) {
                $safeTitle = e($title);
                $safeRole = e(ucfirst($role));
                $safeView = e($viewName);
                $dashboardUrl = e(route($role . '.dashboard'));
                return response(<<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle} | The Brew Box</title>
<style>*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#180d08;color:#fff3e2;font:16px system-ui,sans-serif}.card{width:min(100%,620px);border:1px solid #b78642;border-radius:22px;background:linear-gradient(145deg,#382115,#21120c);padding:36px;box-shadow:0 20px 70px #0007}.eyebrow{color:#e4b875;font-size:12px;letter-spacing:2px;text-transform:uppercase}h1{font-size:32px;margin:12px 0}p{color:#dfcdbb;line-height:1.7}code{color:#ffe2a4;overflow-wrap:anywhere}a{display:inline-block;background:#e5af5b;color:#241309;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:700;margin-top:16px}</style>
</head><body><main class="card"><div class="eyebrow">THE BREW BOX / {$safeRole}</div><h1>{$safeTitle}</h1><p>This menu is connected and protected by your account role. The full management page has not been implemented yet.</p><p>Create the Blade view <code>resources/views/{$safeView}.blade.php</code> to replace this temporary page.</p><a href="{$dashboardUrl}">Back to Dashboard</a></main></body></html>
HTML);
            };

            if ($role !== 'admin') {
                Route::get('/dashboard', function () use ($role, $authorizeRole, $placeholder) {
                    $authorizeRole($role);
                    $viewName = $role . '.dashboard';
                    return View::exists($viewName)
                        ? view($viewName)
                        : $placeholder('Dashboard', str_replace('.', '/', $viewName));
                })->name('dashboard');
            }

            foreach ($modules as $segment => $title) {
                // The real admin product route above supplies products, categories and counts.
                if ($role === 'admin' && in_array($segment, ['products', 'categories'], true)) {
                    continue;
                }
                if ($role === 'superadmin' && in_array($segment, ['announcements', 'profile', 'settings', 'backups', 'system-monitor'], true)) {
                    continue;
                }
                Route::get('/' . $segment, function () use ($role, $segment, $title, $authorizeRole, $placeholder) {
                    $authorizeRole($role);
                    $viewName = $role . '.' . $segment . '.index';
                    return View::exists($viewName)
                        ? view($viewName)
                        : $placeholder($title, str_replace('.', '/', $viewName));
                })->name($segment . '.index');
            }
        });
    }
});
