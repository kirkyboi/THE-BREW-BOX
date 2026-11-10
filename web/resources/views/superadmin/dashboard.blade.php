@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;
    $currentUser = auth()->user();
    // Always resolve the authenticated user's latest saved avatar.
    $freshUser = $currentUser ? DB::table('users')->where('id', $currentUser->id)->first(['name', 'avatar']) : null;
    $dashboardName = $freshUser->name ?? $currentUser->name ?? 'Superadmin';
    $avatarPath = trim((string) ($freshUser->avatar ?? $currentUser->avatar ?? ''));
    $avatarPath = preg_replace('#^(?:/)?(?:storage/|public/)#', '', $avatarPath);
    $avatarUrl = $avatarPath !== '' ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatarPath) : null;

    $safeCount = function (string $table, ?callable $filter = null): int {
        try {
            if (!Schema::hasTable($table)) return 0;
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull('deleted_at');
            if ($filter) $filter($query);
            return $query->count();
        } catch (\Throwable $e) { return 0; }
    };
    $userTotal = $safeCount('users');
    $adminTotal = 0;
    try {
        if (Schema::hasTable('users') && Schema::hasTable('roles')) {
            $adminTotal = DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.slug', 'admin')
                ->when(Schema::hasColumn('users','deleted_at'), fn($q) => $q->whereNull('users.deleted_at'))
                ->count();
        }
    } catch (\Throwable $e) {}
    $metrics = [
        ['label'=>'Total Users', 'value'=>$userTotal, 'icon'=>'users-round', 'detail'=>'Registered accounts'],
        ['label'=>'Administrators', 'value'=>$adminTotal, 'icon'=>'shield-check', 'detail'=>'Admin role accounts'],
        ['label'=>'System Roles', 'value'=>$safeCount('roles'), 'icon'=>'layers-3', 'detail'=>'Access levels'],
        ['label'=>'Permissions', 'value'=>$safeCount('permissions'), 'icon'=>'key-round', 'detail'=>'Configured permissions'],
        ['label'=>'Audit Events', 'value'=>$safeCount('audit_logs'), 'icon'=>'activity', 'detail'=>'Recorded activities'],
    ];
    $roleSummary = collect(); $recentAudits = collect(); $announcements = collect(); $recentUsers = collect();
    $activityLabels = []; $activityValues = [];
    try {
        if (Schema::hasTable('roles') && Schema::hasTable('users')) {
            $roleSummary = DB::table('roles')->leftJoin('users', function($join) {
                $join->on('users.role_id','=','roles.id');
                if (Schema::hasColumn('users','deleted_at')) $join->whereNull('users.deleted_at');
            })->select('roles.name','roles.slug',DB::raw('COUNT(users.id) as total'))
              ->groupBy('roles.id','roles.name','roles.slug')->orderBy('roles.id')->get();
            $recentUsers = DB::table('users')->join('roles','roles.id','=','users.role_id')
                ->select('users.name','users.username','users.status','users.created_at','roles.name as role_name')
                ->when(Schema::hasColumn('users','deleted_at'),fn($q)=>$q->whereNull('users.deleted_at'))
                ->orderByDesc('users.id')->limit(5)->get();
        }
    } catch (\Throwable $e) {}
    try {
        if (Schema::hasTable('audit_logs')) {
            $recentAudits = DB::table('audit_logs')->orderByDesc(Schema::hasColumn('audit_logs','id') ? 'id' : (Schema::hasColumn('audit_logs','log_id') ? 'log_id' : 'created_at'))->limit(5)->get();
            if (Schema::hasColumn('audit_logs','created_at')) {
                for ($i=6;$i>=0;$i--) {
                    $day=now()->subDays($i);
                    $activityLabels[]=$day->format('M j');
                    $activityValues[]=$safeCount('audit_logs',fn($q)=>$q->whereDate('created_at',$day->toDateString()));
                }
            }
        }
    } catch (\Throwable $e) {}
    if (count($activityLabels)===0) {
        for ($i=6;$i>=0;$i--) { $activityLabels[]=now()->subDays($i)->format('M j'); $activityValues[]=0; }
    }
    try {
        if (Schema::hasTable('announcements')) {
            $query=DB::table('announcements');
            if (Schema::hasColumn('announcements','status')) $query->where('status','published');
            if (Schema::hasColumn('announcements','published_at')) $query->where(fn($q)=>$q->whereNull('published_at')->orWhere('published_at','<=',now()));
            if (Schema::hasColumn('announcements','expires_at')) $query->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()));
            if (Schema::hasColumn('announcements','deleted_at')) $query->whereNull('deleted_at');
            if (Schema::hasColumn('announcements','is_pinned')) $query->orderByDesc('is_pinned');
            $query->orderByDesc(Schema::hasColumn('announcements','published_at') ? 'published_at' : 'id');
            $announcements=$query->limit(3)->get();
        }
    } catch (\Throwable $e) {}
    $navigation = [
        ['label'=>'Dashboard','icon'=>'layout-dashboard','route'=>'superadmin.dashboard','path'=>'superadmin/dashboard'],
        ['label'=>'Manage Admins','icon'=>'user-round-cog','route'=>'superadmin.admins.index','path'=>'superadmin/admins'],
        ['label'=>'All Users','icon'=>'users-round','route'=>'superadmin.users.index','path'=>'superadmin/users'],
        ['label'=>'Roles','icon'=>'shield','route'=>'superadmin.roles.index','path'=>'superadmin/roles'],
        ['label'=>'Permissions','icon'=>'key-round','route'=>'superadmin.permissions.index','path'=>'superadmin/permissions'],
        ['label'=>'Announcements','icon'=>'megaphone','route'=>'superadmin.announcements.index','path'=>'superadmin/announcements'],
        ['label'=>'System Settings','icon'=>'settings-2','route'=>'superadmin.settings.index','path'=>'superadmin/settings'],
        ['label'=>'Audit Logs','icon'=>'clipboard-list','route'=>'superadmin.audit-logs.index','path'=>'superadmin/audit-logs'],
        ['label'=>'Backups & Restore','icon'=>'database-backup','route'=>'superadmin.backups.index','path'=>'superadmin/backups'],
        ['label'=>'System Monitor','icon'=>'monitor-dot','route'=>'superadmin.system-monitor.index','path'=>'superadmin/system-monitor'],
        ['label'=>'Reports','icon'=>'chart-no-axes-combined','route'=>'superadmin.reports.index','path'=>'superadmin/reports'],
        ['label'=>'My Profile','icon'=>'circle-user-round','route'=>'superadmin.profile.index','path'=>'superadmin/profile'],
    ];
    $roleColors=['#ffcb65','#e9a64d','#a96a32','#7d4929','#e5bd83','#fff0c9','#f5f1ec'];
    $totalRoleUsers=max(1,(int)$roleSummary->sum('total'));
    $donutParts=[]; $cursor=0;
    foreach($roleSummary as $idx=>$role) {
        $portion=(int)$role->total/$totalRoleUsers*100;
        if($portion>0) $donutParts[]=($roleColors[$idx%count($roleColors)]).' '.$cursor.'% '.($cursor+$portion).'%';
        $cursor+=$portion;
    }
    $donutBackground=count($donutParts) ? 'conic-gradient('.implode(',',$donutParts).')' : 'conic-gradient(#573721 0 100%)';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Super Administrator Dashboard | The Brew Box</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<style>
:root{--bg:#120a07;--sidebar:#180d08;--surface:#28170f;--surface2:#332015;--gold:#ffc866;--gold2:#d99639;--text:#fff7ec;--muted:#c4a98c;--border:#83552a;--side-width:274px}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(ellipse at 78% -10%,#4a2813 0,transparent 43%),#100907;color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px}button,input{font:inherit}button{cursor:pointer}a{color:inherit;text-decoration:none}svg{flex-shrink:0}button:focus-visible,a:focus-visible,input:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
.shell{min-height:100vh}.sidebar{width:var(--side-width);position:fixed;inset:0 auto 0 0;z-index:40;display:flex;flex-direction:column;background:linear-gradient(165deg,#261409,#120907 70%);border-right:1px solid #8b562c;transition:width .25s,transform .25s;box-shadow:8px 0 36px #0005}.brand{height:92px;display:flex;align-items:center;gap:13px;padding:20px;border-bottom:1px solid #a06c343d;overflow:hidden;white-space:nowrap}.brand-mark{width:52px;height:52px;display:grid;place-items:center;color:var(--gold);border:1px solid #c78a46;border-radius:15px;background:#3b2113;box-shadow:0 0 18px #e3a2462b;flex:none}.brand-mark svg{width:30px;height:30px}.brand-text strong{font-family:'Playfair Display',serif;font-size:22px;line-height:1.05;display:block}.brand-text small{font-size:10px;letter-spacing:.4px;color:#d8af7c}.super-pill{display:flex;align-items:center;gap:10px;white-space:nowrap;margin:18px 13px 12px;padding:12px;border:1px solid #a76e36;border-radius:40px;background:linear-gradient(90deg,#5a3216,#2b180e);color:#ffda8e;font-weight:800;font-size:12px;box-shadow:inset 0 1px 0 #ffd68d30}.super-pill svg{width:19px}.nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:4px 12px 18px;scrollbar-width:thin;scrollbar-color:#7b4b27 transparent}.nav-caption{font-size:9px;text-transform:uppercase;letter-spacing:2px;color:#a17c5b;font-weight:800;padding:14px 14px 8px;white-space:nowrap}.nav-item{min-height:43px;display:flex;align-items:center;gap:13px;padding:11px 13px;border-radius:11px;margin:3px 0;color:#e8d3bd;font-size:12px;font-weight:650;white-space:nowrap;transition:background .2s,box-shadow .2s}.nav-item svg{width:18px;height:18px;color:#f1bd67}.nav-item:hover{background:#71422170}.nav-item.active{color:white;background:linear-gradient(90deg,#a66322,#583013);box-shadow:inset 0 0 0 1px #ffc16a,0 0 17px #d9913470}.nav-item.active svg{color:#fff1cb}.nav-item.unavailable{opacity:.5;cursor:not-allowed}.nav-label{overflow:hidden;text-overflow:ellipsis}.nav-dot{margin-left:auto;width:7px;height:7px;background:#ff5b66;border-radius:50%;flex:none}.sidebar-bottom{border-top:1px solid #aa75452c;padding:12px}.logout{background:transparent;border:0;width:100%;text-align:left;color:#ff7979}.logout svg{color:#ff7979}.workspace{margin-left:var(--side-width);min-width:0;transition:margin-left .25s}.topbar{height:92px;position:sticky;top:0;z-index:25;display:flex;align-items:center;gap:17px;padding:0 28px;background:#1a0e08ed;backdrop-filter:blur(14px);border-bottom:1px solid #8b5a2c}.toggle{width:44px;height:44px;display:grid;place-items:center;border:1px solid #e6a74b;border-radius:12px;color:#1a0e08;background:linear-gradient(145deg,#ffe49b,#dc942c);box-shadow:0 0 18px #edaa4180;flex:none}.toggle svg{width:21px;height:21px}.top-title{min-width:0}.top-title h1{font-size:clamp(16px,2.2vw,27px);line-height:1.15;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.top-title p{font-size:11px;color:#cdb395;margin:5px 0 0}.crown{color:var(--gold);font-size:25px;margin-right:7px}.search{display:flex;align-items:center;gap:9px;border:1px solid #82613e;border-radius:25px;padding:10px 13px;margin-left:auto;width:min(285px,26vw);color:#d5b792;background:#2a1a10}.search svg{width:17px;height:17px}.search input{background:transparent;color:white;border:0;outline:0;width:100%;min-width:0;font-size:11px}.search input::placeholder{color:#b59b83}.user-chip{border-left:1px solid #6b452c;padding-left:17px;display:flex;align-items:center;gap:11px;white-space:nowrap}.avatar{height:41px;width:41px;border:2px solid #d99e4a;border-radius:50%;background:linear-gradient(135deg,#dba65b,#513017);display:grid;place-items:center;font-weight:800;color:#1c0d04}.avatar{overflow:hidden;flex:none}.avatar img{width:100%;height:100%;object-fit:cover;display:block}.avatar-fallback{width:100%;height:100%;display:grid;place-items:center}.user-chip strong{display:block;font-size:12px}.user-chip small{display:block;color:var(--gold);font-size:10px;margin-top:3px}.main{max-width:1700px;margin:auto;padding:22px 26px 45px}.hero{position:relative;overflow:hidden;min-height:144px;display:flex;align-items:center;padding:28px 37px;border:1px solid #e9a950;border-radius:18px;margin-bottom:17px;background:linear-gradient(90deg,#2b140ad9 0%,#391a0bd9 56%,#160a06a8 100%),url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1600&q=85') center 49%/cover;box-shadow:inset 0 0 0 1px #ffce6630,0 0 22px #d17c2638}.hero:before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 90% 10%,#ffb85432,transparent 38%);pointer-events:none}.hero-copy{position:relative;z-index:1}.eyebrow{font-size:10px;text-transform:uppercase;letter-spacing:2.4px;font-weight:800;color:#ffcb75;margin-bottom:10px}.hero h2{font-size:clamp(22px,2.6vw,34px);line-height:1.2;margin:0 0 9px;font-weight:800}.hero p{font-size:13px;color:#f1d9c0;margin:0}.hero-coffee{margin-left:auto;display:grid;place-items:center;width:110px;height:110px;border-radius:50%;background:radial-gradient(circle,#ab5a20,#32170b 70%);box-shadow:0 0 0 6px #cfa15c40,0 0 34px #efad5c6b;flex:none}.hero-coffee svg{width:65px;height:65px;color:#ffe5b5}.stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:15px}.stat{position:relative;overflow:hidden;display:flex;align-items:center;gap:12px;min-height:112px;padding:16px 13px;border:1px solid #d9a059;border-radius:17px;background:linear-gradient(145deg,#55321c,#2a170e 73%);box-shadow:inset 0 1px 0 #fff2c533,0 5px 18px #0004}.stat:after{content:'';position:absolute;width:100px;height:50px;right:-35px;bottom:-22px;transform:rotate(-25deg);background:#efb34b29;filter:blur(14px)}.stat-icon{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#6f3e16,#261106);border:1px solid #aa692e;color:var(--gold);flex:none}.stat-icon svg{width:23px;height:23px}.stat-label{font-size:11px;color:#f3d9b8;margin-bottom:4px}.stat-value{font-size:clamp(21px,2.5vw,32px);font-weight:800;line-height:1.05}.stat-detail{font-size:9px;color:#cba982;margin-top:5px}.grid{display:grid;grid-template-columns:1fr 1.12fr;gap:15px}.panel{min-width:0;border:1px solid #c28a48;border-radius:17px;padding:18px 19px;background:linear-gradient(145deg,#402619,#26150e 80%);box-shadow:inset 0 1px 0 #fff1d41f,0 0 15px #ba702a24}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:17px}.panel-head h3{display:flex;align-items:center;gap:10px;font-size:16px;margin:0;font-weight:800}.panel-head h3 svg{width:20px;height:20px;color:var(--gold)}.panel-head small{color:#cbb092;font-size:10px}.view-link{font-size:10px;color:#ffcd78;font-weight:700;border:1px solid #a76e37;padding:7px 10px;border-radius:16px}.role-wrap{display:flex;align-items:center;gap:24px}.donut{width:210px;height:210px;max-width:48%;aspect-ratio:1;flex:none;border-radius:50%;display:grid;place-items:center;position:relative;background:var(--donut);box-shadow:0 0 18px #f9a93630}.donut:before{content:'';position:absolute;inset:24%;border-radius:50%;background:#2d1a10;box-shadow:inset 0 0 8px #0008}.donut-center{position:relative;z-index:1;text-align:center}.donut-center strong{display:block;font-size:30px}.donut-center small{font-size:11px}.legend{flex:1;min-width:0;display:grid;gap:9px}.legend-row{display:flex;align-items:center;gap:8px;font-size:11px}.swatch{width:11px;height:11px;border-radius:3px;flex:none}.legend-row .role-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.legend-row b{font-size:10px;color:#f5ddbd}.chart-wrap{height:230px;position:relative}.table-scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:500px}thead{background:#21120c}th{padding:12px 10px;color:#efc17f;font-size:10px;text-align:left}td{padding:12px 10px;font-size:11px;border-top:1px solid #9866373b;color:#f0ddc8}td strong{font-weight:700;color:#fff7eb}.status{display:inline-block;padding:5px 9px;border-radius:20px;font-size:10px;font-weight:700;background:#694326;color:#e9c9a6}.status.active{background:#23523a;color:#a5e6ae}.status.inactive,.status.suspended{background:#613329;color:#ffb5a1}.audit-row{display:grid;grid-template-columns:1fr auto;gap:10px;padding:12px 0;border-bottom:1px solid #a16b3b42}.audit-row:last-child{border:0}.audit-row strong{font-size:11px;display:block}.audit-row small{display:block;font-size:10px;color:#c5a88c;margin-top:5px}.audit-time{font-size:10px;color:#f4bf70;white-space:nowrap}.announcement{display:flex;align-items:flex-start;gap:13px;padding:13px 0;border-bottom:1px solid #a16b3b42}.announcement:last-child{border:0}.announcement-icon{display:grid;place-items:center;background:#714321;border:1px solid #ba8749;color:#ffcd80;width:47px;height:47px;border-radius:12px;flex:none}.announcement-icon svg{width:22px}.announcement-body{min-width:0;flex:1}.announcement-title{font-size:12px;font-weight:800;margin:0 0 5px}.announcement-body p{font-size:11px;line-height:1.6;color:#d9bca1;margin:0 0 5px}.announcement-body small{font-size:10px;color:#bda080}.pin{font-size:9px;color:#ffe7b5;background:#7a412e;border-radius:12px;padding:3px 7px;margin-left:6px}.empty{padding:30px 12px;text-align:center;color:#c4a586;font-size:12px;border:1px dashed #8a5a30;border-radius:12px}.quick-actions{display:flex;flex-wrap:wrap;gap:8px}.quick{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #986536;border-radius:10px;background:#452817;font-size:11px;font-weight:700}.quick svg{width:16px;color:var(--gold)}.quick.disabled{opacity:.48;cursor:not-allowed}.section-note{font-size:10px;color:#b99b7c;margin-top:12px;line-height:1.5}.footnote{font-size:10px;color:#b99b7c;margin-top:20px}.sidebar-collapsed{--side-width:77px}.sidebar-collapsed .brand{padding-left:12px}.sidebar-collapsed .brand-text,.sidebar-collapsed .super-pill span,.sidebar-collapsed .nav-label,.sidebar-collapsed .nav-caption,.sidebar-collapsed .nav-dot{display:none}.sidebar-collapsed .super-pill{justify-content:center;margin-left:9px;margin-right:9px;padding:11px}.sidebar-collapsed .nav-item{justify-content:center;padding:11px}.sidebar-collapsed .nav{padding-left:8px;padding-right:8px}.sidebar-collapsed .brand-mark{width:49px}.shade{display:none}
@media(max-width:1420px){.stats{grid-template-columns:repeat(3,minmax(0,1fr))}.role-wrap{gap:12px}.donut{width:170px}.top-title p{display:none}}
@media(max-width:1080px){.grid{grid-template-columns:1fr}.stats{grid-template-columns:repeat(3,minmax(0,1fr))}.search{display:none}}
@media(max-width:820px){.sidebar,.sidebar-collapsed .sidebar{width:274px;transform:translateX(-100%)}.workspace,.sidebar-collapsed .workspace{margin-left:0}.sidebar-collapsed .brand-text,.sidebar-collapsed .super-pill span,.sidebar-collapsed .nav-label,.sidebar-collapsed .nav-caption{display:initial}.sidebar-collapsed .nav-caption{display:block}.sidebar-collapsed .nav-item{justify-content:flex-start;padding:11px 13px}.sidebar-collapsed .nav{padding:4px 12px 18px}.sidebar-collapsed .super-pill{justify-content:flex-start;margin:18px 13px 12px}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .shade{display:block;position:fixed;inset:0;background:#000a;z-index:35}.topbar{height:75px;padding:0 15px}.main{padding:16px}.user-chip{padding-left:10px}.user-chip strong,.user-chip small{display:none}}
@media(max-width:570px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.stat{padding:12px;min-height:94px}.stat-icon{width:37px;height:37px}.stat-icon svg{width:18px}.stat-value{font-size:22px}.hero{padding:22px;min-height:130px}.hero-coffee{display:none}.hero h2{font-size:22px}.top-title h1{font-size:16px}.crown{display:none}.role-wrap{flex-direction:column}.donut{max-width:none;width:190px}.legend{width:100%}.panel{padding:15px}.main{padding:12px}.stat-detail{display:none}}
</style>
</head>
<body>
<div class="shade" id="shade" aria-hidden="true"></div>
<div class="shell">
<aside class="sidebar" id="sidebar" aria-label="Super Administrator navigation">
  <a href="{{ route('superadmin.dashboard') }}" class="brand" title="The Brew Box"><span class="brand-mark"><i data-lucide="coffee"></i></span><span class="brand-text"><strong>The<br>Brew Box</strong><small>Sip · Relax · Repeat</small></span></a>
  <div class="super-pill" title="Super Administrator"><i data-lucide="crown"></i><span>Super Administrator</span></div>
  <nav class="nav">
    <div class="nav-caption">System Control</div>
    @foreach($navigation as $index=>$item)
      @if($index===11)<div class="nav-caption">Account</div>@endif
      @php $exists=Route::has($item['route']); $active=request()->is($item['path'])||request()->is($item['path'].'/*'); @endphp
      @if($exists)
        <a href="{{ route($item['route']) }}" class="nav-item {{ $active ? 'active' : '' }}" title="{{ $item['label'] }}" @if($active) aria-current="page" @endif><i data-lucide="{{ $item['icon'] }}"></i><span class="nav-label">{{ $item['label'] }}</span>@if($item['label']==='Announcements' && $announcements->isNotEmpty())<span class="nav-dot" aria-label="Published announcements"></span>@endif</a>
      @else
        <span class="nav-item unavailable" title="{{ $item['label'] }} (route not created yet)" aria-disabled="true"><i data-lucide="{{ $item['icon'] }}"></i><span class="nav-label">{{ $item['label'] }}</span></span>
      @endif
    @endforeach
  </nav>
  <div class="sidebar-bottom"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-item logout" title="Sign Out"><i data-lucide="log-out"></i><span class="nav-label">Sign Out</span></button></form></div>
</aside>
<div class="workspace">
<header class="topbar">
  <button type="button" class="toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button>
  <div class="top-title"><h1><span class="crown">♛</span>Super Administrator Dashboard</h1><p>Full system control, security, and platform configuration</p></div>
  <label class="search"><i data-lucide="search"></i><input id="pageSearch" type="search" placeholder="Search dashboard sections..." aria-label="Search dashboard sections"></label>
  <a class="user-chip" href="{{ route('superadmin.profile.index') }}" title="View your profile"><span class="avatar">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="{{ $dashboardName }} profile photo" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">@endif<span class="avatar-fallback" @if($avatarUrl) style="display:none" @endif>{{ strtoupper(Str::substr($dashboardName, 0, 1)) }}</span></span><span><strong>{{ $dashboardName }}</strong><small>Super Administrator</small></span></a>
</header>
<main class="main">
  <section class="hero"><div class="hero-copy"><div class="eyebrow">The Brew Box · Command Center</div><h2>Welcome, {{ $dashboardName }}! <span style="color:#ffd073">♛</span></h2><p>You are signed in as <strong>Super Administrator</strong>. Manage your entire café platform.</p></div><div class="hero-coffee"><i data-lucide="coffee"></i></div></section>
  <section class="stats" aria-label="System statistics">
    @foreach($metrics as $metric)
      <article class="stat searchable"><span class="stat-icon"><i data-lucide="{{ $metric['icon'] }}"></i></span><div><div class="stat-label">{{ $metric['label'] }}</div><div class="stat-value">{{ number_format($metric['value']) }}</div><div class="stat-detail">{{ $metric['detail'] }}</div></div></article>
    @endforeach
  </section>
  <div class="grid">
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="users-round"></i> User Distribution by Role</h3><small>Live account totals</small></div>
      @if($roleSummary->isNotEmpty())
        <div class="role-wrap"><div class="donut" style="--donut:{{ $donutBackground }}"><div class="donut-center"><strong>{{ number_format($roleSummary->sum('total')) }}</strong><small>Users</small></div></div><div class="legend">@foreach($roleSummary as $idx=>$role)<div class="legend-row"><span class="swatch" style="background:{{ $roleColors[$idx % count($roleColors)] }}"></span><span class="role-name">{{ $role->name }}</span><b>{{ $role->total }} ({{ number_format($role->total/$totalRoleUsers*100,1) }}%)</b></div>@endforeach</div></div>
      @else<div class="empty">No role data available yet.</div>@endif
    </section>
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="chart-no-axes-combined"></i> System Activity</h3><small>Last 7 days · audit events</small></div><div class="chart-wrap"><canvas id="activityChart" role="img" aria-label="Audit log activity for the last seven days"></canvas></div></section>
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="clipboard-list"></i> Recent Audit Logs</h3>@if(Route::has('superadmin.audit-logs.index'))<a class="view-link" href="{{ route('superadmin.audit-logs.index') }}">View All →</a>@endif</div>
      @forelse($recentAudits as $audit)<div class="audit-row"><div><strong>{{ Str::headline($audit->action ?? 'System activity') }}</strong><small>{{ $audit->details ?? $audit->module ?? $audit->ip_address ?? 'System event' }}</small></div><span class="audit-time">{{ !empty($audit->created_at) ? Carbon::parse($audit->created_at)->format('M d, h:i A') : '—' }}</span></div>@empty<div class="empty">No audit activity recorded yet.</div>@endforelse
    </section>
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="megaphone"></i> Announcements</h3>@if(Route::has('superadmin.announcements.index'))<a class="view-link" href="{{ route('superadmin.announcements.index') }}">View All →</a>@else<small>Published notices</small>@endif</div>
      @forelse($announcements as $announcement)<article class="announcement"><div class="announcement-icon"><i data-lucide="{{ !empty($announcement->is_pinned) ? 'pin' : 'megaphone' }}"></i></div><div class="announcement-body"><h4 class="announcement-title">{{ $announcement->title ?? 'Announcement' }}@if(!empty($announcement->is_pinned))<span class="pin">Pinned</span>@endif</h4><p>{{ Str::limit(strip_tags($announcement->content ?? $announcement->body ?? ''),150) }}</p><small>{{ !empty($announcement->published_at) ? Carbon::parse($announcement->published_at)->format('M d, Y') : 'Published' }}</small></div></article>@empty<div class="empty">No published announcements yet. New announcements will appear here after publication.</div>@endforelse
    </section>
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="user-round-plus"></i> Recent User Accounts</h3><small>Latest five</small></div>
      @if($recentUsers->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th></tr></thead><tbody>@foreach($recentUsers as $user)<tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->username }}</td><td>{{ $user->role_name }}</td><td><span class="status {{ strtolower($user->status ?? '') }}">{{ ucfirst($user->status ?? 'unknown') }}</span></td></tr>@endforeach</tbody></table></div>@else<div class="empty">No user accounts found.</div>@endif
    </section>
    <section class="panel searchable"><div class="panel-head"><h3><i data-lucide="settings-2"></i> Quick Administration</h3><small>Superadmin tools</small></div><div class="quick-actions">@foreach(array_slice($navigation,1,10) as $item)@if(Route::has($item['route']))<a class="quick" href="{{ route($item['route']) }}"><i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}</a>@else<span class="quick disabled" title="Route not created yet"><i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}</span>@endif @endforeach</div><p class="section-note">Dimmed items are placeholders until their pages and routes are implemented.</p></section>
  </div>
  <p class="footnote">THE BREW BOX · Super Administrator Control Center · {{ now()->format('M d, Y') }} · All displayed counts are queried from your database.</p>
</main>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  if(window.lucide) lucide.createIcons();
  const body=document.body,toggle=document.getElementById('sidebarToggle'),shade=document.getElementById('shade');
  const mobile=()=>window.matchMedia('(max-width:820px)').matches;
  const closeMobile=()=>{body.classList.remove('mobile-open');toggle.setAttribute('aria-expanded','false')};
  try{if(!mobile()&&localStorage.getItem('brew_superadmin_sidebar_collapsed')==='1')body.classList.add('sidebar-collapsed')}catch(e){}
  toggle.setAttribute('aria-expanded',String(!body.classList.contains('sidebar-collapsed')));
  toggle.addEventListener('click',()=>{
    if(mobile()){const open=body.classList.toggle('mobile-open');toggle.setAttribute('aria-expanded',String(open))}
    else{const collapsed=body.classList.toggle('sidebar-collapsed');toggle.setAttribute('aria-expanded',String(!collapsed));try{localStorage.setItem('brew_superadmin_sidebar_collapsed',collapsed?'1':'0')}catch(e){}}
  });
  shade.addEventListener('click',closeMobile);
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMobile();if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();document.getElementById('pageSearch').focus()}});
  window.addEventListener('resize',()=>{if(!mobile())body.classList.remove('mobile-open')});
  const search=document.getElementById('pageSearch');
  search.addEventListener('input',()=>{const term=search.value.toLowerCase().trim();document.querySelectorAll('.searchable').forEach(el=>{el.style.display=(!term||el.textContent.toLowerCase().includes(term))?'':'none'})});
  if(window.Chart){
    const canvas=document.getElementById('activityChart');
    new Chart(canvas,{type:'line',data:{labels:@json($activityLabels),datasets:[{label:'Audit events',data:@json($activityValues),borderColor:'#ffd078',backgroundColor:'rgba(255,191,78,.16)',pointBackgroundColor:'#ffd078',pointBorderColor:'#3a1e0d',pointBorderWidth:2,pointRadius:4,tension:.35,fill:true,borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#160c08',titleColor:'#ffd078',bodyColor:'#fff'}},scales:{x:{ticks:{color:'#dfc2a0',font:{size:10}},grid:{display:false},border:{color:'#8a5a32'}},y:{beginAtZero:true,ticks:{color:'#dfc2a0',precision:0,font:{size:10}},grid:{color:'rgba(236,187,124,.15)'},border:{display:false}}}}});
  }
});
</script>
</body>
</html>
