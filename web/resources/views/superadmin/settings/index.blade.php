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
<title>System Settings | The Brew Box</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<style>
:root{--bg:#140b08;--panel:#27170f;--gold:#f1b94f;--text:#fff4e4;--muted:#bea994;--side:258px}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,#3b2215 0,transparent 38%),var(--bg);min-height:100vh;color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,textarea,select{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:2px solid var(--gold);outline-offset:2px}.sidebar{width:var(--side);position:fixed;inset:0 auto 0 0;z-index:30;display:flex;flex-direction:column;background:linear-gradient(180deg,#27160d,#120a07);border-right:1px solid #8d5b29;transition:transform .2s,width .2s}.brand{min-height:85px;display:flex;align-items:center;gap:12px;padding:23px 18px;border-bottom:1px solid #6e482a}.brand-icon{color:var(--gold)}.brand-icon svg{width:35px;height:35px}.brand-title{display:block;font:700 21px 'Playfair Display',serif;white-space:nowrap}.brand-text small{font-size:10px;color:#e3ba79;letter-spacing:.8px}.side-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 10px}.side-label{font-size:10px;letter-spacing:2px;color:#c68d53;text-transform:uppercase;margin:13px 12px 9px;font-weight:800}.nav{display:flex;align-items:center;gap:13px;min-height:43px;padding:10px 12px;border-radius:10px;color:#d5baa0;font-size:12px;font-weight:700;margin-bottom:3px;white-space:nowrap}.nav:hover{background:#49301d;color:#fff}.nav.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:#fff}.nav svg{width:18px;height:18px;color:#e7ad55;flex-shrink:0}.sidebar-footer{padding:10px;border-top:1px solid #654328}.logout{border:0;background:none;width:100%;text-align:left;color:#f0a19a}.logout svg{color:#ff746b}.workspace{margin-left:var(--side);min-height:100vh;transition:margin-left .2s}.topbar{height:78px;display:flex;align-items:center;gap:16px;padding:0 30px;background:#1e110cdd;border-bottom:1px solid #654128;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}.icon-button{width:41px;height:41px;border:1px solid #a46a2b;background:linear-gradient(140deg,#d99835,#71400f);color:#1d1008;border-radius:11px;display:grid;place-items:center}.top-title{font-weight:800}.top-spacer{flex:1}.account{display:flex;align-items:center;gap:10px;border-left:1px solid #634126;padding-left:16px}.avatar{width:39px;height:39px;border-radius:50%;background:#f1b94f;color:#28170d;display:grid;place-items:center;font-weight:800;overflow:hidden;flex-shrink:0}.avatar img{width:100%;height:100%;object-fit:cover}.account strong{font-size:12px}.account small{display:block;font-size:10px;color:var(--gold);margin-top:3px}.main{max-width:1450px;margin:auto;padding:29px 30px 50px}.breadcrumbs{color:#d9a05c;font-size:11px;font-weight:700;letter-spacing:.6px;margin-bottom:17px}.hero{border:1px solid #b9772e;border-radius:20px;background:radial-gradient(circle at 85% 40%,#84502470,transparent 35%),linear-gradient(115deg,#382014,#1b100b);padding:29px;display:flex;align-items:center;justify-content:space-between;gap:18px;box-shadow:0 0 25px #c17b2a24;margin-bottom:21px}.eyebrow{color:var(--gold);font-size:11px;text-transform:uppercase;letter-spacing:2px;font-weight:800}.hero h1{font:700 clamp(28px,3.2vw,41px) 'Playfair Display',serif;margin:8px 0}.hero p{color:#e1c9b0;font-size:13px;margin:0;line-height:1.6}.hero-symbol{color:#f6b85a;opacity:.7}.hero-symbol svg{width:75px;height:75px}.layout{display:grid;grid-template-columns:235px minmax(0,1fr);gap:20px;align-items:start}.tabs{border:1px solid #98652f;border-radius:16px;background:linear-gradient(145deg,#352117,#21130e);padding:12px;position:sticky;top:100px}.tab{width:100%;display:flex;gap:12px;align-items:center;text-align:left;padding:13px 12px;border:0;border-radius:10px;background:none;color:#d5baa0;font-weight:700;margin:2px 0}.tab svg{width:18px;color:#e7ad55}.tab.active{background:linear-gradient(95deg,#8d511b,#4c2912);box-shadow:inset 0 0 0 1px #e8a642;color:white}.panel{background:linear-gradient(145deg,#352117,#21130e);border:1px solid #98652f;border-radius:16px;box-shadow:0 0 18px #e69b3020;padding:26px;margin-bottom:18px}.panel h2{margin:0;font-size:19px}.panel p{font-size:12px;color:#cbb19a;line-height:1.7;margin:7px 0 23px}.section-heading{display:flex;align-items:center;gap:12px;margin-bottom:8px}.section-heading>span{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;background:#7e4a1f;color:#ffd17c}.section-heading svg{width:20px}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.field{display:flex;flex-direction:column;gap:8px}.field.full{grid-column:1/-1}.field label{font-size:12px;color:#f1d0a4;font-weight:700}.field input,.field select,.field textarea{width:100%;min-height:44px;padding:12px 14px;border-radius:10px;background:#1b100b;color:#fff4e4;border:1px solid #815630}.field textarea{resize:vertical;min-height:92px}.hint{color:#ad927c;font-size:11px}.switch-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 0;border-bottom:1px solid #62432c}.switch-row:last-child{border-bottom:0}.switch-row strong{display:block;font-size:13px}.switch-row small{display:block;color:#cbb19a;margin-top:5px;line-height:1.6}.switch{position:relative;display:inline-block;width:49px;height:27px;flex-shrink:0}.switch input{position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}.switch-track{position:absolute;inset:0;background:#644434;border-radius:20px;transition:.2s;pointer-events:none}.switch-track:before{content:'';position:absolute;width:19px;height:19px;left:4px;top:4px;background:#fff;border-radius:50%;transition:.2s}.switch input:checked+.switch-track{background:#e2a83d}.switch input:checked+.switch-track:before{transform:translateX(22px)}.switch input:focus-visible+.switch-track{outline:2px solid white;outline-offset:3px}.actions{display:flex;justify-content:flex-end;gap:12px;align-items:center;margin:20px 0}.save{background:linear-gradient(110deg,#e6b25b,#b97624);color:#201108;border:1px solid #f7ce7c;padding:14px 24px;border-radius:11px;font-weight:800}.note{color:#cbb19a;font-size:12px}.notice{border:1px solid #b26c43;border-radius:10px;padding:13px 15px;margin-bottom:18px;background:#573023;color:#ffdbbb}.errors{border-color:#d86b5d}.collapsed{--side:76px}.collapsed .brand{padding:24px 18px}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:none}.collapsed .nav{justify-content:center;padding:12px}.collapsed .side-scroll{padding:14px 9px}@media(max-width:1000px){.layout{grid-template-columns:1fr}.tabs{position:static;display:flex;flex-wrap:wrap}.tab{width:auto}}@media(max-width:800px){.sidebar,.collapsed .sidebar{width:270px;transform:translateX(-100%)}.workspace,.collapsed .workspace{margin-left:0}.collapsed .brand-text,.collapsed .nav-text,.collapsed .side-label{display:block}.collapsed .nav{justify-content:flex-start}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .mobile-shade{display:block;position:fixed;inset:0;background:#000b;z-index:20}.topbar{padding:0 17px}.hero-symbol{display:none}}@media(max-width:580px){.fields{grid-template-columns:1fr}.hero{padding:22px}.panel{padding:18px}.main{padding:20px 15px}.account small,.account strong{display:none}.top-title{font-size:12px}}.mobile-shade{display:none}
</style>
</head>
<body>
<div class="mobile-shade" id="mobileShade"></div>
<aside class="sidebar" id="sidebar" aria-label="Superadmin navigation">
<a class="brand" href="{{ route('superadmin.dashboard') }}"><span class="brand-icon"><i data-lucide="coffee"></i></span><span class="brand-text"><span class="brand-title">The Brew Box</span><small>SUPER ADMINISTRATION</small></span></a>
<nav class="side-scroll"><div class="side-label">System Management</div>
@foreach($menu as [$label,$icon,$routeName])
@if(Route::has($routeName))
<a class="nav {{ $routeName === 'superadmin.settings.index' ? 'active' : '' }}" href="{{ route($routeName) }}" title="{{ $label }}" @if($routeName === 'superadmin.settings.index') aria-current="page" @endif><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></a>
@else
<span class="nav" style="opacity:.5" title="Route not yet registered"><i data-lucide="{{ $icon }}"></i><span class="nav-text">{{ $label }}</span></span>
@endif
@endforeach
</nav>
<div class="sidebar-footer"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav logout" type="submit"><i data-lucide="log-out"></i><span class="nav-text">Sign Out</span></button></form></div>
</aside>
<div class="workspace">
<header class="topbar"><button class="icon-button" type="button" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button><span class="top-title">Super Administrator / System Settings</span><span class="top-spacer"></span>
<a class="account" href="{{ Route::has('superadmin.profile.index') ? route('superadmin.profile.index') : '#' }}"><span class="avatar">@if($avatar)<img src="{{ $avatar }}" alt="Profile photo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'"><span style="display:none">{{ Str::upper(Str::substr($viewer?->name ?? 'S',0,1)) }}</span>@else{{ Str::upper(Str::substr($viewer?->name ?? 'S',0,1)) }}@endif</span><span><strong>{{ $viewer?->name ?? 'Superadmin' }}</strong><small>Super Administrator</small></span></a>
</header>
<main class="main"><div class="breadcrumbs">THE BREW BOX / SUPERADMIN / SYSTEM SETTINGS</div>
<section class="hero"><div><div class="eyebrow">Platform Configuration</div><h1>System Settings</h1><p>Configure your café identity, financial defaults, and operational preferences in one secure place.</p></div><div class="hero-symbol"><i data-lucide="settings-2"></i></div></section>
@if($error)<div class="notice" role="alert">{{ $error }}</div>@endif
@if($errors->any())<div class="notice errors" role="alert"><strong>Please correct these fields:</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<form id="settingsForm" method="POST" action="{{ route('superadmin.settings.update') }}">@csrf @method('PATCH')
<div class="layout"><aside class="tabs" aria-label="Settings sections"><button class="tab active" type="button" data-target="general"><i data-lucide="store"></i> General</button><button class="tab" type="button" data-target="finance"><i data-lucide="wallet"></i> Financial</button><button class="tab" type="button" data-target="operations"><i data-lucide="bell"></i> Operations</button></aside>
<div>
<section class="panel" id="general"><div class="section-heading"><span><i data-lucide="store"></i></span><h2>Business Information</h2></div><p>These details can be used across customer-facing screens, receipts, and notifications when integrated with the corresponding modules.</p>
<div class="fields">
<div class="field"><label for="site_name">Business name *</label><input id="site_name" name="site_name" maxlength="150" required value="{{ old('site_name',$settings['site_name']) }}"></div>
<div class="field"><label for="site_tagline">Tagline</label><input id="site_tagline" name="site_tagline" maxlength="250" value="{{ old('site_tagline',$settings['site_tagline']) }}"></div>
<div class="field"><label for="business_email">Business email</label><input id="business_email" name="business_email" type="email" maxlength="191" value="{{ old('business_email',$settings['business_email']) }}"></div>
<div class="field"><label for="business_phone">Contact number</label><input id="business_phone" name="business_phone" maxlength="30" value="{{ old('business_phone',$settings['business_phone']) }}"></div>
<div class="field full"><label for="business_address">Business address</label><textarea id="business_address" name="business_address" maxlength="500">{{ old('business_address',$settings['business_address']) }}</textarea></div>
</div></section>
<section class="panel" id="finance"><div class="section-heading"><span><i data-lucide="wallet"></i></span><h2>Financial Defaults</h2></div><p>Set reporting currency, operating timezone, and optional charge percentages. These settings do not automatically change existing orders.</p>
<div class="fields"><div class="field"><label for="currency">Currency</label><select id="currency" name="currency">@foreach(['PHP'=>'Philippine Peso (PHP)','USD'=>'US Dollar (USD)','EUR'=>'Euro (EUR)'] as $value=>$label)<option value="{{ $value }}" @selected(old('currency',$settings['currency']) === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="timezone">Timezone</label><select id="timezone" name="timezone">@foreach(['Asia/Manila'=>'Asia/Manila (Philippines)','UTC'=>'UTC','Asia/Singapore'=>'Asia/Singapore','America/New_York'=>'America/New_York'] as $value=>$label)<option value="{{ $value }}" @selected(old('timezone',$settings['timezone']) === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="tax_rate">Tax rate (%)</label><input id="tax_rate" name="tax_rate" type="number" min="0" max="100" step="0.01" required value="{{ old('tax_rate',$settings['tax_rate']) }}"><span class="hint">Administrative default only; connect to checkout logic separately.</span></div>
<div class="field"><label for="service_charge_rate">Service charge (%)</label><input id="service_charge_rate" name="service_charge_rate" type="number" min="0" max="100" step="0.01" required value="{{ old('service_charge_rate',$settings['service_charge_rate']) }}"></div></div></section>
<section class="panel" id="operations"><div class="section-heading"><span><i data-lucide="bell"></i></span><h2>Operational Preferences</h2></div><p>Saved switches become available to other modules once those modules read the settings table.</p>
@foreach([['order_notifications','Order notifications','Enable the preference for new order alerts.'],['reservation_notifications','Reservation notifications','Enable the preference for reservation alerts.'],['maintenance_mode','Maintenance mode','Flag the system for maintenance. This does not block access until middleware enforces it.']] as [$key,$label,$description])
<div class="switch-row"><div><strong>{{ $label }}</strong><small>{{ $description }}</small></div><label class="switch" aria-label="{{ $label }}"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked((string) old($key,$settings[$key]) === '1')><span class="switch-track"></span></label></div>
@endforeach
</section>
<div class="actions"><span class="note">Changes are saved to the settings table.</span><button class="save" type="submit" @disabled((bool) $error)><i data-lucide="save" style="width:16px;height:16px;vertical-align:middle"></i> Save Changes</button></div>
</div></div></form>
</main></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 if(window.lucide)window.lucide.createIcons();
 const body=document.body,toggle=document.getElementById('sidebarToggle'),shade=document.getElementById('mobileShade');
 const mobile=()=>window.matchMedia('(max-width:800px)').matches;
 const closeMobile=()=>{body.classList.remove('mobile-open');toggle.setAttribute('aria-expanded','false')};
 toggle.addEventListener('click',()=>{if(mobile()){const open=body.classList.toggle('mobile-open');toggle.setAttribute('aria-expanded',String(open))}else{const collapsed=body.classList.toggle('collapsed');toggle.setAttribute('aria-expanded',String(!collapsed));try{localStorage.setItem('brew_superadmin_sidebar_collapsed',collapsed?'1':'0')}catch(e){}}});
 shade.addEventListener('click',closeMobile);document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMobile()});
 try{if(!mobile()&&localStorage.getItem('brew_superadmin_sidebar_collapsed')==='1'){body.classList.add('collapsed');toggle.setAttribute('aria-expanded','false')}}catch(e){}
 window.addEventListener('resize',()=>{if(!mobile())closeMobile()});
 const tabs=document.querySelectorAll('.tab');tabs.forEach(tab=>tab.addEventListener('click',()=>{tabs.forEach(t=>t.classList.toggle('active',t===tab));document.getElementById(tab.dataset.target).scrollIntoView({behavior:'smooth',block:'start'})}));
 const form=document.getElementById('settingsForm');form.addEventListener('submit',async e=>{if(!window.Swal)return;e.preventDefault();const result=await Swal.fire({title:'Save system settings?',text:'Your changes will be saved to the database.',icon:'question',showCancelButton:true,confirmButtonText:'Save changes',confirmButtonColor:'#bd812e',cancelButtonColor:'#5b4437',background:'#2a1910',color:'#fff4e4'});if(result.isConfirmed)form.submit()});
 @if(session('success'))
 if(window.Swal)Swal.fire({icon:'success',title:'Settings updated',text:@json(session('success')),confirmButtonColor:'#bd812e',background:'#2a1910',color:'#fff4e4'});
 @endif
});
</script>
</body></html>
