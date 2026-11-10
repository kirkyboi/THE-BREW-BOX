<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->status === 'active' &&
            DB::table('roles')->where('id', $user->role_id)->value('slug') === 'admin', 403);
    }

    private function notificationsQuery()
    {
        $user = Auth::user();
        return DB::table('notifications')
            ->where('notifiable_id', $user->getAuthIdentifier())
            ->whereIn('notifiable_type', array_unique([$user->getMorphClass(), get_class($user)]));
    }

    private function notificationsPayload(): array
    {
        if (!Schema::hasTable('notifications')) return ['unread' => 0, 'items' => []];
        $items = (clone $this->notificationsQuery())->orderByDesc('created_at')->limit(12)->get();
        return [
            'unread' => (clone $this->notificationsQuery())->whereNull('read_at')->count(),
            'items' => $items->map(function ($row) {
                $data = json_decode($row->data ?? '{}', true);
                if (!is_array($data)) $data = [];
                $url = $data['url'] ?? null;
                // Only local, relative URLs may be opened from notifications.
                if (!is_string($url) || !str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) $url = null;
                return [
                    'id' => $row->id,
                    'title' => Str::limit((string)($data['title'] ?? $data['subject'] ?? 'Notification'), 100),
                    'message' => Str::limit((string)($data['message'] ?? $data['body'] ?? ''), 200),
                    'url' => $url,
                    'read' => $row->read_at !== null,
                    'date' => $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->diffForHumans() : '',
                ];
            })->all(),
        ];
    }

    public function index(): View
    {
        $this->authorizeAdmin();
        $today = now()->startOfDay();
        $start = now()->subDays(6)->startOfDay();
        $orders = Schema::hasTable('orders') ? DB::table('orders') : null;
        $orderCount = $orders ? (clone $orders)->where('created_at', '>=', $today)->where('status', '!=', 'cancelled')->count() : 0;
        $sales = $orders ? (float)(clone $orders)->where('created_at', '>=', $today)->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->sum('total_amount') : 0;
        $customers = Schema::hasTable('customers') ? DB::table('customers')->count() : 0;
        $lowStock = 0;
        if (Schema::hasTable('inventories') && Schema::hasTable('ingredients')) {
            $lowStock = DB::table('inventories as i')->join('ingredients as g', 'g.id', '=', 'i.ingredient_id')
                ->where('g.is_active', 1)->whereRaw('(i.quantity_on_hand - i.quantity_reserved) <= g.reorder_level')->count();
        }
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $daily[] = [
                'label' => $day->format('M j'),
                'value' => $orders ? (float)(clone $orders)->whereDate('created_at', $day->toDateString())->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->sum('total_amount') : 0,
            ];
        }
        $recentOrders = collect();
        $categories = collect();
        $bestSellers = collect();
        if ($orders) {
            $recentOrders = DB::table('orders as o')
                ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->select('o.id', 'o.order_number', 'o.status', 'o.total_amount', 'o.payment_status', 'o.created_at', 'u.name as customer_name')
                ->orderByDesc('o.created_at')->limit(6)->get();
            if (Schema::hasTable('order_items')) {
                $bestSellers = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
                    ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
                    ->where('o.created_at', '>=', $start)->where('o.status', '!=', 'cancelled')
                    ->select('oi.product_name', 'p.image', DB::raw('SUM(oi.quantity) as qty'))
                    ->groupBy('oi.product_name', 'p.image')->orderByDesc('qty')->limit(4)->get();
                if (Schema::hasTable('products') && Schema::hasTable('categories')) {
                    $categories = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
                        ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
                        ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                        ->where('o.created_at', '>=', $start)->where('o.status', '!=', 'cancelled')
                        ->selectRaw("COALESCE(c.name, 'Other') as category, SUM(oi.quantity) as qty")
                        ->groupBy('c.name')->orderByDesc('qty')->limit(5)->get();
                }
            }
        }
        $user = Auth::user();
        $avatar = trim((string)($user->avatar ?? ''));
        $avatar = preg_replace('#^(?:/)?(?:storage/|public/)#', '', $avatar);
        return view('admin.dashboard', [
            'orderCount' => $orderCount, 'sales' => $sales, 'customers' => $customers,
            'lowStock' => $lowStock, 'daily' => $daily, 'recentOrders' => $recentOrders,
            'categories' => $categories, 'bestSellers' => $bestSellers,
            'notificationData' => $this->notificationsPayload(),
            'avatarUrl' => $avatar ? Storage::disk('public')->url($avatar) : null,
        ]);
    }

    public function notifications(): JsonResponse
    {
        $this->authorizeAdmin();
        return response()->json($this->notificationsPayload())->header('Cache-Control', 'no-store');
    }

    public function readNotification(string $id): JsonResponse
    {
        $this->authorizeAdmin();
        abort_unless(Schema::hasTable('notifications'), 404);
        $affected = (clone $this->notificationsQuery())->where('id', $id)->update(['read_at' => now()]);
        abort_unless($affected, 404);
        return response()->json(['success' => true, 'unread' => (clone $this->notificationsQuery())->whereNull('read_at')->count()]);
    }

    public function readAllNotifications(): JsonResponse
    {
        $this->authorizeAdmin();
        if (Schema::hasTable('notifications')) {
            (clone $this->notificationsQuery())->whereNull('read_at')->update(['read_at' => now()]);
        }
        return response()->json(['success' => true, 'unread' => 0]);
    }
}
