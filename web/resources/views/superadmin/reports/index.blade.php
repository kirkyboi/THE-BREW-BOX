@php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

$viewer = auth()->user();
$viewer = $viewer ? ($viewer->fresh() ?? $viewer) : null;
$avatarPath = preg_replace('#^(?:public/|storage/)+#', '', trim((string)($viewer->avatar ?? '')));
$avatarUrl = $avatarPath && !str_contains($avatarPath, '..') ? Storage::disk('public')->url($avatarPath) : null;
$menu = [
 ['Dashboard','layout-dashboard','superadmin.dashboard'],
 ['Manage Admins','user-cog','superadmin.admins.index'],
 ['All Users','users','superadmin.users.index'],
 ['Roles','shield','superadmin.roles.index'],
 ['Permissions','key-round','superadmin.permissions.index'],
 ['Announcements','megaphone','superadmin.announcements.index'],
 ['System Settings','settings-2','superadmin.settings.index'],
 ['Audit Logs','clipboard-list','superadmin.audit-logs.index'],
 ['Backups & Restore','database-backup','superadmin.backups.index'],
 ['System Monitor','activity','superadmin.system-monitor.index'],
 ['Reports','chart-no-axes-column-increasing','superadmin.reports.index'],
 ['My Profile','circle-user-round','superadmin.profile.index'],
];
$from = (string) request()->query('from', now()->startOfMonth()->toDateString());
$to = (string) request()->query('to', today()->toDateString());
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && Carbon::createFromFormat('!Y-m-d',$from) !== false ? $from : now()->startOfMonth()->toDateString();
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) && Carbon::createFromFormat('!Y-m-d',$to) !== false ? $to : today()->toDateString();
if ($from > $to) { [$from, $to] = [$to, $from]; }
$ordersCount=0; $completedOrders=0; $grossSales=0; $paidRevenue=0; $expensesTotal=0; $netCash=0; $avgOrder=0;
$paymentCount=0; $reservationCount=0; $usersCount=0;
$daily = collect(); $topProducts = collect(); $orderStatuses = collect(); $recentOrders = collect(); $loadWarnings = [];
try {
 if (Schema::hasTable('orders')) {
   $orderDate = 'COALESCE(placed_at, created_at)';
   $ordersBase = DB::table('orders')->whereRaw("DATE($orderDate) BETWEEN ? AND ?", [$from,$to]);
   $ordersCount = (clone $ordersBase)->count();
   $completedOrders = (clone $ordersBase)->where('status','completed')->count();
   $grossSales = (float) (clone $ordersBase)->where('status','completed')->sum('total_amount');
   $avgOrder = $completedOrders ? $grossSales/$completedOrders : 0;
   $orderStatuses = (clone $ordersBase)->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->orderByDesc('total')->get();
   $recentOrders = (clone $ordersBase)->select('order_number','order_type','status','payment_status','total_amount','placed_at','created_at')->orderByDesc('id')->limit(8)->get();
   $daily = (clone $ordersBase)->where('status','completed')->selectRaw("DATE($orderDate) as day, COUNT(*) as orders, SUM(total_amount) as amount")->groupByRaw("DATE($orderDate)")->orderBy('day')->get();
   if (Schema::hasTable('order_items')) {
     $topProducts = DB::table('order_items as oi')->join('orders as o','o.id','=','oi.order_id')
       ->where('o.status','completed')->whereRaw("DATE(COALESCE(o.placed_at,o.created_at)) BETWEEN ? AND ?",[$from,$to])
       ->select('oi.product_name',DB::raw('SUM(oi.quantity) as units'),DB::raw('SUM(oi.line_total) as sales'))
       ->groupBy('oi.product_name')->orderByDesc('units')->limit(7)->get();
   }
 } else { $loadWarnings[] = 'Orders table is missing.'; }
 if (Schema::hasTable('payments')) {
   $paid = DB::table('payments')->where('status','paid')->whereRaw('DATE(COALESCE(paid_at,created_at)) BETWEEN ? AND ?',[$from,$to]);
   $paidRevenue = (float)(clone $paid)->sum('amount');
   $paymentCount = (clone $paid)->count();
 }
 if (Schema::hasTable('expenses')) $expensesTotal = (float) DB::table('expenses')->whereBetween('expense_date',[$from,$to])->sum('amount');
 $netCash = $paidRevenue - $expensesTotal;
 if (Schema::hasTable('reservations')) $reservationCount = DB::table('reservations')->whereRaw('DATE(reservation_start) BETWEEN ? AND ?',[$from,$to])->count();
 if (Schema::hasTable('users')) {
   $usersQuery = DB::table('users')->whereBetween(DB::raw('DATE(created_at)'),[$from,$to]);
   if (Schema::hasColumn('users','deleted_at')) $usersQuery->whereNull('deleted_at');
   $usersCount = $usersQuery->count();
 }
} catch (\Throwable $e) {
 report($e);
 $loadWarnings[] = 'Some report data could not be loaded. Check the Laravel application log.';
}
$money = fn ($amount) => '₱'.number_format((float)$amount,2);
$chartLabels = $daily->pluck('day')->map(fn($day)=>Carbon::parse($day)->format('M d'))->all();
$chartValues = $daily->pluck('amount')->map(fn($v)=>(float)$v)->all();
@endphp<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Reports | The Brew Box</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <style>

        :root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}

        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}svg{flex-shrink:0}

        .sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:width .2s,transform .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55}.nav-text,.brand-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}

        .workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.icon-button svg{width:21px}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1530px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}

        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:21px}.stat,.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020}.stat{padding:19px;display:flex;align-items:center;gap:13px}.stat-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:13px;background:#7e4a1f;color:#ffd17c}.stat-icon svg{width:23px}.stat small{display:block;color:#d5bba0;margin-bottom:5px}.stat strong{font-size:27px}.panel{padding:21px}.panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.panel h2{font-size:18px;margin:0}.panel-sub{color:#cbb19a;font-size:12px;margin:7px 0 0}.controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.search{display:flex;align-items:center;gap:8px;background:#1b100b;border:1px solid #815630;border-radius:10px;padding:0 12px}.search svg{width:16px;color:var(--gold)}.search input{width:210px;height:40px;background:transparent;border:0;color:white;outline:none;font-size:12px}.search input::placeholder{color:#a58a74}.filter{height:42px;padding:0 12px;background:#1b100b;color:#f6e4d2;border:1px solid #815630;border-radius:10px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:790px}th{padding:13px;text-align:left;font-size:10px;letter-spacing:1.3px;color:#e2b778;text-transform:uppercase;background:#1c100b}td{padding:15px 13px;border-top:1px solid #654229;color:#ead8c7;font-size:12px}td strong{color:white}.initial{display:inline-grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#654020;color:#f7ca85;margin-right:9px;font-weight:800;vertical-align:middle}.pill{padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;display:inline-block}.pill.active{background:#1f4b32;color:#9de6ae}.pill.inactive{background:#5d3028;color:#ffb2a1}.role-pill{display:inline-block;border:1px solid #9b6c39;color:#f1c589;border-radius:8px;padding:6px 9px;font-weight:700;font-size:11px}.muted{color:#bfa38b}.empty{text-align:center;padding:45px 15px;color:#c5a78c;line-height:1.8}.note{margin-top:14px;font-size:11px;color:#c6a78b;line-height:1.7}.notice{border:1px solid #b26c43;border-radius:10px;padding:12px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.hidden-row{display:none!important}.mobile-shade{display:none}.count{font-size:12px;color:#e4b875;font-weight:700}

        .collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}.collapsed .brand-icon svg{width:34px}

        @media(max-width:1150px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.stats{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:15px}.search input{width:170px}.account small{display:none}.top-title{font-size:12px}}

        .audit-detail{margin-top:10px;padding:12px;background:#1b100b;border:1px solid #78502e;border-radius:9px;min-width:230px;max-width:430px}.audit-detail strong{color:#f4c77d}.audit-detail pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:11px;color:#eed6bd;max-height:240px;overflow:auto}.pager{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px}.pager-links{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.pager-links .filter{display:inline-flex;align-items:center;height:34px;font-size:11px}
        .report-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:18px;margin-bottom:20px}.report-grid .panel{min-width:0}.report-grid table{min-width:360px}.money{font-size:clamp(17px,1.8vw,24px)!important;overflow-wrap:anywhere}.controls label{font-size:12px;display:inline-flex;align-items:center;gap:8px}@media(max-width:1100px){.report-grid{grid-template-columns:1fr}}@media print{.sidebar,.topbar,.controls button,.controls a,.mobile-shade{display:none!important}.workspace{margin-left:0!important}.main{padding:0!important}.panel,.stat,.hero{break-inside:avoid;background:#fff!important;color:#24160d!important;box-shadow:none!important}.panel *, .stat *, .hero *{color:#24160d!important}body{background:#fff!important}}
    </style>

</head>

<body>

<div class="mobile-shade" id="mobileShade"></div>

<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">

    <a class="brand" href="{{ route('superadmin.dashboard') }}">

        <span class="brand-icon"><i data-lucide="coffee"></i></span>

        <span class="brand-text"><span class="brand-title">The Brew Box</span><small>SUPER ADMINISTRATION</small></span>

    </a>

    <nav class="side-scroll">

        <div class="side-label">System Management</div>

        @foreach($menu as [$label, $icon, $routeName])

            @if(Route::has($routeName))

                <a class="nav {{ $routeName === 'superadmin.reports.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.reports.index') aria-current="page" @endif>

                    <i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span>

                </a>

            @else

                <span class="nav" style="opacity:.5" title="Route not yet registered"><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></span>

            @endif

        @endforeach

    </nav>

    <div class="sidebar-footer">

        <form method="POST" action="{{ route('logout') }}">@csrf

            <button class="nav logout" type="submit"><i data-lucide="log-out"></i><span class="nav-text">Sign Out</span></button>

        </form>

    </div>

</aside>

<div class="workspace">

    <header class="topbar">

        <button class="icon-button" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button>

        <span class="top-title">Super Administrator / Reports</span><span class="top-spacer"></span>

        <a class="account" href="{{ Route::has('superadmin.profile.index') ? route('superadmin.profile.index') : '#' }}" title="My Profile"><span class="avatar" style="overflow:hidden">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="Profile photo" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span style="display:none;place-items:center;width:100%;height:100%">{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}</span>@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></a>

    </header>

    <main class="main">
<div class="breadcrumbs">THE BREW BOX / SUPERADMIN / REPORTS</div>
<section class="hero"><div><div class="eyebrow">Business Intelligence</div><h1>Reports &amp; Analytics</h1><p>Track café sales, paid transactions, expenses, orders and product performance using your live database.</p></div><div class="hero-symbol"><i data-lucide="chart-no-axes-combined"></i></div></section>
@foreach($loadWarnings as $warning)<div class="notice" role="alert">{{ $warning }}</div>@endforeach
<section class="panel" style="margin-bottom:20px">
 <div class="panel-header"><div><h2>Reporting Period</h2><p class="panel-sub">Select a date range to refresh every metric and report.</p></div><span class="role-pill">{{ Carbon::parse($from)->format('M d, Y') }} — {{ Carbon::parse($to)->format('M d, Y') }}</span></div>
 <form method="GET" action="{{ route('superadmin.reports.index') }}" class="controls">
  <label class="muted">From <input class="filter" type="date" name="from" value="{{ $from }}" required style="color-scheme:dark"></label>
  <label class="muted">To <input class="filter" type="date" name="to" value="{{ $to }}" required style="color-scheme:dark"></label>
  <button class="filter" type="submit" style="border-color:#e3ab59;color:#ffd58d;font-weight:800">Apply Filter</button>
  <a class="filter" style="display:inline-flex;align-items:center" href="{{ route('superadmin.reports.index') }}">This Month</a>
  <button type="button" class="filter" onclick="window.print()" style="border-color:#b9874d;color:#fbd69b"><i data-lucide="printer" style="width:15px;vertical-align:middle"></i> Print Report</button>
 </form>
</section>
<section class="stats" aria-label="Report metrics">
 <div class="stat"><span class="stat-icon"><i data-lucide="wallet"></i></span><div><small>Paid Revenue</small><strong class="money">{{ $money($paidRevenue) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="shopping-bag"></i></span><div><small>Completed Order Sales</small><strong class="money">{{ $money($grossSales) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="receipt"></i></span><div><small>Recorded Expenses</small><strong class="money">{{ $money($expensesTotal) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="trending-up"></i></span><div><small>Net Cash (Payments − Expenses)</small><strong class="money">{{ $money($netCash) }}</strong></div></div>
</section>
<section class="stats" aria-label="Operations metrics">
 <div class="stat"><span class="stat-icon"><i data-lucide="clipboard-list"></i></span><div><small>Total Orders</small><strong>{{ number_format($ordersCount) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="check-circle-2"></i></span><div><small>Completed Orders</small><strong>{{ number_format($completedOrders) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="calendar-check"></i></span><div><small>Reservations</small><strong>{{ number_format($reservationCount) }}</strong></div></div>
 <div class="stat"><span class="stat-icon"><i data-lucide="users"></i></span><div><small>New Users</small><strong>{{ number_format($usersCount) }}</strong></div></div>
</section>
<div class="report-grid">
 <section class="panel"><div class="panel-header"><div><h2>Completed Order Sales Trend</h2><p class="panel-sub">Daily order totals, based on completion status and order placement date.</p></div></div><div style="height:270px;position:relative"><canvas id="salesChart" aria-label="Daily completed order sales" role="img"></canvas></div>@if($daily->isEmpty())<p class="note">No completed orders for the selected period.</p>@endif</section>
 <section class="panel"><div class="panel-header"><div><h2>Order Status Breakdown</h2><p class="panel-sub">Current status of orders placed during this period.</p></div></div><div class="table-wrap"><table><thead><tr><th>Status</th><th>Orders</th><th>Share</th></tr></thead><tbody>@forelse($orderStatuses as $status)<tr><td><span class="role-pill">{{ Str::headline($status->status) }}</span></td><td>{{ number_format($status->total) }}</td><td>{{ $ordersCount ? number_format($status->total/$ordersCount*100,1) : 0 }}%</td></tr>@empty<tr><td colspan="3" class="empty">No orders in this period.</td></tr>@endforelse</tbody></table></div></section>
 <section class="panel"><div class="panel-header"><div><h2>Top Selling Products</h2><p class="panel-sub">Ranked by quantity sold in completed orders.</p></div></div><div class="table-wrap"><table><thead><tr><th>Product</th><th>Units</th><th>Sales</th></tr></thead><tbody>@forelse($topProducts as $product)<tr><td><strong>{{ $product->product_name }}</strong></td><td>{{ number_format($product->units) }}</td><td>{{ $money($product->sales) }}</td></tr>@empty<tr><td colspan="3" class="empty">No product sales to display.</td></tr>@endforelse</tbody></table></div></section>
 <section class="panel"><div class="panel-header"><div><h2>Latest Orders</h2><p class="panel-sub">Recent orders within the selected period.</p></div></div><div class="table-wrap"><table><thead><tr><th>Order</th><th>Status</th><th>Payment</th><th>Total</th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><td><strong>{{ $order->order_number }}</strong><div class="muted">{{ Str::headline($order->order_type) }}</div></td><td><span class="role-pill">{{ Str::headline($order->status) }}</span></td><td>{{ Str::headline($order->payment_status) }}</td><td>{{ $money($order->total_amount) }}</td></tr>@empty<tr><td colspan="4" class="empty">No orders found.</td></tr>@endforelse</tbody></table></div></section>
</div>
<p class="note">Paid revenue sums payments marked paid, by payment date. Completed order sales sums completed order totals, by placement date. These are different measures and may not match. Net cash is paid revenue minus recorded expenses; it is not accounting net profit. Refunds, taxes, inventory costs and other adjustments may require separate reporting.</p>
</main>


</div>

<script>

document.addEventListener('DOMContentLoaded', () => {

    if (window.lucide) window.lucide.createIcons();
    if (window.Chart && document.getElementById('salesChart')) {
      new Chart(document.getElementById('salesChart'), {type:'line',data:{labels:@json($chartLabels),datasets:[{label:'Completed order sales (PHP)',data:@json($chartValues),borderColor:'#f1b94f',backgroundColor:'rgba(241,185,79,.13)',tension:.3,fill:true,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#d5bba0'},grid:{display:false}},y:{beginAtZero:true,ticks:{color:'#d5bba0'},grid:{color:'rgba(200,150,90,.13)'}}}}});
    }

    const body = document.body;

    const toggle = document.getElementById('sidebarToggle');

    const shade = document.getElementById('mobileShade');

    const mobile = () => window.matchMedia('(max-width:800px)').matches;

    const closeMobile = () => { body.classList.remove('mobile-open'); toggle.setAttribute('aria-expanded', 'false'); };

    toggle.addEventListener('click', () => {

        if (mobile()) { const open = body.classList.toggle('mobile-open'); toggle.setAttribute('aria-expanded', String(open)); }

        else { const collapsed = body.classList.toggle('collapsed'); toggle.setAttribute('aria-expanded', String(!collapsed)); try { localStorage.setItem('brew_superadmin_sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) {} }

    });

    shade.addEventListener('click', closeMobile);

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobile(); });

    try { if (!mobile() && localStorage.getItem('brew_superadmin_sidebar_collapsed') === '1') { body.classList.add('collapsed'); toggle.setAttribute('aria-expanded', 'false'); } } catch (e) {}

    window.addEventListener('resize', () => { if (!mobile()) closeMobile(); });


});

</script>

</body>

</html>
