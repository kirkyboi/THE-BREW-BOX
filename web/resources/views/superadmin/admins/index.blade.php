@php

    use Illuminate\Support\Facades\DB;

    use Illuminate\Support\Facades\Schema;

    use Illuminate\Support\Facades\Route;

    use Illuminate\Support\Str;



    $viewer = auth()->user();

    $admins = collect();

    $loadError = null;

    try {

        if (Schema::hasTable('users') && Schema::hasTable('roles')) {

            $admins = DB::table('users')

                ->join('roles', 'roles.id', '=', 'users.role_id')

                ->where('roles.slug', 'admin')

                ->when(Schema::hasColumn('users', 'deleted_at'), fn ($q) => $q->whereNull('users.deleted_at'))

                ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.status', 'users.avatar', 'users.created_at')

                ->orderByDesc('users.id')

                ->get();

        } else {

            $loadError = 'Users or roles table is missing. Run your migrations first.';

        }

    } catch (\Throwable $e) {

        report($e);

        $loadError = 'Administrator records could not be loaded. Check the application logs.';

    }

    $total = $admins->count();

    $active = $admins->filter(fn ($a) => strtolower((string) $a->status) === 'active')->count();

    $inactive = $total - $active;

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

        ['Reports', 'chart-no-axes-column-increasing', 'superadmin.reports.index'],

        ['My Profile', 'circle-user-round', 'superadmin.profile.index'],

    ];

@endphp

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Manage Admins | The Brew Box</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

    <style>

        :root{--bg:#140b08;--sidebar:#1b100b;--panel:#27170f;--panel2:#332016;--line:#78502e;--gold:#f1b94f;--gold2:#d48a30;--text:#fff4e4;--muted:#bea994;--side:258px}

        *{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 80% 0,#392014 0,transparent 38%),var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px}button,input,select{font:inherit}button{cursor:pointer}a{color:inherit;text-decoration:none}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}

        .sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;z-index:30;display:flex;flex-direction:column;transition:width .22s,transform .22s}.brand{display:flex;align-items:center;gap:12px;padding:25px 18px 19px;border-bottom:1px solid #6e482a;min-height:85px}.brand-icon{color:var(--gold);flex:none}.brand-icon svg{width:37px;height:37px}.brand-title{font-family:'Playfair Display',serif;font-weight:700;font-size:21px;line-height:1}.brand small{font-size:10px;color:#e3ba79}.side-scroll{padding:14px 10px;overflow-y:auto;overflow-x:hidden;flex:1}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642,0 0 15px #bb792f45;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55;flex:none}.nav-text,.brand-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}

        .workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .22s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cd9;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center;box-shadow:0 0 15px #e7a03850}.icon-button svg{width:21px}.top-title{font-weight:800;letter-spacing:.2px}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{overflow:hidden;flex:none}.avatar img{width:100%;height:100%;object-fit:cover;display:block}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1530px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font-family:'Playfair Display',serif;font-size:clamp(28px,3.2vw,41px);margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:21px}.stat,.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020}.stat{padding:19px;display:flex;align-items:center;gap:15px}.stat-icon{width:51px;height:51px;display:grid;place-items:center;border-radius:13px;background:#7e4a1f;color:#ffd17c;flex:none}.stat-icon svg{width:24px;height:24px}.stat small{display:block;color:#d5bba0;margin-bottom:5px}.stat strong{font-size:30px;line-height:1}.panel{padding:21px}.panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.panel h2{font-size:18px;margin:0}.panel-sub{color:#cbb19a;font-size:12px;margin:7px 0 0}.controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.search{display:flex;align-items:center;gap:8px;background:#1b100b;border:1px solid #815630;border-radius:10px;padding:0 12px}.search svg{width:16px;color:var(--gold)}.search input{width:225px;height:40px;background:transparent;border:0;color:white;outline:none;font-size:12px}.search input::placeholder{color:#a58a74}.filter{height:42px;padding:0 12px;background:#1b100b;color:#f6e4d2;border:1px solid #815630;border-radius:10px}.primary{display:inline-flex;align-items:center;gap:8px;border:1px solid #ffcf6e;background:linear-gradient(110deg,#ffc96c,#b97422);border-radius:10px;padding:12px 16px;color:#261508;font-size:12px;font-weight:800;white-space:nowrap}.primary svg{width:16px}.primary:hover{filter:brightness(1.1)}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:680px}th{padding:13px;text-align:left;font-size:10px;letter-spacing:1.3px;color:#e2b778;text-transform:uppercase;background:#1c100b}td{padding:15px 13px;border-top:1px solid #654229;color:#ead8c7;font-size:12px}td strong{color:white}.initial{display:inline-grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#654020;color:#f7ca85;margin-right:9px;font-weight:800;vertical-align:middle}.pill{padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;display:inline-block}.pill.active{background:#1f4b32;color:#9de6ae}.pill.inactive,.pill.suspended{background:#5d3028;color:#ffb2a1}.muted{color:#bfa38b}.empty{text-align:center;padding:45px 15px;color:#c5a78c;line-height:1.8}.actions{display:flex;gap:7px}.action{background:#422817;border:1px solid #86542c;color:#efc390;border-radius:8px;padding:7px 10px;font-size:11px}.action:disabled{opacity:.55;cursor:not-allowed}.delete-btn{background:#57251f;border-color:#b45c4e;color:#ffb4a9}.delete-btn:hover{background:#793129}.switch{position:relative;display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:11px;font-weight:800;color:#ebc49a}.switch input{position:absolute;opacity:0;width:1px;height:1px}.switch-track{width:43px;height:24px;border-radius:20px;background:#754037;border:1px solid #b16b53;position:relative;transition:.2s}.switch-track:after{content:"";position:absolute;width:17px;height:17px;top:2px;left:3px;border-radius:50%;background:#fff3e4;transition:.2s}.switch input:checked + .switch-track{background:#356d49;border-color:#88d69a}.switch input:checked + .switch-track:after{transform:translateX(18px)}.switch input:focus-visible + .switch-track{outline:2px solid var(--gold);outline-offset:3px}.note{margin-top:14px;font-size:11px;color:#c6a78b;line-height:1.7}.notice{border:1px solid #b26c43;border-radius:10px;padding:12px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.mobile-shade{display:none}.collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}.collapsed .brand-icon svg{width:34px}.hidden-row{display:none}

        @media(max-width:1000px){.stats{grid-template-columns:repeat(3,minmax(0,1fr))}.main{padding:24px 20px}}

        @media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text{display:block}.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}

        @media(max-width:580px){.stats{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:15px}.search input{width:170px}.account small{display:none}.top-title{font-size:12px}}

    </style>

</head>

<body>

<div class="mobile-shade" id="mobileShade"></div>

<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">

    <a class="brand" href="{{ route('superadmin.dashboard') }}">

        <span class="brand-icon"><i data-lucide="coffee"></i></span>

        <span class="brand-text"><span class="brand-title">The Brew Box</span>

        <small>SUPER ADMINISTRATION</small></span>

    </a>

    <nav class="side-scroll">

        <div class="side-label">System Management</div>

        @foreach($menu as [$label, $icon, $name])

            @if(Route::has($name))

                <a class="nav {{ $name === 'superadmin.admins.index' ? 'active' : '' }}" href="{{ route($name) }}" title="{{ $label }}" @if($name === 'superadmin.admins.index') aria-current="page" @endif><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></a>

            @else

                <span class="nav" style="opacity:.5" title="Route not yet registered"><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></span>

            @endif

        @endforeach

    </nav>

    <div class="sidebar-footer">

        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav logout" type="submit"><i data-lucide="log-out"></i><span class="nav-text">Sign Out</span></button></form>

    </div>

</aside>

<div class="workspace">

    <header class="topbar">

        <button class="icon-button" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button>

        <span class="top-title">Super Administrator / Manage Admins</span>

        <span class="top-spacer"></span>

        <div class="account"><span class="avatar">@if(!empty($viewer->avatar))<img src="{{ asset('storage/' . ltrim(preg_replace('#^(storage/|public/)#', '', $viewer->avatar), '/')) }}" alt="Your profile photo">@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></div>

    </header>

    <main class="main">

        <div class="breadcrumbs">THE BREW BOX / SUPERADMIN / MANAGE ADMINS</div>

        <section class="hero">

            <div><div class="eyebrow">Account Administration</div><h1>Manage Administrators</h1><p>View and monitor administrator accounts across The Brew Box platform.</p></div>

            <div class="hero-symbol"><i data-lucide="shield-check"></i></div>

        </section>

        @if($loadError)<div class="notice" role="alert">{{ $loadError }}</div>@endif

        @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif

        <section class="stats" aria-label="Administrator statistics">

            <div class="stat"><span class="stat-icon"><i data-lucide="users-round"></i></span><div><small>Total Administrators</small><strong>{{ number_format($total) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="user-check"></i></span><div><small>Active Administrators</small><strong>{{ number_format($active) }}</strong></div></div>

            <div class="stat"><span class="stat-icon"><i data-lucide="user-x"></i></span><div><small>Inactive / Other</small><strong>{{ number_format($inactive) }}</strong></div></div>

        </section>

        @if(session('success'))<div class="panel" role="status" style="margin-bottom:16px;border-color:#b98a43;color:#e7bb78;font-weight:700">{{ session('success') }}</div>@endif

        <section class="panel">

            <div class="panel-header">

                <div><h2>Administrator Accounts</h2><p class="panel-sub">Only accounts assigned the <strong>admin</strong> role appear here. Superadmin accounts are excluded.</p></div>

                <div class="controls">

                    <label class="search"><i data-lucide="search"></i><input id="adminSearch" type="search" placeholder="Search administrators..." aria-label="Search administrators"></label>

                    <select id="statusFilter" class="filter" aria-label="Filter by account status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive / Other</option></select>

                    @if(Route::has('superadmin.admins.create'))

                        <a class="primary" href="{{ route('superadmin.admins.create') }}"><i data-lucide="user-plus"></i> Add Administrator</a>

                    @else

                        <button class="primary" type="button" id="addAdminInfo"><i data-lucide="user-plus"></i> Add Administrator</button>

                    @endif

                </div>

            </div>

            <div class="table-wrap">

                <table>

                    <thead><tr><th>Administrator</th><th>Username</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>

                    <tbody id="adminRows">

                    @forelse($admins as $admin)

                        <tr data-search="{{ Str::lower($admin->name.' '.$admin->username.' '.$admin->email) }}" data-status="{{ Str::lower((string)$admin->status) }}">

                            <td><span class="initial" style="overflow:hidden">@if(!empty($admin->avatar))<img src="{{ asset('storage/' . ltrim(preg_replace('#^(storage/|public/)#', '', $admin->avatar), '/')) }}" alt="" style="width:35px;height:35px;object-fit:cover">@else{{ Str::upper(Str::substr($admin->name, 0, 1)) }}@endif</span><strong>{{ $admin->name }}</strong></td>

                            <td>{{ $admin->username }}</td>

                            <td>{{ $admin->email }}</td>

                            <td>
                                @if(in_array($admin->status, ['active','inactive'], true))
                                    <form method="POST" action="{{ route('superadmin.admins.toggle-status', $admin->id) }}" class="status-form" data-admin-name="{{ $admin->name }}" data-next-status="{{ $admin->status === 'active' ? 'inactive' : 'active' }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="switch" title="Change administrator status">
                                            <input type="checkbox" {{ $admin->status === 'active' ? 'checked' : '' }} onchange="confirmStatusToggle(this)" aria-label="Toggle status for {{ $admin->name }}">
                                            <span class="switch-track"></span>
                                            <span>{{ ucfirst($admin->status) }}</span>
                                        </label>
                                    </form>
                                @else
                                    <span class="pill suspended">Suspended</span>
                                @endif
                            </td>
                            <td class="muted">{{ $admin->created_at ? \Illuminate\Support\Carbon::parse($admin->created_at)->format('M d, Y') : '—' }}</td>

                            <td>
                                <form method="POST" action="{{ route('superadmin.admins.destroy', $admin->id) }}" class="delete-form" data-admin-name="{{ $admin->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action delete-btn" onclick="confirmDeleteAdmin(this)"><i data-lucide="trash-2" style="width:14px;height:14px;vertical-align:middle"></i> Delete</button>
                                </form>
                            </td>
                        </tr>

                    @empty

                        <tr><td colspan="6" class="empty">No administrator accounts found. Create an account with the <strong>admin</strong> role to see it here.</td></tr>

                    @endforelse

                    </tbody>

                </table>

                <p id="noMatches" class="empty" hidden>No administrators match your search or filter.</p>

            </div>

            <p class="note">Status changes and deletion require confirmation. Deletion moves the administrator to the deleted records (soft delete), preserving historical data.</p>

        </section>

    </main>

</div>

<script>

async function confirmStatusToggle(input) {
    const form = input.closest('form');
    const next = form.dataset.nextStatus;
    const name = form.dataset.adminName;
    let approved;
    if (window.Swal) {
        const result = await Swal.fire({title: next === 'inactive' ? 'Deactivate administrator?' : 'Activate administrator?', text: `${name} will be set to ${next}.`, icon:'warning', showCancelButton:true, confirmButtonText: next === 'inactive' ? 'Yes, deactivate' : 'Yes, activate', confirmButtonColor:'#b77a32', cancelButtonColor:'#6c5140', background:'#291810', color:'#fff4e4'});
        approved = result.isConfirmed;
    } else {
        approved = confirm(`Change ${name}'s status to ${next}?`);
    }
    if (!approved) { input.checked = !input.checked; return; }
    input.disabled = true;
    form.submit();
}
async function confirmDeleteAdmin(button) {
    const form = button.closest('form');
    const name = form.dataset.adminName;
    let approved;
    if (window.Swal) {
        const result = await Swal.fire({title:'Delete administrator?', text:`${name} will be removed from active administrator records. This action cannot be undone from this page.`, icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete', confirmButtonColor:'#b83b37', cancelButtonColor:'#6c5140', background:'#291810', color:'#fff4e4'});
        approved = result.isConfirmed;
    } else {
        approved = confirm(`Delete administrator ${name}? This action cannot be undone from this page.`);
    }
    if (approved) { button.disabled = true; form.submit(); }
}
document.addEventListener('DOMContentLoaded', () => {

    if (window.lucide) lucide.createIcons();

    const body = document.body;

    const toggle = document.getElementById('sidebarToggle');

    const shade = document.getElementById('mobileShade');

    const mobile = () => window.matchMedia('(max-width:800px)').matches;

    const closeMobile = () => {body.classList.remove('mobile-open');toggle.setAttribute('aria-expanded','false')};

    toggle.addEventListener('click', () => {

        if (mobile()) { const open = body.classList.toggle('mobile-open');toggle.setAttribute('aria-expanded',String(open)); }

        else {const collapsed=body.classList.toggle('collapsed');toggle.setAttribute('aria-expanded',String(!collapsed));try{localStorage.setItem('brew_superadmin_sidebar_collapsed',collapsed?'1':'0')}catch(e){}}

    });

    shade.addEventListener('click', closeMobile);

    document.addEventListener('keydown', e => {if(e.key==='Escape')closeMobile()});

    try {if(!mobile()&&localStorage.getItem('brew_superadmin_sidebar_collapsed')==='1'){body.classList.add('collapsed');toggle.setAttribute('aria-expanded','false')}}catch(e){}

    window.addEventListener('resize', () => {if(!mobile())closeMobile()});

    const search=document.getElementById('adminSearch'), filter=document.getElementById('statusFilter');

    const rows=[...document.querySelectorAll('#adminRows tr[data-search]')];

    const noMatches=document.getElementById('noMatches');

    const apply=()=>{let shown=0;rows.forEach(row=>{const term=search.value.trim().toLowerCase();const status=row.dataset.status;const ok=row.dataset.search.includes(term)&&(filter.value==='all'||(filter.value==='active'?status==='active':status!=='active'));row.classList.toggle('hidden-row',!ok);if(ok)shown++});noMatches.hidden=shown>0||rows.length===0};

    search.addEventListener('input',apply);filter.addEventListener('change',apply);

    document.getElementById('addAdminInfo')?.addEventListener('click',()=>alert('The Add Administrator form is not installed yet. Create a protected superadmin.admins.create route, controller, and form before enabling account creation.'));

});

</script>

</body>

</html>
