@php

    use Illuminate\Support\Facades\DB;

    use Illuminate\Support\Facades\Schema;

    use Illuminate\Support\Facades\Route;

    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $viewerAvatar = filled($viewer?->avatar) ? asset('storage/' . ltrim($viewer->avatar, '/')) : null;

    $roles = collect();

    $loadError = null;

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

    try {

        if (Schema::hasTable('roles') && Schema::hasTable('users')) {

            $query = DB::table('roles')->leftJoin('users', function ($join) {
                $join->on('users.role_id', '=', 'roles.id');
                if (Schema::hasColumn('users', 'deleted_at')) {
                    $join->whereNull('users.deleted_at');
                }
            });

            if (Schema::hasColumn('users', 'deleted_at')) {

                $query->whereNull('users.deleted_at');

            }

            $roles = $query->select('roles.id', 'roles.name', 'roles.slug', 'roles.description', 'roles.created_at', DB::raw('COUNT(users.id) AS users_count'))

                ->groupBy('roles.id', 'roles.name', 'roles.slug', 'roles.description', 'roles.created_at')

                ->orderBy('roles.id')->get();

        } else {

            $loadError = 'Roles or users table is missing. Run your database migrations.';

        }

    } catch (\Throwable $e) {

        report($e);

        $loadError = 'Could not load roles. Check storage/logs/laravel.log.';

    }

    $totalRoles = $roles->count();

    $assignedRoles = $roles->filter(fn ($r) => (int) $r->users_count > 0)->count();

    $unassignedRoles = $totalRoles - $assignedRoles;

    $totalAssignedUsers = $roles->sum('users_count');

@endphp

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Roles | The Brew Box</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>

    <style>

        :root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}

        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}svg{flex-shrink:0}

        .sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:width .2s,transform .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55}.nav-text,.brand-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}

        .workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.icon-button svg{width:21px}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800;overflow:hidden;flex-shrink:0;border:2px solid #d9a45c}.avatar img{width:100%;height:100%;object-fit:cover;display:block}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1530px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}

        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:21px}.stat,.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020}.stat{padding:19px;display:flex;align-items:center;gap:13px}.stat-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:13px;background:#7e4a1f;color:#ffd17c}.stat-icon svg{width:23px}.stat small{display:block;color:#d5bba0;margin-bottom:5px}.stat strong{font-size:27px}.panel{padding:21px}.panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.panel h2{font-size:18px;margin:0}.panel-sub{color:#cbb19a;font-size:12px;margin:7px 0 0}.controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.search{display:flex;align-items:center;gap:8px;background:#1b100b;border:1px solid #815630;border-radius:10px;padding:0 12px}.search svg{width:16px;color:var(--gold)}.search input{width:210px;height:40px;background:transparent;border:0;color:white;outline:none;font-size:12px}.search input::placeholder{color:#a58a74}.filter{height:42px;padding:0 12px;background:#1b100b;color:#f6e4d2;border:1px solid #815630;border-radius:10px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:790px}th{padding:13px;text-align:left;font-size:10px;letter-spacing:1.3px;color:#e2b778;text-transform:uppercase;background:#1c100b}td{padding:15px 13px;border-top:1px solid #654229;color:#ead8c7;font-size:12px}td strong{color:white}.initial{display:inline-grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#654020;color:#f7ca85;margin-right:9px;font-weight:800;vertical-align:middle}.pill{padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;display:inline-block}.pill.active{background:#1f4b32;color:#9de6ae}.pill.inactive{background:#5d3028;color:#ffb2a1}.role-pill{display:inline-block;border:1px solid #9b6c39;color:#f1c589;border-radius:8px;padding:6px 9px;font-weight:700;font-size:11px}.muted{color:#bfa38b}.empty{text-align:center;padding:45px 15px;color:#c5a78c;line-height:1.8}.note{margin-top:14px;font-size:11px;color:#c6a78b;line-height:1.7}.notice{border:1px solid #b26c43;border-radius:10px;padding:12px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.hidden-row{display:none!important}.mobile-shade{display:none}.count{font-size:12px;color:#e4b875;font-weight:700}

        .collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}.collapsed .brand-icon svg{width:34px}

        @media(max-width:1150px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.stats{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:15px}.search input{width:170px}.account small{display:none}.top-title{font-size:12px}}

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

                <a class="nav {{ $routeName === 'superadmin.roles.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.roles.index') aria-current="page" @endif>

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

        <span class="top-title">Super Administrator / Roles</span><span class="top-spacer"></span>

        <div class="account"><span class="avatar">@if($viewerAvatar)<img src="{{ $viewerAvatar }}" alt="Profile photo" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"><span style="display:none;width:100%;height:100%;place-items:center">{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}</span>@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></div>

    </header>

    <main class="main">

        <div class="breadcrumbs">THE BREW BOX / SUPERADMIN / ROLES</div>

        <section class="hero">

            <div><div class="eyebrow">Access Management</div><h1>Roles & Access</h1><p>Review the seven system roles, descriptions, and assigned user counts in one place.</p></div>

            <div class="hero-symbol"><i data-lucide="shield-check"></i></div>

        </section>

        @if($loadError)<div class="notice" role="alert">{{ $loadError }}</div>@endif

        <section class="stats" aria-label="Role statistics">

            <div class="stat"><span class="stat-icon"><i data-lucide="shield"></i></span><div><small>Total Roles</small><strong>{{ number_format($totalRoles) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="users"></i></span><div><small>Assigned Users</small><strong>{{ number_format($totalAssignedUsers) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="shield-check"></i></span><div><small>Roles In Use</small><strong>{{ number_format($assignedRoles) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="shield-off"></i></span><div><small>Unassigned Roles</small><strong>{{ number_format($unassignedRoles) }}</strong></div></div>

        </section>

        <section class="panel">

            <div class="panel-header">

                <div><h2>System Roles</h2><p class="panel-sub">Roles are loaded from your database. User counts reflect current assignments.</p></div>

                <div class="controls"><label class="search"><i data-lucide="search"></i><input id="roleSearch" type="search" placeholder="Search roles..." aria-label="Search roles"></label>

                <select class="filter" id="usageFilter" aria-label="Filter role usage"><option value="all">All roles</option><option value="used">In use</option><option value="unused">Unassigned</option></select></div>

            </div>

            <div class="table-wrap"><table>

                <thead><tr><th>Role</th><th>Slug</th><th>Description</th><th>Users</th><th>Created At</th><th>Access</th></tr></thead>

                <tbody id="roleRows">

                @forelse($roles as $role)

                    <tr data-search="{{ Str::lower(($role->name ?? '').' '.($role->slug ?? '').' '.($role->description ?? '')) }}" data-usage="{{ (int)$role->users_count > 0 ? 'used' : 'unused' }}">

                        <td><span class="initial"><i data-lucide="shield" style="width:17px;height:17px"></i></span><strong>{{ $role->name }}</strong></td>

                        <td><span class="role-pill">{{ $role->slug }}</span></td>

                        <td class="muted">{{ $role->description ?: 'No description provided' }}</td>

                        <td><strong>{{ number_format($role->users_count) }}</strong></td>

                        <td class="muted">@if(!empty($role->created_at))<time datetime="{{ \Illuminate\Support\Carbon::parse($role->created_at)->toIso8601String() }}" title="{{ \Illuminate\Support\Carbon::parse($role->created_at)->format('F j, Y g:i:s A') }}">{{ \Illuminate\Support\Carbon::parse($role->created_at)->format('M d, Y') }}<br><small style="color:#d9aa70">{{ \Illuminate\Support\Carbon::parse($role->created_at)->format('h:i A') }}</small></time>@else<span title="No created_at value stored in the database">Not recorded</span>@endif</td>

                        <td><span class="pill {{ (int)$role->users_count > 0 ? 'active' : 'inactive' }}">{{ (int)$role->users_count > 0 ? 'In use' : 'Unassigned' }}</span></td>

                    </tr>

                @empty

                    <tr><td colspan="6" class="empty">No roles found in the database.</td></tr>

                @endforelse

                </tbody>

            </table></div>

            <p id="noMatches" class="empty" hidden>No roles match your search or filter.</p>

            <p class="note"><span class="count" id="visibleCount">{{ $totalRoles }}</span> of {{ $totalRoles }} roles shown. This page is read-only; creating, editing, or deleting roles requires authorized controller actions and routes.</p>

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

    const search = document.getElementById('roleSearch');

    const usage = document.getElementById('usageFilter');

    const rows = [...document.querySelectorAll('#roleRows tr[data-search]')];

    const empty = document.getElementById('noMatches');

    const count = document.getElementById('visibleCount');

    const apply = () => {

        const term = search.value.trim().toLowerCase(); let shown = 0;

        rows.forEach(row => {

            const match = row.dataset.search.includes(term) && (usage.value === 'all' || row.dataset.usage === usage.value);

            row.classList.toggle('hidden-row', !match);

            if (match) shown++;

        });

        empty.hidden = shown > 0 || rows.length === 0;

        count.textContent = String(shown);

    };

    search.addEventListener('input', apply);

    usage.addEventListener('change', apply);

});

</script>

</body>

</html>
