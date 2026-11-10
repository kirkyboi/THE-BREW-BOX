@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $viewer = auth()->user();
    $viewer = $viewer ? $viewer->fresh() ?? $viewer : null;
    $avatarPath = trim((string) ($viewer->avatar ?? ''));
    $avatarPath = preg_replace('#^(?:public/|storage/)+#', '', $avatarPath);
    $avatarUrl = $avatarPath && !str_contains($avatarPath, '..') ? Storage::disk('public')->url($avatarPath) : null;
    $menu = [
        ['Dashboard', 'layout-dashboard', 'superadmin.dashboard'],
        ['Manage Admins', 'user-cog', 'superadmin.admins.index'],
        ['All Users', 'users', 'superadmin.users.index'],
        ['Roles', 'shield', 'superadmin.roles.index'],
        ['Permissions', 'key-round', 'superadmin.permissions.index'],
        ['Announcements', 'megaphone', 'superadmin.announcements.index'],
        ['System Settings', 'settings-2', 'superadmin.settings.index'],
        ['Audit Logs', 'clipboard-list', 'superadmin.audit-logs.index'],
        ['Backups & Restore', 'database-backup', 'superadmin.backups.index'],
        ['System Monitor', 'activity', 'superadmin.system-monitor.index'],
        ['Reports', 'chart-column-increasing', 'superadmin.reports.index'],
        ['My Profile', 'circle-user-round', 'superadmin.profile.index'],
    ];
    $search = trim((string) request()->query('search', ''));
    $selectedModule = trim((string) request()->query('module', ''));
    $selectedAction = trim((string) request()->query('action', ''));
    $dateFrom = (string) request()->query('from', '');
    $dateTo = (string) request()->query('to', '');
    $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
    $dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '';
    $logs = collect(); $modules = collect(); $actions = collect(); $loadError = null;
    $totalLogs = 0; $todayLogs = 0; $lastSevenDays = 0; $actorCount = 0;
    $pageLogs = null;
    try {
        if (!Schema::hasTable('audit_logs')) {
            $loadError = 'The audit_logs table does not exist yet. Run your database migrations.';
        } else {
            $base = DB::table('audit_logs');
            $totalLogs = (clone $base)->count();
            $todayLogs = (clone $base)->whereDate('created_at', today())->count();
            $lastSevenDays = (clone $base)->where('created_at', '>=', now()->subDays(7))->count();
            $actorCount = (clone $base)->whereNotNull('user_id')->distinct()->count('user_id');
            $modules = DB::table('audit_logs')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
            $actions = DB::table('audit_logs')->whereNotNull('action')->distinct()->orderBy('action')->pluck('action');
            $query = DB::table('audit_logs as audit');
            if (Schema::hasTable('users')) {
                $query->leftJoin('users as actor', 'actor.id', '=', 'audit.user_id')
                    ->select('audit.*', 'actor.name as actor_name', 'actor.username as actor_username', 'actor.avatar as actor_avatar');
            } else {
                $query->select('audit.*');
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('audit.action', 'like', '%' . $search . '%')
                      ->orWhere('audit.module', 'like', '%' . $search . '%')
                      ->orWhere('audit.ip_address', 'like', '%' . $search . '%')
                      ->orWhere('audit.record_type', 'like', '%' . $search . '%');
                    if (Schema::hasTable('users')) {
                        $q->orWhere('actor.name', 'like', '%' . $search . '%')
                          ->orWhere('actor.username', 'like', '%' . $search . '%');
                    }
                });
            }
            if ($selectedModule !== '') $query->where('audit.module', $selectedModule);
            if ($selectedAction !== '') $query->where('audit.action', $selectedAction);
            if ($dateFrom !== '') $query->whereDate('audit.created_at', '>=', $dateFrom);
            if ($dateTo !== '') $query->whereDate('audit.created_at', '<=', $dateTo);
            $pageLogs = $query->orderByDesc('audit.created_at')->orderByDesc('audit.id')->paginate(20)->withQueryString();
            $logs = $pageLogs->getCollection();
        }
    } catch (\Throwable $e) {
        report($e);
        $loadError = 'Audit records could not be loaded. Check storage/logs/laravel.log.';
    }
    $redact = function ($value) use (&$redact) {
        if (!is_array($value)) return $value;
        foreach ($value as $key => $item) {
            $value[$key] = preg_match('/password|token|secret|api.?key|authorization|cookie|otp|pin|credential/i', (string) $key)
                ? '[REDACTED]' : $redact($item);
        }
        return $value;
    };
    $formatChanges = function ($raw) use ($redact) {
        if ($raw === null || $raw === '') return null;
        $decoded = json_decode((string) $raw, true);
        return json_last_error() === JSON_ERROR_NONE
            ? json_encode($redact($decoded), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '[Unrecognized change data]';
    };
@endphp

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Audit Logs | The Brew Box</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>

    <style>

        :root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}

        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}svg{flex-shrink:0}

        .sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:width .2s,transform .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55}.nav-text,.brand-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}

        .workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.icon-button svg{width:21px}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1530px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}

        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:21px}.stat,.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020}.stat{padding:19px;display:flex;align-items:center;gap:13px}.stat-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:13px;background:#7e4a1f;color:#ffd17c}.stat-icon svg{width:23px}.stat small{display:block;color:#d5bba0;margin-bottom:5px}.stat strong{font-size:27px}.panel{padding:21px}.panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.panel h2{font-size:18px;margin:0}.panel-sub{color:#cbb19a;font-size:12px;margin:7px 0 0}.controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.search{display:flex;align-items:center;gap:8px;background:#1b100b;border:1px solid #815630;border-radius:10px;padding:0 12px}.search svg{width:16px;color:var(--gold)}.search input{width:210px;height:40px;background:transparent;border:0;color:white;outline:none;font-size:12px}.search input::placeholder{color:#a58a74}.filter{height:42px;padding:0 12px;background:#1b100b;color:#f6e4d2;border:1px solid #815630;border-radius:10px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:790px}th{padding:13px;text-align:left;font-size:10px;letter-spacing:1.3px;color:#e2b778;text-transform:uppercase;background:#1c100b}td{padding:15px 13px;border-top:1px solid #654229;color:#ead8c7;font-size:12px}td strong{color:white}.initial{display:inline-grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#654020;color:#f7ca85;margin-right:9px;font-weight:800;vertical-align:middle}.pill{padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;display:inline-block}.pill.active{background:#1f4b32;color:#9de6ae}.pill.inactive{background:#5d3028;color:#ffb2a1}.role-pill{display:inline-block;border:1px solid #9b6c39;color:#f1c589;border-radius:8px;padding:6px 9px;font-weight:700;font-size:11px}.muted{color:#bfa38b}.empty{text-align:center;padding:45px 15px;color:#c5a78c;line-height:1.8}.note{margin-top:14px;font-size:11px;color:#c6a78b;line-height:1.7}.notice{border:1px solid #b26c43;border-radius:10px;padding:12px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.hidden-row{display:none!important}.mobile-shade{display:none}.count{font-size:12px;color:#e4b875;font-weight:700}

        .collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}.collapsed .brand-icon svg{width:34px}

        @media(max-width:1150px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.stats{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:15px}.search input{width:170px}.account small{display:none}.top-title{font-size:12px}}

        .audit-detail{margin-top:10px;padding:12px;background:#1b100b;border:1px solid #78502e;border-radius:9px;min-width:230px;max-width:430px}.audit-detail strong{color:#f4c77d}.audit-detail pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:11px;color:#eed6bd;max-height:240px;overflow:auto}.pager{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px}.pager-links{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.pager-links .filter{display:inline-flex;align-items:center;height:34px;font-size:11px}
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

                <a class="nav {{ $routeName === 'superadmin.audit-logs.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.audit-logs.index') aria-current="page" @endif>

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

        <span class="top-title">Super Administrator / Audit Logs</span><span class="top-spacer"></span>

        <a class="account" href="{{ Route::has('superadmin.profile.index') ? route('superadmin.profile.index') : '#' }}" title="My Profile"><span class="avatar" style="overflow:hidden">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="Profile photo" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span style="display:none;place-items:center;width:100%;height:100%">{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}</span>@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></a>

    </header>

    <main class="main">

        <div class="breadcrumbs">THE BREW BOX / SUPERADMIN / AUDIT LOGS</div>

        <section class="hero">
            <div><div class="eyebrow">Security &amp; Accountability</div><h1>System Audit Logs</h1><p>Review recorded account actions, affected modules, timestamps and change history.</p></div>
            <div class="hero-symbol"><i data-lucide="scroll-text"></i></div>
        </section>
        @if($loadError)<div class="notice" role="alert">{{ $loadError }}</div>@endif
        <section class="stats" aria-label="Audit statistics">
            <div class="stat"><span class="stat-icon"><i data-lucide="clipboard-list"></i></span><div><small>Total Events</small><strong>{{ number_format($totalLogs) }}</strong></div></div>
            <div class="stat"><span class="stat-icon"><i data-lucide="calendar-days"></i></span><div><small>Today</small><strong>{{ number_format($todayLogs) }}</strong></div></div>
            <div class="stat"><span class="stat-icon"><i data-lucide="activity"></i></span><div><small>Last 7 Days</small><strong>{{ number_format($lastSevenDays) }}</strong></div></div>
            <div class="stat"><span class="stat-icon"><i data-lucide="users"></i></span><div><small>Unique Actors</small><strong>{{ number_format($actorCount) }}</strong></div></div>
        </section>
        <section class="panel">
            <div class="panel-header">
                <div><h2>Activity History</h2><p class="panel-sub">Newest events first. Filter results using the controls below.</p></div>
            </div>
            <form method="GET" action="{{ route('superadmin.audit-logs.index') }}" class="controls" style="margin-bottom:19px">
                <label class="search"><i data-lucide="search"></i><input name="search" value="{{ $search }}" type="search" placeholder="Search activity or user..." aria-label="Search audit logs"></label>
                <select name="module" class="filter" aria-label="Module"><option value="">All modules</option>@foreach($modules as $module)<option value="{{ $module }}" @selected($selectedModule === $module)>{{ $module }}</option>@endforeach</select>
                <select name="action" class="filter" aria-label="Action"><option value="">All actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected($selectedAction === $action)>{{ Str::headline($action) }}</option>@endforeach</select>
                <label class="muted" style="font-size:11px">From <input class="filter" name="from" type="date" value="{{ $dateFrom }}" style="color-scheme:dark"></label>
                <label class="muted" style="font-size:11px">To <input class="filter" name="to" type="date" value="{{ $dateTo }}" style="color-scheme:dark"></label>
                <button class="filter" type="submit" style="color:#ffd27e;border-color:#d29a4d;font-weight:800">Apply</button>
                <a class="filter" href="{{ route('superadmin.audit-logs.index') }}" style="display:inline-flex;align-items:center">Reset</a>
            </form>
            <div class="table-wrap"><table style="min-width:970px">
                <thead><tr><th>Event</th><th>Actor</th><th>Module</th><th>IP Address</th><th>Timestamp</th><th>Details</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td><strong>{{ Str::headline($log->action) }}</strong><div class="muted" style="margin-top:5px">#{{ $log->id }} @if($log->record_type) · {{ $log->record_type }} @endif @if($log->record_id) #{{ $log->record_id }} @endif</div></td>
                        <td><strong>{{ $log->actor_name ?? ($log->user_id ? 'User #'.$log->user_id : 'System') }}</strong>@if(!empty($log->actor_username))<div class="muted" style="margin-top:4px">{{ '@'.$log->actor_username }}</div>@endif</td>
                        <td><span class="role-pill">{{ $log->module ?: 'System' }}</span></td>
                        <td class="muted">{{ $log->ip_address ?: '—' }}</td>
                        <td class="muted">{{ $log->created_at ? Carbon::parse($log->created_at)->format('M d, Y · h:i A') : '—' }}</td>
                        <td>@if($log->old_values || $log->new_values || $log->user_agent)<details><summary style="cursor:pointer;color:#ffd078;font-weight:700">View details</summary><div class="audit-detail">@if($log->old_values)<strong>Previous values</strong><pre>{{ $formatChanges($log->old_values) }}</pre>@endif @if($log->new_values)<strong>New values</strong><pre>{{ $formatChanges($log->new_values) }}</pre>@endif @if($log->user_agent)<strong>Browser / device</strong><p style="overflow-wrap:anywhere">{{ Str::limit($log->user_agent, 350) }}</p>@endif</div></details>@else<span class="muted">—</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No audit events match the selected filters.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($pageLogs)
            <div class="pager"><span class="muted">Showing {{ $pageLogs->firstItem() ?? 0 }}–{{ $pageLogs->lastItem() ?? 0 }} of {{ number_format($pageLogs->total()) }} events</span><div class="pager-links">@if($pageLogs->onFirstPage())<span class="filter" style="opacity:.5">Previous</span>@else<a class="filter" href="{{ $pageLogs->previousPageUrl() }}">Previous</a>@endif <span class="muted">Page {{ $pageLogs->currentPage() }} of {{ $pageLogs->lastPage() }}</span> @if($pageLogs->hasMorePages())<a class="filter" href="{{ $pageLogs->nextPageUrl() }}">Next</a>@else<span class="filter" style="opacity:.5">Next</span>@endif</div></div>
            @endif
            <p class="note">Audit records are read-only to preserve accountability. Sensitive keys in JSON change data are masked when displayed.</p>
        </section>
    </main>

</div>

<script>

document.addEventListener('DOMContentLoaded', () => {

    if (window.lucide) window.lucide.createIcons();

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
