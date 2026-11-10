@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $viewer = auth()->user();
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
        ['Reports', 'chart-column', 'superadmin.reports.index'],
        ['My Profile', 'circle-user-round', 'superadmin.profile.index'],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Add Administrator | The Brew Box</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<style>
:root{color-scheme:dark;--side:258px;--gold:#f1b94f;--text:#f9eedc;--muted:#c4a98d}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 80% 0%,#52321c 0%,#20130d 42%,#120c09 100%);color:var(--text);font:14px 'DM Sans',sans-serif}a{color:#f0c57b;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
.sidebar{position:fixed;inset:0 auto 0 0;width:var(--side);background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;z-index:30;display:flex;flex-direction:column;transition:width .22s,transform .22s}.brand{display:flex;align-items:center;gap:12px;padding:25px 18px 19px;border-bottom:1px solid #6e482a;min-height:85px;color:#fff}.brand-icon{width:37px;height:37px;color:var(--gold);flex:none}.brand-icon svg{width:37px;height:37px}.brand-text{min-width:0;overflow:hidden}.brand-title{font:700 21px 'Playfair Display',serif;display:block;white-space:nowrap}.brand small{font-size:10px;color:#e3ba79;white-space:nowrap}.side-scroll{padding:14px 10px;overflow-y:auto;overflow-x:hidden;flex:1}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border:0;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap;width:100%;background:transparent;text-align:left}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642,0 0 15px #bb792f45;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55;flex:none}.nav-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{color:#f0a19a}.logout svg{color:#ff746b}.nav-unavailable{opacity:.5;cursor:default}
.workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .22s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cd9;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.icon-button svg{width:21px}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.wrap{max-width:1000px;margin:0 auto;padding:36px 30px 60px}.back{display:inline-flex;align-items:center;gap:7px;margin-bottom:25px;color:#d5ad79}.eyebrow{font-size:11px;font-weight:800;letter-spacing:2px;color:#e6b76c;text-transform:uppercase}h1{font:700 clamp(30px,4vw,44px) 'Playfair Display',serif;margin:10px 0}p{color:var(--muted);line-height:1.7}.panel{margin-top:27px;border:1px solid #76512d;background:linear-gradient(150deg,#352115,#24160f);border-radius:22px;padding:clamp(22px,4vw,38px);box-shadow:0 25px 70px #0007}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}label{font-size:12px;font-weight:700;color:#f3dec1}input,select{width:100%;background:#1c120e;color:#fff1de;border:1px solid #725236;border-radius:11px;padding:13px 14px;outline:none}input:focus,select:focus{border-color:#e5b46e;box-shadow:0 0 0 3px #dba85a22}.hint{font-size:11px;color:#bda48d}.error{color:#ffb7a6;font-size:12px}.alert{border:1px solid #a65a43;background:#4e241e;color:#ffdbce;padding:14px 18px;border-radius:12px;margin-top:20px}.actions{display:flex;justify-content:flex-end;align-items:center;gap:16px;margin-top:28px}.btn{background:linear-gradient(120deg,#f2c67e,#bd8240);color:#2b180e;border:0;border-radius:11px;padding:14px 23px;font-weight:800;font-size:13px}.btn:hover{filter:brightness(1.08)}.cancel{padding:12px}.mobile-shade{display:none}.collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}
@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.wrap{padding:28px 20px 50px}}
@media(max-width:650px){.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.actions{flex-wrap:wrap}.account small{display:none}.top-title{font-size:12px}}
</style>
</head>
<body>
<div class="mobile-shade" id="mobileShade"></div>
<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">
  <a class="brand" href="{{ route('superadmin.dashboard') }}"><span class="brand-icon"><i data-lucide="coffee"></i></span><span class="brand-text"><span class="brand-title">The Brew Box</span><small>SUPER ADMINISTRATION</small></span></a>
  <nav class="side-scroll"><div class="side-label">System Management</div>
    @foreach($menu as [$label, $icon, $name])
      @if(Route::has($name))
        <a class="nav {{ $name === 'superadmin.admins.index' ? 'active' : '' }}" href="{{ route($name) }}" title="{{ $label }}" @if($name === 'superadmin.admins.index') aria-current="page" @endif><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></a>
      @else
        <span class="nav nav-unavailable" title="Route not yet registered"><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></span>
      @endif
    @endforeach
  </nav>
  <div class="sidebar-footer"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav logout" type="submit"><i data-lucide="log-out"></i><span class="nav-text">Sign Out</span></button></form></div>
</aside>
<div class="workspace">
  <header class="topbar"><button class="icon-button" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button><span class="top-title">Super Administrator / Create Admin</span><span class="top-spacer"></span><div class="account"><span class="avatar">{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></div></header>
  <main class="wrap">
    <a class="back" href="{{ route('superadmin.admins.index') }}"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Back to Manage Admins</a>
    <div class="eyebrow">The Brew Box / Superadmin / Manage Admins</div>
    <h1>Create Administrator</h1>
    <p>Provision a new administrator account. Only an authenticated Super Administrator can submit this form.</p>
    @if($errors->any())<div class="alert" role="alert">Please correct the highlighted fields below.</div>@endif
    <form class="panel" method="POST" action="{{ route('superadmin.admins.store') }}">
      @csrf
      <div class="grid">
        <div class="field"><label for="name">Full name *</label><input id="name" name="name" maxlength="150" value="{{ old('name') }}" autocomplete="name" required>@error('name')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="username">Username *</label><input id="username" name="username" maxlength="80" value="{{ old('username') }}" autocomplete="off" required>@error('username')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="email">Email *</label><input id="email" name="email" type="email" maxlength="191" value="{{ old('email') }}" autocomplete="email" required>@error('email')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" maxlength="20" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="password">Password *</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><span class="hint">At least 12 characters, including uppercase, lowercase, and numbers.</span>@error('password')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="password_confirmation">Confirm password *</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
        <div class="field full"><label for="status">Account status</label><select id="status" name="status"><option value="active" @selected(old('status','active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select>@error('status')<span class="error">{{ $message }}</span>@enderror</div>
      </div>
      <div class="actions"><a class="cancel" href="{{ route('superadmin.admins.index') }}">Cancel</a><button class="btn" type="submit">Create Administrator</button></div>
    </form>
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
        if (mobile()) {
            const open = body.classList.toggle('mobile-open');
            toggle.setAttribute('aria-expanded', String(open));
        } else {
            const collapsed = body.classList.toggle('collapsed');
            toggle.setAttribute('aria-expanded', String(!collapsed));
            try { localStorage.setItem('brew_superadmin_sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
    });
    shade.addEventListener('click', closeMobile);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMobile(); });
    try {
        if (!mobile() && localStorage.getItem('brew_superadmin_sidebar_collapsed') === '1') {
            body.classList.add('collapsed');
            toggle.setAttribute('aria-expanded', 'false');
        }
    } catch (e) {}
    window.addEventListener('resize', () => { if (!mobile()) closeMobile(); });
});
</script>
</body>
</html>
