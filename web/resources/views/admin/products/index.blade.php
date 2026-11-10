@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
$nav = [
 ['Dashboard','layout-dashboard','dashboard'],['Orders','shopping-cart','orders'],['Table Management','armchair','tables'],
 ['Reservations','calendar-days','reservations'],['Products','coffee','products'],['Categories','layers','categories'],
 ['Inventory','boxes','inventory'],['Recipes','book-open','recipes'],['Suppliers','truck','suppliers'],
 ['Purchases','package-plus','purchases'],['Customers','users','customers'],['Staff Management','contact','staff'],
 ['Payments','credit-card','payments'],['Expenses','receipt','expenses'],['Reports','chart-no-axes-combined','reports'],
 ['Promotions','badge-percent','promotions'],['Announcements','megaphone','announcements'],
 ['Notifications','bell','notifications'],['Users','user-round','users'],['My Profile','circle-user','profile'],['Settings','settings','settings']
];
$avatarRaw = trim((string)(auth()->user()->avatar ?? ''));
$avatarPath = preg_replace('#^(?:/)?(?:storage/|public/)#','',$avatarRaw);
$avatarUrl = $avatarRaw === '' ? null : (Str::startsWith($avatarRaw,['https://','http://']) ? $avatarRaw : Storage::disk('public')->url($avatarPath));
$photoUrl = fn($raw) => !$raw ? null : (Str::startsWith($raw,['https://','http://']) ? $raw : Storage::disk('public')->url(preg_replace('#^(?:/)?(?:storage/|public/)#','',(string)$raw)));
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Manage Products | The Brew Box</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<style>
:root{--bg:#120a07;--sidebar:#180d08;--surface:#28170f;--surface2:#332015;--gold:#ffc866;--gold2:#d99639;--text:#fff7ec;--muted:#c4a98c;--border:#83552a;--side-width:274px}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(ellipse at 78% -10%,#4a2813 0,transparent 43%),#100907;color:var(--text);font:14px 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}button{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:2px solid var(--gold);outline-offset:3px}
.sidebar{position:fixed;inset:0 auto 0 0;width:var(--side-width);z-index:40;background:linear-gradient(165deg,#261409,#120907 70%);border-right:1px solid #8b562c;display:flex;flex-direction:column;box-shadow:8px 0 36px #0005;transition:width .25s,transform .25s}
.brand{height:92px;display:flex;align-items:center;gap:13px;padding:20px;border-bottom:1px solid #a06c343d;overflow:hidden;white-space:nowrap}
.brand-mark{width:52px;height:52px;flex:none;display:grid;place-items:center;color:var(--gold);border:1px solid #c78a46;border-radius:15px;background:#3b2113;box-shadow:0 0 18px #e3a2462b}.brand-mark svg{width:29px}
.brand-text strong{display:block;font:700 22px/1.05 'Playfair Display',serif}.brand-text small{font-size:10px;color:#d8af7c}
.role-pill{display:flex;align-items:center;gap:10px;white-space:nowrap;margin:18px 13px 12px;padding:12px;border:1px solid #a76e36;border-radius:40px;background:linear-gradient(90deg,#5a3216,#2b180e);color:#ffda8e;font-weight:800;font-size:12px}.role-pill svg{width:19px}
.nav{flex:1;overflow:auto;padding:4px 12px 18px;scrollbar-width:thin;scrollbar-color:#7b4b27 transparent}.nav-caption{font-size:9px;text-transform:uppercase;letter-spacing:2px;color:#a17c5b;font-weight:800;padding:14px 14px 8px;white-space:nowrap}.nav-item{min-height:43px;display:flex;align-items:center;gap:13px;padding:11px 13px;border-radius:11px;margin:3px 0;color:#e8d3bd;font-size:12px;font-weight:650;white-space:nowrap}.nav-item svg{width:18px;height:18px;color:#f1bd67;flex:none}.nav-item:hover{background:#71422170}.nav-item.active{color:#fff;background:linear-gradient(90deg,#a66322,#583013);box-shadow:inset 0 0 0 1px #ffc16a,0 0 17px #d9913470}.nav-item.active svg{color:#fff1cb}.nav-label{overflow:hidden;text-overflow:ellipsis}.sidebar-bottom{border-top:1px solid #aa75452c;padding:12px}.logout{background:none;border:0;width:100%;color:#ff7979;text-align:left}.logout svg{color:#ff7979}
.workspace{margin-left:var(--side-width);min-width:0;transition:margin-left .25s}.topbar{height:92px;position:sticky;top:0;z-index:25;display:flex;align-items:center;gap:17px;padding:0 28px;background:#1a0e08ed;backdrop-filter:blur(14px);border-bottom:1px solid #8b5a2c}.toggle{width:44px;height:44px;display:grid;place-items:center;flex:none;border:1px solid #e6a74b;border-radius:12px;color:#1a0e08;background:linear-gradient(145deg,#ffe49b,#dc942c);box-shadow:0 0 18px #edaa4180}.toggle svg{width:21px}.top-title h1{font-size:clamp(17px,2.1vw,26px);margin:0}.top-title p{font-size:11px;color:#cdb395;margin:5px 0 0}.top-spacer{flex:1}.user-chip{display:flex;align-items:center;gap:11px;border-left:1px solid #6b452c;padding-left:17px}.avatar{width:41px;height:41px;border:2px solid #d99e4a;border-radius:50%;object-fit:cover;display:grid;place-items:center;background:#5a351e;color:#ffd999;font-weight:800}.user-chip strong{font-size:12px;display:block}.user-chip small{display:block;font-size:10px;color:var(--gold);margin-top:3px}
.main{max-width:1700px;margin:auto;padding:22px 26px 45px}.hero{position:relative;overflow:hidden;min-height:145px;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:27px 34px;border:1px solid #e9a950;border-radius:18px;margin-bottom:18px;background:linear-gradient(90deg,#2b140ae8,#391a0bd8 60%,#160a06a8),url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1600&q=85') center/cover;box-shadow:inset 0 0 0 1px #ffce6630,0 0 22px #d17c2638}.eyebrow{color:#ffcb75;letter-spacing:2.4px;font-size:10px;text-transform:uppercase;font-weight:800}.hero h2{font-size:clamp(22px,2.6vw,34px);margin:10px 0 7px}.hero p{color:#f1d9c0;font-size:12px;margin:0}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #a76e36;border-radius:11px;padding:11px 15px;background:#462817;color:#ffdeab;font-size:12px;font-weight:800;white-space:nowrap}.btn svg{width:17px;height:17px}.btn.primary{background:linear-gradient(145deg,#ffe49b,#dc942c);color:#291508;border-color:#ffcf75}.btn:hover{filter:brightness(1.12)}.btn.danger{color:#ffc0b7;border-color:#97453b;background:#50251d}.btn.small{padding:8px 10px}.btn:disabled{opacity:.5;cursor:not-allowed}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.stat{min-height:112px;display:flex;align-items:center;gap:12px;padding:16px 13px;border:1px solid #d9a059;border-radius:17px;background:linear-gradient(145deg,#55321c,#2a170e 73%);box-shadow:inset 0 1px 0 #fff2c533,0 5px 18px #0004}.stat-icon{width:46px;height:46px;flex:none;display:grid;place-items:center;border:1px solid #aa692e;border-radius:50%;background:linear-gradient(135deg,#6f3e16,#261106);color:var(--gold)}.stat-icon svg{width:23px}.stat small{font-size:11px;color:#f3d9b8}.stat strong{display:block;font-size:clamp(21px,2.5vw,32px);line-height:1.2;margin:3px 0}.stat em{font-size:9px;color:#cba982;font-style:normal}
.panel{border:1px solid #c28a48;border-radius:17px;padding:20px;background:linear-gradient(145deg,#402619,#26150e 80%);box-shadow:inset 0 1px 0 #fff1d41f,0 0 15px #ba702a24}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:19px;flex-wrap:wrap}.panel-head h3{display:flex;align-items:center;gap:9px;font-size:16px;margin:0}.panel-head h3 svg{width:19px;color:var(--gold)}.muted{color:var(--muted)}.filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.input,.select,.textarea{background:#23140e;border:1px solid #815633;border-radius:10px;padding:11px 12px;color:#fff5e7;min-width:0;width:100%;outline:none}.input::placeholder,.textarea::placeholder{color:#b89b82}.filters .input{width:235px}.filters .select{width:150px}.table-scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:830px}thead{background:#21120c}th{padding:13px 11px;color:#efc17f;font-size:10px;text-align:left}td{padding:13px 11px;border-top:1px solid #9866373b;color:#f0ddc8;font-size:12px}td strong{color:#fff7eb}.product-cell{display:flex;align-items:center;gap:12px}.product-photo{width:53px;height:53px;flex:none;object-fit:cover;border-radius:12px;border:1px solid #9c6738;background:#4e301d}.photo-empty{display:grid;place-items:center;color:#ffd39a}.product-photo svg{width:23px}.product-cell small{display:block;color:#c7a68b;margin-top:4px;font-size:10px}.status{display:inline-block;padding:6px 10px;border-radius:20px;font-size:10px;font-weight:800;background:#694326;color:#e9c9a6}.status.good{background:#23523a;color:#a5e6ae}.status.bad{background:#613329;color:#ffb5a1}.status.gold{background:#674b20;color:#ffe2a0}.row-actions{display:flex;align-items:center;gap:7px}.empty{text-align:center;padding:55px 12px;color:#c4a586}.pagination{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:18px;flex-wrap:wrap;color:#c8ac92;font-size:11px}.pages{display:flex;gap:7px;align-items:center}
.alert{border:1px solid #a76e36;border-radius:12px;padding:14px 17px;margin-bottom:16px;background:#3e2717;color:#ffdbab}.alert.success{border-color:#45885a;background:#203d2b;color:#b9f2c3}.alert.error{border-color:#bd5d4d;background:#51241f;color:#ffcbc1}.alert ul{margin:6px 0 0 17px}
.modal-backdrop{position:fixed;inset:0;background:#070403c9;z-index:100;display:none;align-items:center;justify-content:center;padding:18px;backdrop-filter:blur(6px)}.modal-backdrop.open{display:flex}.modal{width:min(780px,100%);max-height:94vh;overflow:auto;background:linear-gradient(145deg,#412719,#20130e);border:1px solid #d49a55;border-radius:20px;box-shadow:0 30px 100px #000b}.modal-header{padding:23px 25px;border-bottom:1px solid #87552e;display:flex;justify-content:space-between;align-items:center;gap:10px}.modal-header h3{margin:0;font-size:21px}.modal-content{padding:24px 25px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:17px}.field label{display:block;color:#f1d6b6;font-size:11px;font-weight:800;margin-bottom:8px}.field.full{grid-column:1/-1}.field small{display:block;margin-top:7px;color:#bda080;font-size:10px}.check-row{display:flex;gap:16px;flex-wrap:wrap}.check-row label{display:flex;align-items:center;gap:8px;color:#f1d6b6;font-size:12px}.check-row input{accent-color:#df9c45;width:16px;height:16px}.modal-footer{display:flex;justify-content:flex-end;gap:9px;border-top:1px solid #87552e;padding:17px 25px}.image-preview{display:none;max-width:150px;max-height:130px;object-fit:cover;margin-top:10px;border-radius:12px;border:1px solid #9d6c3c}.shade{display:none}
.sidebar-collapsed{--side-width:77px}.sidebar-collapsed .brand{padding-left:12px}.sidebar-collapsed .brand-text,.sidebar-collapsed .role-pill span,.sidebar-collapsed .nav-label,.sidebar-collapsed .nav-caption{display:none}.sidebar-collapsed .role-pill{justify-content:center;margin-left:9px;margin-right:9px;padding:11px}.sidebar-collapsed .nav-item{justify-content:center;padding:11px}.sidebar-collapsed .nav{padding:4px 8px 18px}
@media(max-width:1200px){.stats{grid-template-columns:repeat(2,1fr)}.filters .input{width:200px}}
@media(max-width:820px){.sidebar,.sidebar-collapsed .sidebar{width:274px;transform:translateX(-100%)}.workspace,.sidebar-collapsed .workspace{margin-left:0}.sidebar-collapsed .brand-text,.sidebar-collapsed .role-pill span,.sidebar-collapsed .nav-label,.sidebar-collapsed .nav-caption{display:initial}.sidebar-collapsed .nav-caption{display:block}.sidebar-collapsed .nav-item{justify-content:flex-start;padding:11px 13px}.sidebar-collapsed .nav{padding:4px 12px 18px}.mobile-open .sidebar{transform:translateX(0)}.mobile-open .shade{display:block;position:fixed;inset:0;background:#000a;z-index:35}.topbar{height:75px;padding:0 15px}.main{padding:16px}.user-chip strong,.user-chip small{display:none}.filters .input{width:100%}.filters .select{flex:1;width:auto}.hero{padding:22px}}
@media(max-width:570px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.stat{min-height:95px;padding:12px}.stat-icon{width:35px;height:35px}.stat-icon svg{width:18px}.stat strong{font-size:23px}.stat em{display:none}.hero{flex-direction:column;align-items:flex-start}.panel{padding:14px}.form-grid{grid-template-columns:1fr}.modal-content{padding:16px}.modal-header,.modal-footer{padding:16px}.main{padding:12px}}
</style>
</head>
<body>
<div class="shade" id="shade"></div>
<aside class="sidebar" id="sidebar" aria-label="Admin navigation">
<a href="{{ route('admin.dashboard') }}" class="brand"><span class="brand-mark"><i data-lucide="coffee"></i></span><span class="brand-text"><strong>The<br>Brew Box</strong><small>Sip · Relax · Repeat</small></span></a>
<div class="role-pill"><i data-lucide="shield-check"></i><span>Administrator</span></div>
<nav class="nav">
<div class="nav-caption">CAFÉ MANAGEMENT</div>
@foreach($nav as [$label,$icon,$segment])
@php $routeName=$segment==='dashboard'?'admin.dashboard':'admin.'.$segment.'.index'; @endphp
@if(Route::has($routeName))
<a class="nav-item {{ $segment==='products'?'active':'' }}" href="{{ route($routeName) }}" title="{{ $label }}"><i data-lucide="{{ $icon }}"></i><span class="nav-label">{{ $label }}</span></a>
@else
<span class="nav-item" aria-disabled="true" style="opacity:.45"><i data-lucide="{{ $icon }}"></i><span class="nav-label">{{ $label }}</span></span>
@endif
@endforeach
</nav>
<div class="sidebar-bottom"><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-item logout" type="submit"><i data-lucide="log-out"></i><span class="nav-label">Sign Out</span></button></form></div>
</aside>
<div class="workspace">
<header class="topbar">
<button type="button" class="toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true"><i data-lucide="panel-left-close"></i></button>
<div class="top-title"><h1>Products Management</h1><p>Manage your café menu, pricing and availability</p></div>
<div class="top-spacer"></div>
<a class="user-chip" href="{{ Route::has('admin.profile.index')?route('admin.profile.index'):'#' }}">
@if($avatarUrl)<img class="avatar" src="{{ $avatarUrl }}" alt="Admin profile">@else<span class="avatar">{{ strtoupper(Str::substr(auth()->user()->name ?? 'A',0,1)) }}</span>@endif
<span><strong>{{ auth()->user()->name ?? 'Admin' }}</strong><small>Administrator</small></span>
</a>
</header>
<main class="main">
<section class="hero"><div><div class="eyebrow">THE BREW BOX · MENU CONTROL</div><h2>Craft Every Great Cup.</h2><p>Keep your product catalog beautiful, organized, and ready for customers.</p></div><button type="button" class="btn primary" id="createBtn"><i data-lucide="plus"></i> Add New Product</button></section>
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Please correct these errors:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="stats" aria-label="Product statistics">
@foreach([['All Products',$counts['total'],'package','Products in catalog'],['Active Products',$counts['active'],'check-circle-2','Visible in your catalog'],['Featured Items',$counts['featured'],'star','Highlighted products'],['Unavailable',$counts['unavailable'],'circle-off','Temporarily out of stock']] as [$label,$count,$icon,$detail])
<article class="stat"><span class="stat-icon"><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($count) }}</strong><em>{{ $detail }}</em></div></article>
@endforeach
</section>
<section class="panel">
<div class="panel-head"><h3><i data-lucide="coffee"></i> Product Catalog <span class="muted" style="font-size:11px;font-weight:500">({{ $products->total() }} results)</span></h3>
<form method="GET" action="{{ route('admin.products.index') }}" class="filters" id="filtersForm">
<input class="input" name="search" value="{{ request('search') }}" placeholder="Search name, SKU, description..." aria-label="Search products">
<select class="select" name="category" aria-label="Filter category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('category')===(string)$category->id)>{{ $category->name }}</option>@endforeach</select>
<select class="select" name="status" aria-label="Filter status"><option value="">All statuses</option>@foreach(['active'=>'Active','inactive'=>'Inactive','unavailable'=>'Unavailable','featured'=>'Featured'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select>
<button class="btn" type="submit"><i data-lucide="search"></i> Filter</button>
@if(request()->hasAny(['search','category','status']))<a class="btn" href="{{ route('admin.products.index') }}">Clear</a>@endif
</form></div>
<div class="table-scroll"><table><thead><tr><th>PRODUCT</th><th>SKU</th><th>CATEGORY</th><th>PRICE</th><th>AVAILABILITY</th><th>STATUS</th><th>FEATURED</th><th>ACTIONS</th></tr></thead><tbody>
@forelse($products as $product)
<tr>
<td><div class="product-cell">@if($photoUrl($product->image))<img class="product-photo" src="{{ $photoUrl($product->image) }}" alt="{{ $product->name }}" loading="lazy">@else<span class="product-photo photo-empty"><i data-lucide="coffee"></i></span>@endif<div><strong>{{ $product->name }}</strong><small>{{ Str::limit($product->description ?? 'Freshly made',42) }}</small></div></div></td>
<td>{{ $product->sku }}</td><td>{{ $product->category_name }}</td><td><strong>₱{{ number_format((float)$product->base_price,2) }}</strong></td>
<td><span class="status {{ $product->is_available?'good':'bad' }}">{{ $product->is_available?'Available':'Unavailable' }}</span></td>
<td><span class="status {{ $product->is_active?'good':'bad' }}">{{ $product->is_active?'Active':'Inactive' }}</span></td>
<td>@if($product->is_featured)<span class="status gold">★ Featured</span>@else<span class="muted">—</span>@endif</td>
<td><div class="row-actions"><button type="button" class="btn small edit-btn" data-product='@json($product)' title="Edit {{ $product->name }}"><i data-lucide="pencil"></i></button><form method="POST" action="{{ route('admin.products.destroy',$product->id) }}" class="delete-form">@csrf @method('DELETE')<button type="submit" class="btn small danger" title="Archive {{ $product->name }}"><i data-lucide="archive"></i></button></form></div></td>
</tr>
@empty<tr><td colspan="8"><div class="empty"><i data-lucide="coffee"></i><p>No products match your filters. Add a new product to get started.</p></div></td></tr>@endforelse
</tbody></table></div>
<div class="pagination"><span>Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products</span><div class="pages">@if($products->onFirstPage())<span class="btn small" style="opacity:.4">Previous</span>@else<a class="btn small" href="{{ $products->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $products->currentPage() }} / {{ $products->lastPage() }}</span>@if($products->hasMorePages())<a class="btn small" href="{{ $products->nextPageUrl() }}">Next</a>@else<span class="btn small" style="opacity:.4">Next</span>@endif</div></div>
</section>
</main></div>
<div class="modal-backdrop" id="productModal" role="presentation">
<div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
<form method="POST" id="productForm" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
@csrf <input type="hidden" name="_method" id="formMethod" value="POST">
<div class="modal-header"><div><div class="eyebrow">THE BREW BOX · PRODUCT EDITOR</div><h3 id="modalTitle">Add New Product</h3></div><button type="button" class="btn small" id="closeModal" aria-label="Close dialog"><i data-lucide="x"></i></button></div>
<div class="modal-content"><div class="form-grid">
<div class="field"><label for="productName">Product Name *</label><input id="productName" class="input" name="name" maxlength="150" required placeholder="e.g. Spanish Latte"></div>
<div class="field"><label for="productSku">SKU *</label><input id="productSku" class="input" name="sku" maxlength="60" required placeholder="e.g. DRK-001"></div>
<div class="field"><label for="productCategory">Category *</label><select id="productCategory" class="select" name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label for="productPrice">Base Price (₱) *</label><input id="productPrice" class="input" type="number" name="base_price" step="0.01" min="0" max="9999999999" required placeholder="150.00"></div>
<div class="field"><label for="productPrep">Preparation Time (minutes) *</label><input id="productPrep" class="input" type="number" name="preparation_minutes" min="0" max="65535" value="5" required></div>
<div class="field"><label for="productImage">Product Photo (JPG, PNG, WebP; max 4 MB)</label><input id="productImage" class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp"><img id="imagePreview" class="image-preview" alt="Product preview"></div>
<div class="field full"><label for="productDescription">Description</label><textarea id="productDescription" class="textarea" name="description" rows="3" maxlength="5000" placeholder="Describe the flavor and ingredients..."></textarea></div>
<div class="field full"><label>Product Settings</label><div class="check-row"><label><input type="checkbox" name="is_active" value="1" id="isActive" checked> Active</label><label><input type="checkbox" name="is_available" value="1" id="isAvailable" checked> Available</label><label><input type="checkbox" name="is_featured" value="1" id="isFeatured"> Featured</label></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn" id="cancelModal">Cancel</button><button type="submit" class="btn primary"><i data-lucide="save"></i> Save Product</button></div>
</form></div></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
if(window.lucide)lucide.createIcons();
const body=document.body,toggle=document.getElementById('sidebarToggle'),shade=document.getElementById('shade'),modal=document.getElementById('productModal'),form=document.getElementById('productForm');
const mobile=()=>window.matchMedia('(max-width:820px)').matches;
try{if(!mobile()&&localStorage.getItem('brew_admin_sidebar_collapsed')==='1')body.classList.add('sidebar-collapsed')}catch(e){}
toggle.addEventListener('click',()=>{if(mobile()){const open=body.classList.toggle('mobile-open');toggle.setAttribute('aria-expanded',String(open))}else{const collapsed=body.classList.toggle('sidebar-collapsed');toggle.setAttribute('aria-expanded',String(!collapsed));try{localStorage.setItem('brew_admin_sidebar_collapsed',collapsed?'1':'0')}catch(e){}}});
shade.addEventListener('click',()=>body.classList.remove('mobile-open'));
const openModal=()=>{modal.classList.add('open');body.style.overflow='hidden';document.getElementById('productName').focus()};
const closeModal=()=>{modal.classList.remove('open');body.style.overflow=''};
const fields={name:'productName',sku:'productSku',category_id:'productCategory',base_price:'productPrice',preparation_minutes:'productPrep',description:'productDescription'};
document.getElementById('createBtn').addEventListener('click',()=>{form.reset();form.action=@json(route('admin.products.store'));document.getElementById('formMethod').value='POST';document.getElementById('modalTitle').textContent='Add New Product';document.getElementById('imagePreview').style.display='none';openModal()});
document.querySelectorAll('.edit-btn').forEach(btn=>btn.addEventListener('click',()=>{const p=JSON.parse(btn.dataset.product);form.reset();Object.entries(fields).forEach(([key,id])=>document.getElementById(id).value=p[key]??'');document.getElementById('isActive').checked=!!p.is_active;document.getElementById('isAvailable').checked=!!p.is_available;document.getElementById('isFeatured').checked=!!p.is_featured;document.getElementById('formMethod').value='PUT';form.action=@json(route('admin.products.index'))+'/'+p.id;document.getElementById('modalTitle').textContent='Edit '+p.name;const preview=document.getElementById('imagePreview');preview.style.display='none';openModal()}));
document.getElementById('productImage').addEventListener('change',e=>{const preview=document.getElementById('imagePreview');const file=e.target.files[0];if(!file){preview.style.display='none';return}if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>4*1024*1024){alert('Choose a JPG, PNG or WebP image under 4 MB.');e.target.value='';return}preview.src=URL.createObjectURL(file);preview.style.display='block';preview.onload=()=>URL.revokeObjectURL(preview.src)});
['closeModal','cancelModal'].forEach(id=>document.getElementById(id).addEventListener('click',closeModal));
modal.addEventListener('click',e=>{if(e.target===modal)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();body.classList.remove('mobile-open')}});
document.querySelectorAll('.delete-form').forEach(f=>f.addEventListener('submit',e=>{if(!confirm('Archive this product? It will no longer appear on the customer menu.'))e.preventDefault()}));
@if($errors->any())
document.getElementById('createBtn').click();
@php
$oldFieldIds = ['name'=>'productName', 'sku'=>'productSku', 'category_id'=>'productCategory', 'base_price'=>'productPrice', 'preparation_minutes'=>'productPrep', 'description'=>'productDescription'];
@endphp
@foreach($oldFieldIds as $field => $elementId)
@if(old($field) !== null)
document.getElementById(@json($elementId)).value = @json(old($field));
@endif
@endforeach
@if(old('is_active') !== null)
document.getElementById('isActive').checked = true;
@endif
@if(old('is_available') !== null)
document.getElementById('isAvailable').checked = true;
@endif
@if(old('is_featured') !== null)
document.getElementById('isFeatured').checked = true;
@endif
@endif
});
</script>
</body></html>
