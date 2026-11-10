@php

    use Illuminate\Support\Facades\DB;

    use Illuminate\Support\Facades\Schema;

    use Illuminate\Support\Facades\Route;

    use Illuminate\Support\Str;



    $viewer = auth()->user();
    $avatarPath = trim((string) ($viewer->avatar ?? ''));
    $avatarPath = preg_replace('#^(?:/)?(?:storage/|public/)#', '', $avatarPath);
    $viewerAvatarUrl = $avatarPath !== '' ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatarPath) : null;

    $users = collect();

    $loadError = null;

    $roleNames = ['superadmin', 'admin', 'manager', 'cashier', 'barista', 'staff', 'customer'];



    try {

        if (Schema::hasTable('users') && Schema::hasTable('roles')) {

            $query = DB::table('users')->leftJoin('roles', 'roles.id', '=', 'users.role_id');

            if (Schema::hasColumn('users', 'deleted_at')) {

                $query->whereNull('users.deleted_at');

            }

            $users = $query->select(

                'users.id', 'users.name', 'users.username', 'users.email',

                'users.status', 'users.created_at', 'users.avatar', 'roles.slug as role_slug',

                'roles.name as role_name'

            )->orderByDesc('users.id')->get();

        } else {

            $loadError = 'Users or roles table is missing. Run your database migrations.';

        }

    } catch (\Throwable $e) {

        report($e);

        $loadError = 'Could not load user records. Please check storage/logs/laravel.log.';

    }



    $totalUsers = $users->count();

    $activeUsers = $users->filter(fn ($u) => strtolower((string) $u->status) === 'active')->count();

    $inactiveUsers = $totalUsers - $activeUsers;

    $rolesInUse = $users->pluck('role_slug')->filter()->unique()->count();



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

@endphp

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>All Users | The Brew Box</title>

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

    .avatar,.initial{overflow:hidden;position:relative}.avatar-image,.initial-image{width:100%;height:100%;object-fit:cover;display:block}.avatar-fallback{width:100%;height:100%;align-items:center;justify-content:center}.status-toggle{display:inline-flex;align-items:center;gap:9px;background:transparent;border:0;color:#ffb2a1;font-weight:800;font-size:11px;padding:5px;cursor:pointer}.status-toggle.is-active{color:#9de6ae}.toggle-track{width:37px;height:21px;border-radius:20px;background:#754335;display:flex;align-items:center;padding:3px;transition:background .2s}.toggle-knob{width:15px;height:15px;background:#fff4e4;border-radius:50%;transition:transform .2s}.is-active .toggle-track{background:#2e8254}.is-active .toggle-knob{transform:translateX(16px)}.status-toggle:disabled{opacity:.5;cursor:wait}
    </style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
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

                <a class="nav {{ $routeName === 'superadmin.users.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.users.index') aria-current="page" @endif>

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

        <span class="top-title">Super Administrator / All Users</span><span class="top-spacer"></span>

        <div class="account"><span class="avatar">@if($viewerAvatarUrl)<img src="{{ $viewerAvatarUrl }}" alt="Profile photo" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'" class="avatar-image"><span class="avatar-fallback" style="display:none">{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}</span>@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></div>

    </header>

    <main class="main">

        <div class="breadcrumbs">THE BREW BOX / SUPERADMIN / ALL USERS</div>

        <section class="hero">

            <div><div class="eyebrow">Platform Accounts</div><h1>All Users</h1><p>View every account across all seven roles. Search, filter, and securely manage account status.</p></div>

            <div class="hero-symbol"><i data-lucide="users-round"></i></div>

        </section>

        @if($loadError)<div class="notice" role="alert">{{ $loadError }}</div>@endif

        <section class="stats" aria-label="User statistics">

            <div class="stat"><span class="stat-icon"><i data-lucide="users"></i></span><div><small>Total Users</small><strong>{{ number_format($totalUsers) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="user-check"></i></span><div><small>Active Accounts</small><strong>{{ number_format($activeUsers) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="user-x"></i></span><div><small>Inactive / Other</small><strong>{{ number_format($inactiveUsers) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="shield-check"></i></span><div><small>Roles In Use</small><strong>{{ number_format($rolesInUse) }}</strong></div></div>

        </section>

        <section class="panel">

            <div class="panel-header">

                <div><h2>Registered Accounts</h2><p class="panel-sub">All seven roles are included. Superadmin accounts and your own account are protected from status changes.</p></div>

                <div class="controls">

                    <label class="search"><i data-lucide="search"></i><input id="userSearch" type="search" placeholder="Search users..." aria-label="Search users"></label>

                    <select class="filter" id="roleFilter" aria-label="Filter by role"><option value="all">All roles</option>@foreach($roleNames as $roleName)<option value="{{ $roleName }}">{{ ucfirst($roleName) }}</option>@endforeach<option value="unknown">Unknown role</option></select>

                    <select class="filter" id="statusFilter" aria-label="Filter by status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive / Other</option></select>

                </div>

            </div>

            <div class="table-wrap"><table>

                <thead><tr><th>User</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th></tr></thead>

                <tbody id="userRows">

                @forelse($users as $user)

                    @php

                        $roleSlug = Str::lower((string) ($user->role_slug ?? 'unknown'));

                        $status = Str::lower((string) ($user->status ?? 'unknown'));
                        $photoPath = trim((string) ($user->avatar ?? ''));
                        $photoPath = preg_replace('#^(?:/)?(?:storage/|public/)#', '', $photoPath);
                        $photoUrl = $photoPath !== '' ? \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) : null;
                        $canToggle = $roleSlug !== 'superadmin' && (int) $user->id !== (int) $viewer->id && in_array($status, ['active','inactive'], true);

                    @endphp

                    <tr data-search="{{ Str::lower(($user->name ?? '').' '.($user->username ?? '').' '.($user->email ?? '').' '.$roleSlug) }}" data-role="{{ $roleSlug }}" data-status="{{ $status }}">

                        <td><span class="initial">@if($photoUrl)<img src="{{ $photoUrl }}" alt="" class="initial-image" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span class="avatar-fallback" style="display:none">{{ Str::upper(Str::substr($user->name ?? '?', 0, 1)) }}</span>@else{{ Str::upper(Str::substr($user->name ?? '?', 0, 1)) }}@endif</span><strong>{{ $user->name ?? 'Unnamed user' }}</strong></td>

                        <td>{{ $user->username ?? '—' }}</td>

                        <td>{{ $user->email ?? '—' }}</td>

                        <td><span class="role-pill">{{ $user->role_name ?: ucfirst($roleSlug) }}</span></td>

                        <td>
                            @if($canToggle && Route::has('superadmin.users.toggle-status'))
                                <form class="status-form" method="POST" action="{{ route('superadmin.users.toggle-status', $user->id) }}" data-name="{{ $user->name }}" data-next="{{ $status === 'active' ? 'deactivate' : 'activate' }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="status-toggle {{ $status === 'active' ? 'is-active' : '' }}" role="switch" aria-checked="{{ $status === 'active' ? 'true' : 'false' }}" aria-label="Change status for {{ $user->name }}">
                                        <span class="toggle-track"><span class="toggle-knob"></span></span><span>{{ ucfirst($status) }}</span>
                                    </button>
                                </form>
                            @else
                                <span class="pill {{ $status === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($status) }}</span>
                            @endif
                        </td>

                        <td class="muted">{{ $user->created_at ? \Illuminate\Support\Carbon::parse($user->created_at)->format('M d, Y') : '—' }}</td>

                    </tr>

                @empty

                    <tr><td colspan="6" class="empty">No user accounts found.</td></tr>

                @endforelse

                </tbody>

            </table></div>

            <p id="noMatches" class="empty" hidden>No users match your search or filters.</p>

            <p class="note"><span class="count" id="visibleCount">{{ $totalUsers }}</span> of {{ $totalUsers }} accounts shown. Status changes require confirmation and are enforced by the server. Superadmin accounts cannot be toggled here.</p>

        </section>

    </main>

</div>

<script>

document.addEventListener('DOMContentLoaded', () => {

    if (window.lucide) window.lucide.createIcons();

    document.querySelectorAll('.status-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const action = form.dataset.next;
            const name = form.dataset.name;
            let approved = false;
            if (window.Swal) {
                const result = await Swal.fire({title: `${action === 'activate' ? 'Activate' : 'Deactivate'} account?`, text: `Are you sure you want to ${action} ${name}?`, icon: 'warning', showCancelButton: true, confirmButtonText: `Yes, ${action}`, cancelButtonText: 'Cancel', confirmButtonColor: action === 'activate' ? '#287d50' : '#c46c37', cancelButtonColor: '#705343', background: '#28170f', color: '#fff4e4'});
                approved = result.isConfirmed;
            } else {
                approved = window.confirm(`Are you sure you want to ${action} ${name}?`);
            }
            if (approved) { form.querySelector('button').disabled = true; HTMLFormElement.prototype.submit.call(form); }
        });
    });
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

    const search = document.getElementById('userSearch');

    const role = document.getElementById('roleFilter');

    const status = document.getElementById('statusFilter');

    const rows = [...document.querySelectorAll('#userRows tr[data-search]')];

    const empty = document.getElementById('noMatches');

    const count = document.getElementById('visibleCount');

    const apply = () => {

        const term = search.value.trim().toLowerCase(); let shown = 0;

        rows.forEach(row => {

            const match = row.dataset.search.includes(term) && (role.value === 'all' || row.dataset.role === role.value) && (status.value === 'all' || (status.value === 'active' ? row.dataset.status === 'active' : row.dataset.status !== 'active'));

            row.classList.toggle('hidden-row', !match); if (match) shown++;

        });

        empty.hidden = shown > 0 || rows.length === 0;

        count.textContent = String(shown);

    };

    search.addEventListener('input', apply); role.addEventListener('change', apply); status.addEventListener('change', apply);

});

</script>

</body>

</html>
