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
        ['Reports', 'chart-column-increasing', 'superadmin.reports.index'],
        ['My Profile', 'circle-user-round', 'superadmin.profile.index'],
    ];
    // Only show submit controls when matching server routes exist.
    $profileUpdateRoute = Route::has('superadmin.profile.update') ? 'superadmin.profile.update' : null;
    $passwordUpdateRoute = Route::has('superadmin.profile.password.update') ? 'superadmin.profile.password.update' : null;
    $photo = $viewer->profile_photo ?? $viewer->avatar ?? null;
    $photoUrl = $photo ? (Str::startsWith((string)$photo, ['http://','https://']) ? $photo : asset('storage/'.ltrim(Str::replaceFirst('public/','',Str::replaceFirst('storage/','',(string)$photo)),'/'))) : null;
@endphp
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>My Profile | The Brew Box</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script><style>

        :root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}

        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--gold);outline-offset:3px}svg{flex-shrink:0}

        .sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:width .2s,transform .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55}.nav-text,.brand-text{overflow:hidden}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}

        .workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.icon-button svg{width:21px}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1530px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}

        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:21px}.stat,.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020}.stat{padding:19px;display:flex;align-items:center;gap:13px}.stat-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:13px;background:#7e4a1f;color:#ffd17c}.stat-icon svg{width:23px}.stat small{display:block;color:#d5bba0;margin-bottom:5px}.stat strong{font-size:27px}.panel{padding:21px}.panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.panel h2{font-size:18px;margin:0}.panel-sub{color:#cbb19a;font-size:12px;margin:7px 0 0}.controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.search{display:flex;align-items:center;gap:8px;background:#1b100b;border:1px solid #815630;border-radius:10px;padding:0 12px}.search svg{width:16px;color:var(--gold)}.search input{width:210px;height:40px;background:transparent;border:0;color:white;outline:none;font-size:12px}.search input::placeholder{color:#a58a74}.filter{height:42px;padding:0 12px;background:#1b100b;color:#f6e4d2;border:1px solid #815630;border-radius:10px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:790px}th{padding:13px;text-align:left;font-size:10px;letter-spacing:1.3px;color:#e2b778;text-transform:uppercase;background:#1c100b}td{padding:15px 13px;border-top:1px solid #654229;color:#ead8c7;font-size:12px}td strong{color:white}.initial{display:inline-grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#654020;color:#f7ca85;margin-right:9px;font-weight:800;vertical-align:middle}.pill{padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;display:inline-block}.pill.active{background:#1f4b32;color:#9de6ae}.pill.inactive{background:#5d3028;color:#ffb2a1}.role-pill{display:inline-block;border:1px solid #9b6c39;color:#f1c589;border-radius:8px;padding:6px 9px;font-weight:700;font-size:11px}.muted{color:#bfa38b}.empty{text-align:center;padding:45px 15px;color:#c5a78c;line-height:1.8}.note{margin-top:14px;font-size:11px;color:#c6a78b;line-height:1.7}.notice{border:1px solid #b26c43;border-radius:10px;padding:12px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.hidden-row{display:none!important}.mobile-shade{display:none}.count{font-size:12px;color:#e4b875;font-weight:700}

        .collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}.collapsed .brand-icon svg{width:34px}

        @media(max-width:1150px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.stats{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:15px}.search input{width:170px}.account small{display:none}.top-title{font-size:12px}}

    
.profile-grid{display:grid;grid-template-columns:330px minmax(0,1fr);gap:20px;align-items:start}.identity{text-align:center;padding:30px 24px}.portrait{height:112px;width:112px;border-radius:25px;display:grid;place-items:center;background:linear-gradient(135deg,#e5a94a,#70401c);border:3px solid #e7b55b;color:#26150c;font:700 43px 'Playfair Display',serif;margin:0 auto 16px;overflow:hidden}.portrait img{height:100%;width:100%;object-fit:cover}.identity h2{font:700 25px 'Playfair Display',serif;margin:0 0 7px}.identity p{color:#cdb49b;margin:0 0 18px}.profile-badge{display:inline-flex;align-items:center;gap:7px;background:#56351d;border:1px solid #a87438;color:#ffd48b;padding:8px 12px;border-radius:30px;font-size:11px;font-weight:800}.detail-list{margin-top:24px;text-align:left;border-top:1px solid #69462b;padding-top:13px}.detail{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid #533722}.detail span{color:#c5a88e;font-size:12px}.detail strong{font-size:12px;text-align:right;overflow-wrap:anywhere}.profile-stack{display:grid;gap:20px}.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}.field label{font-size:12px;color:#f0d1a5;font-weight:800}.field input{height:45px;background:#190e09;border:1px solid #8d5b30;color:#fff4e4;border-radius:10px;padding:0 13px;width:100%}.field input:disabled{opacity:.65;cursor:not-allowed}.field small{color:#bda38d;line-height:1.5}.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:12px 17px;border-radius:10px;font-weight:800;border:1px solid #b7803c;background:linear-gradient(135deg,#e5a84b,#aa681f);color:#211108}.btn svg{width:16px;height:16px}.btn:disabled{opacity:.55;cursor:not-allowed}.helper{color:#bfa58d;font-size:12px;line-height:1.7}.alert{padding:13px 16px;border-radius:11px;margin-bottom:16px;border:1px solid #b77842;background:#50301e;color:#ffdeb8}.alert.success{border-color:#4e8c60;background:#173925;color:#b1efc3}.alert ul{margin:7px 0 0;padding-left:20px}.section-top{display:flex;gap:12px;align-items:center;margin-bottom:19px}.section-top .stat-icon{width:44px;height:44px}.section-top h2{margin:0;font-size:18px}.section-top p{margin:5px 0 0;color:#c5ab94;font-size:12px}.password-wrap{position:relative}.password-wrap input{padding-right:48px}.reveal{position:absolute;right:6px;top:5px;height:35px;width:38px;background:transparent;border:0;color:#d7aa6e}.reveal svg{width:18px;height:18px}.upload{display:flex;align-items:center;gap:13px;flex-wrap:wrap}.upload input{max-width:100%;height:auto;padding:11px}.warning{border:1px solid #7c5b36;background:#291a12;border-radius:11px;padding:12px 14px;margin-top:16px;color:#e2bb89;font-size:12px;line-height:1.7}@media(max-width:1120px){.profile-grid{grid-template-columns:1fr}.identity{display:grid;grid-template-columns:140px 1fr;text-align:left;gap:4px 22px;align-items:center}.identity .portrait{grid-row:1/4;margin:0}.identity .detail-list{grid-column:1/-1}}@media(max-width:600px){.field-grid{grid-template-columns:1fr}.identity{display:block;text-align:center}.identity .portrait{margin:0 auto 16px}.detail strong{max-width:65%}.form-actions .btn{width:100%}}
</style></head><body><div class="mobile-shade" id="mobileShade"></div>

<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">

    <a class="brand" href="{{ route('superadmin.dashboard') }}">

        <span class="brand-icon"><i data-lucide="coffee"></i></span>

        <span class="brand-text"><span class="brand-title">The Brew Box</span><small>SUPER ADMINISTRATION</small></span>

    </a>

    <nav class="side-scroll">

        <div class="side-label">System Management</div>

        @foreach($menu as [$label, $icon, $routeName])

            @if(Route::has($routeName))

                <a class="nav {{ $routeName === 'superadmin.profile.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.profile.index') aria-current="page" @endif>

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

        <span class="top-title">Super Administrator / My Profile</span><span class="top-spacer"></span>

        <div class="account"><span class="avatar">@if($photoUrl)<img src="{{ $photoUrl }}" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.hidden=true">@else{{ Str::upper(Str::substr($viewer->name ?? 'S', 0, 1)) }}@endif</span><span><strong>{{ $viewer->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></div>

    </header>

    <main class="main"><div class="breadcrumbs">THE BREW BOX / SUPERADMIN / MY PROFILE</div>
<section class="hero"><div><div class="eyebrow">Account &amp; Security</div><h1>My Profile</h1><p>Review your account details and manage your personal information in a secure workspace.</p></div><div class="hero-symbol"><i data-lucide="circle-user-round"></i></div></section>
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert" role="alert"><strong>Please review the highlighted information.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="profile-grid">
 <section class="panel identity" aria-label="Account overview">
  <div class="portrait">@if($photoUrl)<img src="{{ $photoUrl }}" alt="Profile photo" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><span hidden>{{ Str::upper(Str::substr($viewer->name ?? 'S',0,1)) }}</span>@else{{ Str::upper(Str::substr($viewer->name ?? 'S',0,1)) }}@endif</div>
  <h2>{{ $viewer->name ?? 'Super Administrator' }}</h2><p>{{ $viewer->email ?? 'No email address' }}</p>
  <div><span class="profile-badge"><i data-lucide="shield-check" style="width:15px;height:15px"></i> SUPER ADMINISTRATOR</span></div>
  <div class="detail-list"><div class="detail"><span>Account status</span><strong>{{ ucfirst((string)($viewer->status ?? 'Unknown')) }}</strong></div><div class="detail"><span>Username</span><strong>{{ $viewer->username ?? 'Not set' }}</strong></div><div class="detail"><span>Member since</span><strong>{{ $viewer?->created_at ? \Illuminate\Support\Carbon::parse($viewer->created_at)->format('M d, Y') : '—' }}</strong></div></div>
 </section>
 <div class="profile-stack">
  <section class="panel"><div class="section-top"><span class="stat-icon"><i data-lucide="user-round-pen"></i></span><div><h2>Personal Information</h2><p>Your profile details and contact information.</p></div></div>
  @if($profileUpdateRoute)
  <form method="POST" action="{{ route($profileUpdateRoute) }}" enctype="multipart/form-data">@csrf @method('PATCH')
  @else
  <div class="warning">Profile editing is not enabled yet. Register a profile update route and controller action to activate saving.</div>
  @endif
  <div class="field-grid"><div class="field"><label for="profileName">Full Name</label><input id="profileName" name="name" value="{{ old('name',$viewer->name ?? '') }}" maxlength="255" autocomplete="name" {{ $profileUpdateRoute ? 'required' : 'disabled' }}></div><div class="field"><label for="profileUsername">Username</label><input id="profileUsername" name="username" value="{{ old('username',$viewer->username ?? '') }}" autocomplete="username" {{ $profileUpdateRoute ? '' : 'disabled' }}></div><div class="field full"><label for="profileEmail">Email Address</label><input id="profileEmail" type="email" name="email" value="{{ old('email',$viewer->email ?? '') }}" autocomplete="email" {{ $profileUpdateRoute ? 'required' : 'disabled' }}></div><div class="field full"><label for="photoInput">Profile Photo</label><div class="upload"><input id="photoInput" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" {{ $profileUpdateRoute ? '' : 'disabled' }}><img id="photoPreview" hidden alt="New photo preview" style="width:55px;height:55px;border-radius:10px;object-fit:cover"></div><small>Optional. JPEG, PNG or WebP, up to 5 MB. The image is saved when you click Save Changes.</small></div><div class="field full"><label style="display:flex;align-items:center;gap:9px;font-weight:600"><input type="checkbox" name="remove_photo" value="1" style="width:16px;height:16px" /> Remove current profile photo</label></div></div>
  @if($profileUpdateRoute)<div class="form-actions"><button class="btn" type="submit"><i data-lucide="save"></i> Save Changes</button></div></form>@endif
  </section>
  <section class="panel"><div class="section-top"><span class="stat-icon"><i data-lucide="lock-keyhole"></i></span><div><h2>Account Security</h2><p>Keep your Superadmin account protected.</p></div></div>
  @if($passwordUpdateRoute)
  <form method="POST" action="{{ route($passwordUpdateRoute) }}">@csrf @method('PUT')
  <div class="field-grid"><div class="field full"><label for="currentPassword">Current Password</label><div class="password-wrap"><input type="password" id="currentPassword" name="current_password" autocomplete="current-password" required><button type="button" class="reveal" data-reveal="currentPassword" aria-label="Show password"><i data-lucide="eye"></i></button></div></div><div class="field"><label for="newPassword">New Password</label><div class="password-wrap"><input type="password" id="newPassword" name="password" autocomplete="new-password" minlength="8" required><button type="button" class="reveal" data-reveal="newPassword" aria-label="Show password"><i data-lucide="eye"></i></button></div></div><div class="field"><label for="confirmPassword">Confirm New Password</label><div class="password-wrap"><input type="password" id="confirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required><button type="button" class="reveal" data-reveal="confirmPassword" aria-label="Show password"><i data-lucide="eye"></i></button></div></div></div>
  <div class="form-actions"><button class="btn" type="submit"><i data-lucide="shield-check"></i> Update Password</button></div></form>
  @else <div class="warning">Password changes are not enabled yet. A secure password update endpoint is required to verify your current password and save a new hash.</div>@endif
  </section>
 </div>
</div></main></div><script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) window.lucide.createIcons();
  const body=document.body, toggle=document.getElementById('sidebarToggle'), shade=document.getElementById('mobileShade');
  const mobile=()=>window.matchMedia('(max-width:800px)').matches;
  const close=()=>{body.classList.remove('mobile-open');toggle.setAttribute('aria-expanded','false')};
  toggle.addEventListener('click',()=>{if(mobile()){toggle.setAttribute('aria-expanded',String(body.classList.toggle('mobile-open')))}else{const collapsed=body.classList.toggle('collapsed');toggle.setAttribute('aria-expanded',String(!collapsed));try{localStorage.setItem('brew_superadmin_sidebar_collapsed',collapsed?'1':'0')}catch(e){}}});
  shade.addEventListener('click',close);document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});
  try{if(!mobile()&&localStorage.getItem('brew_superadmin_sidebar_collapsed')==='1'){body.classList.add('collapsed');toggle.setAttribute('aria-expanded','false')}}catch(e){}
  window.addEventListener('resize',()=>{if(!mobile())close()});
  const reveal=document.querySelectorAll('[data-reveal]');reveal.forEach(btn=>btn.addEventListener('click',()=>{const input=document.getElementById(btn.dataset.reveal);if(input){input.type=input.type==='password'?'text':'password';btn.setAttribute('aria-label',input.type==='password'?'Show password':'Hide password')}}));
  const photo=document.getElementById('photoInput'), preview=document.getElementById('photoPreview');
  if(photo&&preview)photo.addEventListener('change',()=>{const file=photo.files?.[0];if(file&&file.type.startsWith('image/')){const url=URL.createObjectURL(file);preview.src=url;preview.hidden=false;preview.onload=()=>URL.revokeObjectURL(url)}});
});
</script></body></html>