@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $viewer = auth()->user();
    $avatar = filled($viewer?->avatar) ? asset('storage/' . ltrim($viewer->avatar, '/')) : null;
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
        ['Reports','chart-column-increasing','superadmin.reports.index'],
        ['My Profile','circle-user-round','superadmin.profile.index'],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>System Monitor | The Brew Box</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<style>
:root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);min-height:100vh;color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,textarea,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:2px solid var(--gold);outline-offset:2px}.sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:transform .2s,width .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55;flex-shrink:0}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}.workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800;overflow:hidden;flex-shrink:0}.avatar img{width:100%;height:100%;object-fit:cover}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1450px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}.layout{display:grid;grid-template-columns:235px minmax(0,1fr);gap:20px;align-items:start}.tabs{border:1px solid #98652f;border-radius:16px;background:linear-gradient(145deg,#352117,#21130e);padding:12px;position:sticky;top:100px}.tab{width:100%;display:flex;gap:12px;align-items:center;text-align:left;padding:13px 12px;border:0;border-radius:10px;background:none;color:#d5baa0;font-weight:700;margin:2px 0}.tab svg{width:18px;color:#e7ad55}.tab.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:white}.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020;padding:26px;margin-bottom:18px}.panel h2{margin:0;font-size:19px}.panel p{font-size:12px;color:#cbb19a;line-height:1.7;margin:7px 0 23px}.section-heading{display:flex;align-items:center;gap:12px;margin-bottom:8px}.section-heading>span{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;background:#7e4a1f;color:#ffd17c}.section-heading svg{width:20px}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}.field label{font-size:12px;color:#f1d0a4;font-weight:700}.field input,.field select,.field textarea{width:100%;min-height:44px;padding:12px 14px;border-radius:10px;background:#1b100b;color:#fff4e4;border:1px solid #815630}.field textarea{resize:vertical;min-height:92px}.hint{color:#ad927c;font-size:11px}.switch-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 0;border-bottom:1px solid #62432c}.switch-row:last-child{border-bottom:0}.switch-row strong{display:block;font-size:13px}.switch-row small{display:block;color:#cbb19a;margin-top:5px;line-height:1.6}.switch{position:relative;display:inline-block;width:49px;height:27px;flex-shrink:0}.switch input{position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}.switch-track{position:absolute;inset:0;background:#644434;border-radius:20px;transition:.2s;pointer-events:none}.switch-track:before{content:'';position:absolute;width:19px;height:19px;left:4px;top:4px;background:#fff;border-radius:50%;transition:.2s}.switch input:checked+.switch-track{background:#e2a83d}.switch input:checked+.switch-track:before{transform:translateX(22px)}.switch input:focus-visible+.switch-track{outline:2px solid white;outline-offset:3px}.actions{display:flex;justify-content:flex-end;gap:12px;align-items:center;margin:20px 0}.save{background:linear-gradient(110deg,#e6b25b,#b97624);color:#201108;border:1px solid #f7ce7c;padding:14px 24px;border-radius:11px;font-weight:800}.note{color:#cbb19a;font-size:12px}.notice{border:1px solid #b26c43;border-radius:10px;padding:13px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.errors{border-color:#d86b5d}.collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}@media(max-width:1000px){.layout{grid-template-columns:1fr}.tabs{position:static;display:flex;flex-wrap:wrap}.tab{width:auto}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.fields{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:18px}.main{padding:20px 15px}.account small,.account strong{display:none}.top-title{font-size:12px}}.mobile-shade{display:none}
</style>

<style>
.backup-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin:0 0 22px}.metric{padding:20px;background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px}.metric small{display:block;color:#cbb19a;margin-bottom:9px}.metric strong{font-size:26px;color:#fff4e4}.backup-head{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}.backup-head h2{margin:0}.backup-head p{margin:8px 0;color:#cbb19a;line-height:1.6}.backup-btn{border:1px solid #f7ce7c;border-radius:10px;padding:12px 17px;background:linear-gradient(110deg,#e6b25b,#b97624);color:#201108;font-weight:800;display:inline-flex;gap:8px;align-items:center}.backup-btn svg{width:17px}.backup-btn.secondary{background:#24150e;border-color:#976b40;color:#f5d6a2}.backup-btn.danger{background:#42201d;border-color:#ab594c;color:#ffc0b9}.backup-btn:disabled{opacity:.5;cursor:not-allowed}.backup-table{width:100%;border-collapse:collapse;min-width:760px}.backup-table th{padding:14px;text-align:left;color:#e2b778;background:#1c100b;font-size:11px;text-transform:uppercase;letter-spacing:1px}.backup-table td{padding:16px 14px;border-top:1px solid #654229;color:#ead8c7}.backup-table td strong{color:#fff4e4}.backup-table-wrap{overflow:auto;margin-top:20px}.file-icon{width:36px;height:36px;background:#6a4221;color:#f1b94f;border-radius:10px;display:inline-grid;place-items:center;margin-right:9px;vertical-align:middle}.file-icon svg{width:18px}.actions-inline{display:flex;gap:8px}.hintbox{border:1px solid #8b663d;background:#24170f;border-radius:13px;padding:16px 18px;color:#d8bfa4;line-height:1.7;margin-top:18px}.hintbox strong{color:#f1b94f}.notice-ok{background:#20412c;border-color:#4d9861;color:#bdf4cb}.empty{text-align:center;padding:44px;color:#cbb19a}.muted{color:#bca38e}.mobile-shade{display:none}@media(max-width:900px){.backup-grid{grid-template-columns:1fr}}@media(max-width:580px){.main{padding:20px 15px}.backup-head{align-items:stretch}.backup-btn{justify-content:center}}
.monitor-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}.monitor-grid .metric strong{font-size:20px}.monitor-grid .metric p{font-size:12px;color:#cbb19a;margin:8px 0 0}.status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:8px}.status-dot.good{background:#60d394}.status-dot.bad{background:#f08070}.monitor-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.info-row{display:flex;justify-content:space-between;gap:18px;padding:14px 0;border-bottom:1px solid #654229}.info-row span{color:#cbb19a}.info-row strong{text-align:right;overflow-wrap:anywhere}.healthy{color:#80e1a1}.unhealthy{color:#ff9587}.checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.check{display:flex;align-items:center;gap:13px;border:1px solid #69462d;background:#21140d;padding:16px;border-radius:12px}.check>svg{color:#e8b568;flex-shrink:0}.check>div{flex:1}.check strong,.check small{display:block}.check small{color:#cbb19a;margin-top:6px;line-height:1.5}.pill{font-size:10px;font-weight:800;border-radius:8px;padding:6px 9px}.pill.ok{background:#204b34;color:#a8eac0}.pill.warn{background:#6b3026;color:#ffd0b7}.monitor-bottom{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;color:#cbb19a;font-size:12px}.monitor-bottom>span{display:flex;align-items:center;gap:8px}.monitor-bottom svg{width:17px;height:17px}@media(max-width:1150px){.monitor-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:850px){.monitor-two,.checks{grid-template-columns:1fr}}@media(max-width:520px){.monitor-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="mobile-shade" id="mobileShade"></div>
<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">
<a class="brand" href="{{ route('superadmin.dashboard') }}"><span class="brand-icon"><i data-lucide="coffee"></i></span><span class="brand-text"><span class="brand-title">The Brew Box</span><small>SUPER ADMINISTRATION</small></span></a>
<nav class="side-scroll"><div class="side-label">System Management</div>
@foreach($menu as [$label,$icon,$routeName])
@if(Route::has($routeName))
<a class="nav {{ $routeName === 'superadmin.system-monitor.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.system-monitor.index') aria-current="page" @endif><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></a>
@else
<span class="nav" style="opacity:.5" title="Route not yet registered"><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></span>
@endif
@endforeach
</nav>
<div class="sidebar-footer"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav logout" type="submit"><i data-lucide="log-out"></i><span class="nav-text">Sign Out</span></button></form></div>
</aside>
<div class="workspace">
<header class="topbar"><button class="icon-button" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button><span class="top-title">Super Administrator / System Monitor</span><span class="top-spacer"></span>
<a class="account" href="{{ Route::has('superadmin.profile.index') ? route('superadmin.profile.index') : '#' }}"><span class="avatar">@if($avatar)<img src="{{ $avatar }}" alt="Profile photo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'"><span style="display:none">{{ Str::upper(Str::substr($viewer?->name ?? 'S',0,1)) }}</span>@else{{ Str::upper(Str::substr($viewer?->name ?? 'S',0,1)) }}@endif</span><span><strong>{{ $viewer?->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></a>
</header>
<main class="main">
<div class="breadcrumbs">THE BREW BOX / SUPERADMIN / SYSTEM MONITOR</div>
<section class="hero"><div><div class="eyebrow">Infrastructure Overview</div><h1>System Monitor</h1><p>Real-time application health, database connectivity, server environment, and operational diagnostics.</p></div><div class="hero-symbol"><i data-lucide="activity"></i></div></section>
<div class="monitor-grid">
 <article class="metric"><small>Application</small><strong><span class="status-dot good"></span> Online</strong><p>Laravel {{ $monitor['laravel'] }}</p></article>
 <article class="metric"><small>Database</small><strong><span class="status-dot {{ $monitor['database_ok'] ? 'good' : 'bad' }}"></span>{{ $monitor['database_ok'] ? 'Connected' : 'Unavailable' }}</strong><p>{{ $monitor['database_driver'] }}</p></article>
 <article class="metric"><small>Storage</small><strong><span class="status-dot {{ $monitor['storage_ok'] ? 'good' : 'bad' }}"></span>{{ $monitor['storage_ok'] ? 'Writable' : 'Check permissions' }}</strong><p>Application storage</p></article>
 <article class="metric"><small>Environment</small><strong>{{ ucfirst($monitor['environment']) }}</strong><p>Debug {{ $monitor['debug'] ? 'enabled' : 'disabled' }}</p></article>
</div>
<div class="monitor-two">
<section class="panel"><div class="section-heading"><span><i data-lucide="server"></i></span><div><h2>Server Information</h2><p>Current runtime and host details</p></div></div>
<div class="info-row"><span>PHP version</span><strong>{{ $monitor['php'] }}</strong></div>
<div class="info-row"><span>Operating system</span><strong>{{ $monitor['os'] }}</strong></div>
<div class="info-row"><span>Web server</span><strong>{{ $monitor['server'] }}</strong></div>
<div class="info-row"><span>PHP memory limit</span><strong>{{ $monitor['memory_limit'] }}</strong></div>
<div class="info-row"><span>Max upload size</span><strong>{{ $monitor['upload_limit'] }}</strong></div>
<div class="info-row"><span>Timezone</span><strong>{{ $monitor['timezone'] }}</strong></div>
</section>
<section class="panel"><div class="section-heading"><span><i data-lucide="database"></i></span><div><h2>Database &amp; Storage</h2><p>Connectivity and available capacity</p></div></div>
<div class="info-row"><span>Database driver</span><strong>{{ $monitor['database_driver'] }}</strong></div>
<div class="info-row"><span>Database status</span><strong class="{{ $monitor['database_ok'] ? 'healthy' : 'unhealthy' }}">{{ $monitor['database_ok'] ? 'Healthy' : 'Connection failed' }}</strong></div>
<div class="info-row"><span>Database latency</span><strong>{{ $monitor['db_latency_ms'] !== null ? number_format($monitor['db_latency_ms'], 1) . ' ms' : 'Unavailable' }}</strong></div>
<div class="info-row"><span>Disk free</span><strong>{{ $monitor['disk_free'] !== null ? number_format($monitor['disk_free'] / 1073741824, 2) . ' GB' : 'Unavailable' }}</strong></div>
<div class="info-row"><span>Disk total</span><strong>{{ $monitor['disk_total'] !== null ? number_format($monitor['disk_total'] / 1073741824, 2) . ' GB' : 'Unavailable' }}</strong></div>
<div class="info-row"><span>Cache driver</span><strong>{{ $monitor['cache_driver'] }}</strong></div>
</section>
</div>
<section class="panel"><div class="section-heading"><span><i data-lucide="shield-check"></i></span><div><h2>Application Checks</h2><p>Diagnostic checks are read-only and do not change system configuration</p></div></div>
<div class="checks">
<div class="check"><i data-lucide="{{ $monitor['database_ok'] ? 'check-circle-2' : 'alert-triangle' }}"></i><div><strong>Database connection</strong><small>{{ $monitor['database_ok'] ? 'A simple SELECT query completed successfully.' : 'The database did not respond to the health check.' }}</small></div><span class="pill {{ $monitor['database_ok'] ? 'ok' : 'warn' }}">{{ $monitor['database_ok'] ? 'PASS' : 'FAIL' }}</span></div>
<div class="check"><i data-lucide="{{ $monitor['storage_ok'] ? 'check-circle-2' : 'alert-triangle' }}"></i><div><strong>Storage permissions</strong><small>Laravel storage directory must be writable.</small></div><span class="pill {{ $monitor['storage_ok'] ? 'ok' : 'warn' }}">{{ $monitor['storage_ok'] ? 'PASS' : 'FAIL' }}</span></div>
<div class="check"><i data-lucide="{{ $monitor['cache_ok'] ? 'check-circle-2' : 'alert-triangle' }}"></i><div><strong>Cache connectivity</strong><small>Cache write, read and cleanup test.</small></div><span class="pill {{ $monitor['cache_ok'] ? 'ok' : 'warn' }}">{{ $monitor['cache_ok'] ? 'PASS' : 'FAIL' }}</span></div>
<div class="check"><i data-lucide="{{ !$monitor['debug'] ? 'check-circle-2' : 'info' }}"></i><div><strong>Debug mode</strong><small>Debug mode should be disabled on a public production server.</small></div><span class="pill {{ !$monitor['debug'] ? 'ok' : 'warn' }}">{{ !$monitor['debug'] ? 'OFF' : 'ON' }}</span></div>
</div></section>
<div class="monitor-bottom"><span><i data-lucide="clock-3"></i> Snapshot captured {{ $monitor['checked_at'] }}</span><a class="backup-btn" href="{{ route('superadmin.system-monitor.index') }}"><i data-lucide="refresh-cw"></i> Refresh Status</a></div>
</main></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  window.lucide?.createIcons();
  const toggle = document.getElementById('sidebarToggle');
  const shade = document.getElementById('mobileShade');
  toggle?.addEventListener('click', () => {
    if (window.innerWidth <= 800) document.body.classList.toggle('mobile-open');
    else document.body.classList.toggle('collapsed');
    toggle.setAttribute('aria-expanded', String(!document.body.classList.contains('collapsed')));
  });
  shade?.addEventListener('click', () => document.body.classList.remove('mobile-open'));
});
</script></body></html>
