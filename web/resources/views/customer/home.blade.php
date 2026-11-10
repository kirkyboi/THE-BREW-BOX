
@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Str;

    $products = collect();
    $announcements = collect();

    try {
        if (Schema::hasTable('products') && Schema::hasTable('categories')) {
            $products = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.description',
                    'products.base_price',
                    'products.image',
                    'categories.name as category_name'
                )
                ->where('products.is_active', 1)
                ->where('products.is_available', 1)
                ->where('categories.is_active', 1)
                ->whereNull('products.deleted_at')
                ->orderByDesc('products.is_featured')
                ->limit(12)
                ->get();
        }
    } catch (\Throwable $e) {
        report($e);
    }

    try {
        if (Schema::hasTable('announcements')) {
            $announcements = DB::table('announcements')
                ->where('status', 'published')
                ->whereIn('target_audience', ['all', 'customers'])
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('is_pinned')
                ->limit(6)
                ->get();
        }
    } catch (\Throwable $e) {
        report($e);
    }

    $samples = collect([
        (object)[
            'id' => null,
            'name' => 'Caramel Macchiato',
            'description' => 'Velvety espresso, steamed milk and golden caramel.',
            'base_price' => 140,
            'category_name' => 'Coffee',
            'image' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=800&q=85'
        ],
        (object)[
            'id' => null,
            'name' => 'Spanish Latte',
            'description' => 'A rich, creamy coffee with a sweet finish.',
            'base_price' => 150,
            'category_name' => 'Coffee',
            'image' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=800&q=85'
        ],
        (object)[
            'id' => null,
            'name' => 'Matcha Latte',
            'description' => 'Smooth, earthy matcha blended to perfection.',
            'base_price' => 160,
            'category_name' => 'Non-Coffee',
            'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?w=800&q=85'
        ],
        (object)[
            'id' => null,
            'name' => 'Chocolate Dream',
            'description' => 'A decadent chocolate drink for sweet moments.',
            'base_price' => 150,
            'category_name' => 'Non-Coffee',
            'image' => 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?w=800&q=85'
        ],
        (object)[
            'id' => null,
            'name' => 'Butter Croissant',
            'description' => 'Golden, flaky and freshly baked.',
            'base_price' => 85,
            'category_name' => 'Pastries',
            'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=800&q=85'
        ]
    ]);

    $displayProducts = $products->isNotEmpty() ? $products : $samples;

    $imageUrl = function ($path, $fallback) {
        if (!$path) {
            return $fallback;
        }

        if (Str::startsWith($path, ['https://', 'http://', '//'])) {
            return $path;
        }

        return asset(
            Str::startsWith($path, 'storage/')
                ? $path
                : 'storage/' . ltrim($path, '/')
        );
    };

    // Announcement images may be saved as relative public-disk paths or full URLs.
    $announcementImage = function ($announcement) use ($imageUrl) {
        $raw = $announcement->image ?? $announcement->image_path ?? $announcement->featured_image ?? $announcement->thumbnail ?? null;
        if (!is_string($raw) || trim($raw) === '') return null;
        $raw = trim($raw);
        if (Str::startsWith($raw, ['https://', 'http://', '//'])) return $raw;
        if (Str::startsWith($raw, ['/storage/', '/uploads/', '/images/'])) return asset(ltrim($raw, '/'));
        if (Str::startsWith($raw, ['storage/', 'uploads/', 'images/'])) return asset($raw);
        if (Str::startsWith($raw, 'public/')) return asset('storage/' . Str::after($raw, 'public/'));
        return $imageUrl($raw, null);
    };

    // Hero carousel uses real available drinks when present; curated previews fill missing themes.
    $heroDefaults = collect([
        (object) ['id'=>null,'name'=>'Caramel Macchiato','description'=>'Silky espresso, steamed milk and golden caramel.','base_price'=>140,'category_name'=>'Coffee','image'=>'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=1100&q=90'],
        (object) ['id'=>null,'name'=>'Matcha Latte','description'=>'Earthy green matcha, whisked smooth with creamy milk.','base_price'=>160,'category_name'=>'Non-Coffee','image'=>'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?w=1100&q=90'],
        (object) ['id'=>null,'name'=>'Mocha','description'=>'Deep chocolate and bold espresso in perfect harmony.','base_price'=>155,'category_name'=>'Coffee','image'=>'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?w=1100&q=90'],
        (object) ['id'=>null,'name'=>'Spanish Latte','description'=>'A creamy espresso treat with a beautifully sweet finish.','base_price'=>150,'category_name'=>'Coffee','image'=>'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=1100&q=90'],
    ]);
    $heroProducts = collect();
    foreach ($heroDefaults as $default) {
        $keyword = Str::lower($default->name);
        $match = $products->first(function ($item) use ($keyword) {
            return Str::contains(Str::lower($item->name), $keyword);
        });
        $heroProducts->push($match ?: $default);
    }
    $heroThemes = ['caramel','matcha','mocha','spanish'];

    $cart = session('cart', []);
    $cartCount = 0;

    if (is_array($cart)) {
        foreach ($cart as $item) {
            if (is_array($item)) {
                $cartCount += (int)($item['quantity'] ?? 0);
            }
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#201008">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>THE BREW BOX | Crafted With Passion</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Great+Vibes&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>

    <style>
        :root {
            --espresso: #1b0d07;
            --dark: #28140b;
            --brown: #4b2919;
            --gold: #dca967;
            --gold-light: #f6d5a0;
            --cream: #f8f1e7;
            --white: #fffdf9;
            --ink: #2a1910;
            --muted: #8b7769;
            --border: #e8d9c6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 95px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button, input {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .container {
            width: min(1220px, calc(100% - 48px));
            margin: auto;
        }

        .serif {
            font-family: 'Playfair Display', serif;
        }

        .script {
            font-family: 'Great Vibes', cursive;
        }

        .gold-text {
            color: var(--gold);
        }

        /* NAVIGATION */

        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255,255,255,.12);
            color: white;
        }

        .navbar {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            flex-shrink: 0;
        }

        .brand-logo {
            width: 62px;
            height: 62px;
            border: 1.5px solid var(--gold);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: var(--gold-light);
            font-size: 27px;
            background: rgba(255,255,255,.04);
        }

        .brand-text strong {
            display: block;
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            letter-spacing: -.7px;
        }

        .brand-text small {
            color: var(--gold-light);
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 29px;
        }

        .nav-links a {
            font-size: 12px;
            font-weight: 700;
            color: #f4e9dc;
            transition: .2s;
        }

        .nav-links a:hover {
            color: var(--gold);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .icon-button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -10px;
            right: -12px;
            background: var(--gold);
            color: var(--espresso);
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: grid;
            place-items: center;
            font-size: 10px;
            font-weight: 800;
        }

        .auth-button {
            padding: 13px 20px;
            border-radius: 7px;
            border: 1px solid var(--gold);
            background: var(--gold);
            color: var(--espresso);
            font-size: 12px;
            font-weight: 800;
            transition: .25s;
            white-space: nowrap;
        }

        .auth-button:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
        }

        .logout-form { margin: 0; }
        .logout-form .auth-button { cursor: pointer; }
        .mobile-auth-link { display: none; }
        .mobile-auth-link button {
            border: 0;
            background: transparent;
            color: #f4e9dc;
            font-size: 12px;
            font-weight: 700;
            padding: 10px;
            text-align: left;
        }

        .mobile-toggle {
            display: none;
            border: 0;
            background: none;
            color: white;
            font-size: 23px;
        }

        /* HERO */

        .hero {
            min-height: 760px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            color: white;
            background:
                linear-gradient(90deg,
                    rgba(20,8,3,.96) 0%,
                    rgba(27,12,5,.88) 37%,
                    rgba(27,12,5,.42) 75%,
                    rgba(20,8,3,.35) 100%),
                url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=2000&q=90')
                center / cover no-repeat;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .24;
            pointer-events: none;
            background: radial-gradient(circle at 75% 40%, #e0a15c, transparent 42%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding-top: 140px;
            padding-bottom: 115px;
        }

        .hero-eyebrow {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--gold-light);
            text-transform: uppercase;
            letter-spacing: 4px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 25px;
        }

        .hero-eyebrow::before {
            content: "";
            width: 45px;
            height: 1px;
            background: var(--gold);
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(56px, 7.5vw, 105px);
            letter-spacing: -4px;
            line-height: 1.07;
            max-width: 800px;
        }

        .hero h1 span {
            color: var(--gold-light);
        }

        .hero-script {
            font-size: clamp(36px, 4.5vw, 66px);
            color: #f4d7b1;
            margin: 13px 0 20px;
        }

        .hero-description {
            max-width: 490px;
            font-size: 15px;
            line-height: 1.95;
            color: #e3d4c7;
            margin-bottom: 32px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            padding: 16px 27px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 800;
            transition: .25s;
            border: 1px solid transparent;
        }

        .btn:hover {
            transform: translateY(-3px);
        }

        .btn-gold {
            background: linear-gradient(135deg, #f6d6a5, #dca767);
            color: #241208;
            box-shadow: 0 12px 30px rgba(0,0,0,.14);
        }

        .btn-outline {
            border-color: rgba(245,213,174,.7);
            color: #fff4e8;
            background: rgba(255,255,255,.04);
        }

        .hero-side {
            position: absolute;
            right: 6%;
            bottom: 130px;
            z-index: 2;
            font-size: 53px;
            color: var(--gold-light);
            text-align: center;
            transform: rotate(-8deg);
            text-shadow: 0 5px 20px #160900;
        }

        .hero-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 55px;
            background: var(--cream);
            clip-path: polygon(
                0 60%, 12% 85%, 28% 45%, 44% 75%,
                60% 40%, 77% 70%, 90% 42%,
                100% 65%, 100% 100%, 0 100%
            );
        }

        /* SHARED SECTIONS */

        .section {
            padding: 95px 0;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 39px;
        }

        .section-kicker {
            color: #aa7444;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 3px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(34px, 4vw, 49px);
            letter-spacing: -1.5px;
            margin-bottom: 12px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
        }

        /* MENU */

        .menu-section {
            background:
                radial-gradient(circle at 0 30%, #efddc7, transparent 25%),
                var(--cream);
        }

        .menu-filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 34px;
        }

        .filter-btn {
            padding: 12px 21px;
            border-radius: 7px;
            border: 1px solid var(--border);
            background: var(--white);
            color: #5e4434;
            font-size: 12px;
            font-weight: 800;
            transition: .2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--dark);
            border-color: var(--dark);
            color: var(--gold-light);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 23px;
        }

        .product-card {
            background: #fffdf9;
            border: 1px solid #ead9c5;
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(51,25,9,.06);
            transition: .3s;
        }

        .product-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 40px rgba(51,25,9,.13);
        }

        .product-image {
            position: relative;
            height: 230px;
            overflow: hidden;
            background: #e9d6bf;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .5s;
        }

        .product-card:hover img {
            transform: scale(1.07);
        }

        .product-tag {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 8px 11px;
            background: #fff8eb;
            color: #744526;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 800;
        }

        .product-info {
            padding: 21px;
        }

        .product-info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            margin-bottom: 9px;
        }

        .product-info p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
            min-height: 43px;
        }

        .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 18px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 800;
            color: var(--brown);
        }

        .add-cart {
            border: none;
            background: var(--dark);
            color: white;
            border-radius: 7px;
            padding: 12px 14px;
            font-size: 11px;
            font-weight: 800;
            transition: .2s;
        }

        .add-cart:hover {
            background: #75472b;
        }

        .empty-products {
            display: none;
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        /* ANNOUNCEMENTS */

        .announcements-section {
            background: #fffaf4;
        }

        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .announcement-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            padding: 26px;
            box-shadow: 0 8px 24px rgba(0,0,0,.04);
        }

        .announcement-card .type {
            color: #a27041;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
        }

        .announcement-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            margin: 13px 0;
        }

        .announcement-card p {
            font-size: 13px;
            line-height: 1.8;
            color: var(--muted);
        }

        /* ANNOUNCEMENT PHOTOGRAPHY & BOTANICAL DECOR */
        .announcements-section, .menu-section, .gallery-section { position: relative; overflow: hidden; }
        .announcements-section { background: linear-gradient(155deg,#fffaf2,#f4e7d6); }
        .announcement-card { padding: 0; display: flex; flex-direction: column; transition: transform .3s,box-shadow .3s; position: relative; z-index: 1; }
        .announcement-card:hover { transform: translateY(-7px); box-shadow: 0 22px 45px rgba(51,25,9,.13); }
        .announcement-image { position: relative; height: 215px; overflow: hidden; background: linear-gradient(135deg,#55321d,#b88652); }
        .announcement-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
        .announcement-card:hover .announcement-image img { transform: scale(1.06); }
        .announcement-image-fallback { height: 100%; display: grid; place-items: center; color: #f4d6a4; font-size: 65px; background: radial-gradient(circle at 70% 30%,#936443,#3a1c10); }
        .announcement-body { padding: 25px; display: flex; flex-direction: column; flex: 1; }
        .announcement-card .type { display: inline-flex; align-items: center; gap: 8px; }
        .announcement-meta { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; margin-top: auto; padding-top: 22px; font-size: 11px; color: #92775f; }
        .announcement-pin { color: #9d6130; font-weight: 800; }
        .botanical { position: absolute; pointer-events: none; z-index: 0; opacity: .3; width: 185px; height: 240px; }
        .botanical.left { left: -65px; top: 20px; transform: rotate(-23deg); }
        .botanical.right { right: -70px; bottom: 10px; transform: rotate(158deg); }
        .botanical svg { width: 100%; height: 100%; }
        .bean-decor { position: absolute; pointer-events: none; color: #996c42; opacity: .27; font-size: 33px; z-index: 0; }
        .bean-decor.one { left: 3%; bottom: 30px; transform: rotate(-35deg); }
        .bean-decor.two { right: 5%; top: 40px; transform: rotate(27deg); }
        .announcements-section .container, .menu-section .container { position: relative; z-index: 1; }
        .payment-methods { margin-top: 24px; padding-top: 20px; border-top: 1px solid #4c3020; }
        .payment-methods h4 { margin-bottom: 13px; }
        .payment-badges { display: flex; flex-wrap: wrap; gap: 8px; }
        .payment-badge { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 12px; border: 1px solid #755039; border-radius: 7px; background: #2c180f; font-size: 12px; font-weight: 800; color: #f7e4cb; }
        .payment-badge.gcash { color: #77b4ff; }
        .payment-badge.maya { color: #a2f0a4; }
        .payment-badge.visa { color: #a6c7ff; }
        @media(max-width:600px) { .announcement-image { height: 205px; } .botanical { opacity: .17; } }

        /* ABOUT */

        .about-section {
            background: var(--espresso);
            color: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 520px;
        }

        .about-image {
            background:
                url('https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=1200&q=90')
                center / cover no-repeat;
            min-height: 420px;
        }

        .about-content {
            padding: clamp(45px, 6vw, 95px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background:
                radial-gradient(circle at 100% 0%, #52301d, transparent 60%),
                var(--espresso);
        }

        .about-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(33px, 4vw, 54px);
            line-height: 1.25;
            margin: 15px 0 24px;
        }

        .about-content > p {
            color: #ddc9b8;
            font-size: 14px;
            line-height: 2;
            max-width: 530px;
            margin-bottom: 27px;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 28px;
            border-top: 1px solid #67432c;
            padding-top: 25px;
        }

        .about-feature i {
            color: var(--gold);
            font-size: 25px;
            margin-bottom: 11px;
        }

        .about-feature strong {
            display: block;
            font-size: 12px;
        }

        .about-feature small {
            display: block;
            margin-top: 7px;
            color: #cdb6a4;
            font-size: 11px;
            line-height: 1.5;
        }

        /* WHY CHOOSE US */

        .why-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .why-card {
            background: #fffaf3;
            padding: 35px 24px;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .why-icon {
            width: 72px;
            height: 72px;
            border: 1px solid #e6c5a1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 22px;
            color: #7d4b2a;
            font-size: 28px;
        }

        .why-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .why-card p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        /* TESTIMONIAL */

        .testimonial-section {
            background: #eaddcd;
            padding: 75px 0;
            text-align: center;
        }

        .testimonial-box {
            max-width: 730px;
            margin: auto;
        }

        .testimonial-stars {
            color: #ad7738;
            font-size: 22px;
            letter-spacing: 5px;
            margin: 20px 0;
        }

        .testimonial-box blockquote {
            font-family: 'Playfair Display', serif;
            font-size: clamp(20px, 3vw, 31px);
            line-height: 1.6;
        }

        .testimonial-box .credit {
            margin-top: 23px;
            color: #80634e;
            font-size: 12px;
        }

        /* GALLERY */

        .gallery-section {
            background: #fffaf3;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
        }

        .gallery-grid img {
            width: 100%;
            height: 265px;
            object-fit: cover;
            border-radius: 10px;
            transition: .3s;
        }

        .gallery-grid img:hover {
            transform: translateY(-5px);
        }

        /* NEWSLETTER */

        .newsletter-section {
            background: #352013;
            color: white;
            padding: 65px 0;
        }

        .newsletter-layout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 35px;
        }

        .newsletter-layout h2 {
            font-family: 'Playfair Display', serif;
            font-size: 35px;
            margin-bottom: 10px;
        }

        .newsletter-layout p {
            color: #d4bda9;
            font-size: 13px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
            width: min(100%, 450px);
        }

        .newsletter-form input {
            flex: 1;
            min-width: 0;
            padding: 16px;
            border-radius: 7px;
            border: 1px solid #a88b70;
            outline: none;
        }

        .newsletter-form button {
            border: none;
            border-radius: 7px;
            padding: 14px 22px;
            background: var(--gold);
            color: var(--espresso);
            font-weight: 800;
        }

        /* FOOTER */

        .footer {
            background: #190c06;
            color: white;
            padding-top: 65px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1.4fr;
            gap: 40px;
            padding-bottom: 50px;
        }

        .footer h4 {
            font-size: 13px;
            margin-bottom: 20px;
            color: var(--gold-light);
        }

        .footer p,
        .footer a {
            font-size: 12px;
            line-height: 1.9;
            color: #d4bfae;
        }

        .footer-column a {
            display: block;
            margin-bottom: 9px;
        }

        .footer-column a:hover {
            color: var(--gold);
        }

        .footer-description {
            margin-top: 19px;
            max-width: 320px;
        }

        .footer-socials {
            display: flex;
            gap: 17px;
            margin-top: 22px;
            font-size: 19px;
        }

        .footer-bottom {
            border-top: 1px solid #4c3020;
            padding: 24px 0;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            font-size: 11px;
            color: #bca28d;
        }

        /* NOTIFICATIONS */

        .toast {
            position: fixed;
            right: 25px;
            bottom: 25px;
            z-index: 1000;
            max-width: 360px;
            padding: 16px 22px;
            background: var(--dark);
            color: white;
            border: 1px solid var(--gold);
            border-radius: 9px;
            box-shadow: 0 15px 40px rgba(0,0,0,.25);
            font-size: 13px;
            display: none;
        }

        .toast.show {
            display: block;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .nav-links {
                gap: 17px;
            }

            .products-grid,
            .why-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .about-section {
                grid-template-columns: 45% 55%;
            }

            .about-content {
                padding: 40px;
            }
        }

        @media (max-width: 850px) {
            .mobile-toggle {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 88px;
                left: 20px;
                right: 20px;
                padding: 23px;
                background: #27150b;
                border: 1px solid #805432;
                border-radius: 12px;
                flex-direction: column;
                align-items: stretch;
                box-shadow: 0 20px 40px rgba(0,0,0,.3);
            }

            .nav-links.open {
                display: flex;
            }
            .mobile-auth-link { display: block; }

            .nav-links a {
                padding: 10px;
            }

            .hero-side {
                display: none;
            }

            .about-section {
                grid-template-columns: 1fr;
            }

            .about-image {
                min-height: 340px;
            }

            .announcement-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .newsletter-layout {
                flex-direction: column;
                align-items: stretch;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .container {
                width: calc(100% - 30px);
            }

            .navbar {
                min-height: 82px;
            }

            .brand-logo {
                width: 47px;
                height: 47px;
                font-size: 22px;
            }

            .brand-text strong {
                font-size: 17px;
            }

            .brand-text small {
                font-size: 8px;
            }

            .nav-actions {
                gap: 14px;
            }

            .auth-button {
                display: none;
            }

            .hero {
                min-height: 680px;
            }

            .hero-content {
                padding-top: 140px;
            }

            .hero h1 {
                font-size: 56px;
                letter-spacing: -2px;
            }

            .hero-script {
                font-size: 40px;
            }

            .hero-description {
                font-size: 13px;
            }

            .section {
                padding: 65px 0;
            }

            .products-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .product-image {
                height: 145px;
            }

            .product-info {
                padding: 13px;
            }

            .product-info h3 {
                font-size: 15px;
            }

            .product-bottom {
                flex-direction: column;
                align-items: stretch;
            }

            .add-cart {
                width: 100%;
            }

            .announcement-grid,
            .why-grid {
                grid-template-columns: 1fr;
            }

            .about-content {
                padding: 38px 24px;
            }

            .about-features {
                grid-template-columns: 1fr;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .gallery-grid img {
                height: 175px;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }

            .footer-brand {
                grid-column: 1 / -1;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

/* THE BREW BOX / PREMIUM INTERACTIVE PRODUCT EXPERIENCE */
.hero-experience{--hero-accent:#e9ae6a;--hero-deep:#190c08;--hero-glow:#9c4b23;--hero-text:#fff2db;min-height:790px;isolation:isolate;background:var(--hero-deep);transition:background-color .9s ease,color .9s ease;overflow:hidden}
.hero-experience.theme-caramel{--hero-accent:#e9ae6a;--hero-deep:#1a0d08;--hero-glow:#a35325;--hero-text:#fff1d9}
.hero-experience.theme-matcha{--hero-accent:#c9df8a;--hero-deep:#12251a;--hero-glow:#6d9250;--hero-text:#f2ffdc}
.hero-experience.theme-mocha{--hero-accent:#e4a98d;--hero-deep:#23100f;--hero-glow:#9e4b43;--hero-text:#ffe9e0}
.hero-experience.theme-spanish{--hero-accent:#ffe0a0;--hero-deep:#2d190f;--hero-glow:#bd8743;--hero-text:#fff0ce}
.hero-experience::before{display:none}.hero-atmosphere{position:absolute;inset:0;background:radial-gradient(ellipse at 76% 43%,var(--hero-glow),transparent 43%),radial-gradient(ellipse at 0 100%,#6e3a1c50,transparent 42%);opacity:.65;transition:background 1s ease;z-index:-2}.hero-grain{position:absolute;inset:0;opacity:.13;pointer-events:none;background-image:url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1600&q=65');background-position:center;background-size:cover;mix-blend-mode:soft-light;z-index:-1}
.hero-experience-inner{min-height:790px;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:25px;padding-top:140px;padding-bottom:95px;position:relative;z-index:2}
.hero-story{padding:20px 0 40px}.hero-overline{display:flex;align-items:center;gap:12px;color:var(--hero-accent);font-size:10px;font-weight:800;letter-spacing:2.5px}.hero-overline-line{height:1px;width:37px;background:currentColor}.hero-index{margin:28px 0 19px;color:#ffffff9b;font-size:11px;font-weight:700;letter-spacing:2px}.hero-index span:first-child{font-size:16px;color:var(--hero-accent)}.hero-small-title{color:var(--hero-accent);letter-spacing:2.7px;font-size:11px;font-weight:800;margin-bottom:12px}.hero-experience h1{font-size:clamp(53px,6.2vw,91px);line-height:1.06;letter-spacing:-3px;color:white;margin:0 0 23px;max-width:650px}.hero-experience h1 em{font-style:normal;color:var(--hero-accent);display:block}.hero-experience .hero-description{font-size:15px;line-height:1.85;max-width:440px;color:#f3e5d8c9;margin-bottom:22px;min-height:58px}.hero-price{display:flex;align-items:center;gap:17px;margin-bottom:26px}.hero-price span{font-size:10px;letter-spacing:2px;color:#d7c5b7}.hero-price strong{font-size:25px;color:var(--hero-accent)}.hero-experience .btn-gold{background:var(--hero-accent);color:var(--hero-deep);box-shadow:0 12px 35px #0005}.hero-experience .btn-outline{border-color:#ffffff70}.hero-experience .btn svg{width:17px;height:17px}.hero-trust{display:flex;gap:20px;flex-wrap:wrap;margin-top:35px;color:#d6c9b9;font-size:11px}.hero-trust span{display:flex;align-items:center;gap:7px}.hero-trust svg{width:15px;height:15px;color:var(--hero-accent)}
.hero-showcase{height:565px;position:relative;display:flex;align-items:center;justify-content:center;min-width:0}.hero-image-halo{position:absolute;width:min(95%,520px);aspect-ratio:1;border-radius:50%;background:radial-gradient(circle,var(--hero-glow) 0%,transparent 68%);filter:blur(8px);opacity:.8}.hero-image-ring{position:absolute;width:min(91%,490px);aspect-ratio:1;border:1px solid color-mix(in srgb,var(--hero-accent) 48%,transparent);border-radius:50%;box-shadow:0 0 0 29px #ffffff06,0 0 0 62px #ffffff04}.heroSwiper{width:100%;height:490px;z-index:2}.hero-slide{display:flex;align-items:center;justify-content:center}.hero-product-visual{width:min(80%,420px);height:425px;border-radius:50% 50% 44% 44%;padding:10px;background:linear-gradient(145deg,#ffffff21,#ffffff04);box-shadow:0 25px 85px #0008,0 0 0 1px #ffffff25;overflow:hidden}.hero-product-visual img{width:100%;height:100%;object-fit:cover;border-radius:inherit;filter:drop-shadow(0 20px 25px #0005)}.hero-stamp{position:absolute;right:2%;top:8%;width:102px;height:102px;border:1px solid var(--hero-accent);border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;transform:rotate(13deg);color:var(--hero-accent);background:#180c087a;z-index:4;text-align:center;letter-spacing:1.5px;font-size:9px;font-weight:800}.hero-stamp svg{width:24px;height:24px}.hero-carousel-bottom{position:absolute;bottom:0;left:5%;right:5%;display:flex;align-items:center;justify-content:space-between;gap:15px;z-index:5}.hero-carousel-label{font-size:10px;letter-spacing:2px;color:#ffffffa6;font-weight:800}.hero-carousel-controls{display:flex;align-items:center;gap:16px}.hero-arrow{width:43px;height:43px;border-radius:50%;display:grid;place-items:center;border:1px solid var(--hero-accent);color:var(--hero-accent);background:#160b0870;transition:.2s}.hero-arrow:hover{background:var(--hero-accent);color:var(--hero-deep)}.hero-arrow svg{width:18px;height:18px}.hero-pagination{display:flex;gap:7px;align-items:center}.hero-pagination .swiper-pagination-bullet{background:#fff8;opacity:1;margin:0!important;width:7px;height:7px}.hero-pagination .swiper-pagination-bullet-active{background:var(--hero-accent);width:25px;border-radius:10px}.hero-leaf{position:absolute;color:var(--hero-accent);opacity:.18;z-index:1;pointer-events:none}.hero-leaf svg{width:120px;height:120px;stroke-width:.65}.hero-leaf-one{top:13%;right:2%;transform:rotate(-34deg)}.hero-leaf-two{bottom:8%;left:-25px;transform:rotate(130deg)}.hero-bean{position:absolute;width:33px;height:47px;border:2px solid var(--hero-accent);border-radius:50%;opacity:.18;transform:rotate(32deg);pointer-events:none}.hero-bean::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;border-left:2px solid var(--hero-accent);border-radius:50%;transform:rotate(20deg)}.hero-bean-one{right:11%;top:20%}.hero-bean-two{left:47%;bottom:16%;transform:rotate(-30deg)}
.hero-experience .hero-bottom{background:var(--cream)}
@media(max-width:1050px){.hero-experience-inner{grid-template-columns:1fr 1fr;gap:8px}.hero-experience h1{font-size:clamp(49px,6vw,72px)}.hero-showcase{height:500px}.heroSwiper{height:440px}.hero-product-visual{height:365px;width:min(85%,365px)}.hero-stamp{width:80px;height:80px;right:0}}
@media(max-width:800px){.hero-experience-inner{grid-template-columns:1fr;min-height:auto;padding-top:155px;padding-bottom:95px}.hero-story{padding:0}.hero-showcase{height:430px}.heroSwiper{height:375px}.hero-product-visual{height:325px;width:min(74%,340px)}.hero-stamp{top:4%;right:12%}.hero-experience h1{font-size:clamp(50px,10vw,75px)}.hero-experience .hero-description{min-height:0}.hero-carousel-bottom{bottom:0}}
@media(max-width:540px){.hero-experience-inner{padding-top:133px;padding-bottom:80px}.hero-overline{font-size:9px;letter-spacing:1.5px}.hero-index{margin:18px 0}.hero-experience h1{font-size:clamp(46px,12vw,65px);letter-spacing:-2px}.hero-experience .hero-description{font-size:13px}.hero-experience .btn{padding:13px 16px;font-size:11px}.hero-trust{margin-top:22px}.hero-showcase{height:350px}.heroSwiper{height:315px}.hero-product-visual{height:265px;width:min(75%,280px)}.hero-stamp{width:66px;height:66px;font-size:7px;right:4%}.hero-stamp svg{width:18px;height:18px}.hero-carousel-label{font-size:8px}.hero-arrow{height:36px;width:36px}.hero-carousel-controls{gap:9px}}
@media(prefers-reduced-motion:reduce){.hero-experience,.hero-atmosphere,.hero-arrow{transition:none!important}}


.product-card{position:relative}
/* BREW BOX CINEMATIC LUXURY — layered liquid, depth and scroll reveals */
.hero-experience{--liquid-a:#f6cc86;--liquid-b:#b16b38;--particle:#c48b4c;perspective:1100px}
.hero-experience.theme-matcha{--liquid-a:#b5d884;--liquid-b:#477b4b;--particle:#a5d67a}
.hero-experience.theme-mocha{--liquid-a:#d69b86;--liquid-b:#63372d;--particle:#ba7666}
.hero-experience.theme-spanish{--liquid-a:#ffe2a4;--liquid-b:#c48b4c;--particle:#e8bd7a}
.hero-showcase{perspective:1100px;transform-style:preserve-3d;contain:layout style}
.hero-image-halo{transform:translateZ(-45px);transition:background .75s ease}
.hero-image-ring{transform:translateZ(-18px)}
.hero-product-visual{position:relative;z-index:3;will-change:transform;transform-style:preserve-3d;backface-visibility:hidden;transition:box-shadow .45s ease}
.hero-product-visual img{position:relative;z-index:3;transform:translateZ(26px);backface-visibility:hidden}
.hero-slide{perspective:950px;overflow:visible!important}
.hero-liquid-layer{position:absolute;inset:4% 0 0;z-index:1;pointer-events:none;overflow:visible;filter:drop-shadow(0 12px 30px #0005)}
.hero-liquid-layer svg{width:100%;height:100%;overflow:visible;transform-origin:50% 50%;opacity:.82}
.hero-liquid-layer .liquid-outer{fill:none;stroke:var(--liquid-a);stroke-width:11;stroke-linecap:round;opacity:.5}
.hero-liquid-layer .liquid-inner{fill:none;stroke:var(--liquid-b);stroke-width:17;stroke-linecap:round;opacity:.46}
.hero-liquid-layer .liquid-drops{fill:var(--liquid-a);opacity:.65}
.hero-liquid-layer .liquid-highlight{fill:none;stroke:#fff3d6;stroke-width:2;opacity:.35}
.hero-ambient-particles{position:absolute;inset:0;z-index:1;pointer-events:none;overflow:hidden}
.hero-particle{position:absolute;width:var(--size,8px);height:var(--size,8px);border-radius:50%;background:var(--particle);opacity:var(--opacity,.36);filter:blur(var(--blur,0px));box-shadow:0 0 16px var(--particle);will-change:transform}
.hero-floor-shadow{position:absolute;left:23%;right:23%;bottom:10%;height:35px;border-radius:50%;background:#000;filter:blur(24px);opacity:.55;z-index:1;pointer-events:none}
.hero-bean{z-index:2}
.hero-leaf{z-index:2}
.hero-visual-label{position:absolute;z-index:4;left:7%;bottom:13%;color:#f9e6caad;font-size:9px;letter-spacing:3px;writing-mode:vertical-rl;transform:rotate(180deg);pointer-events:none}
.product-card{transform-style:preserve-3d;perspective:900px;will-change:auto;transition:box-shadow .3s ease,border-color .3s ease}
.product-card .product-image{transform:translateZ(12px);backface-visibility:hidden}
.product-card .product-info{transform:translateZ(7px)}
.product-card.is-tilting{will-change:transform;border-color:#caa06b;box-shadow:0 22px 52px #45220b30}
.product-card .product-shine{position:absolute;inset:0;z-index:4;pointer-events:none;border-radius:inherit;opacity:0;background:radial-gradient(circle at var(--shine-x,50%) var(--shine-y,50%),#fff9da69,transparent 48%);transition:opacity .2s}
.product-card.is-tilting .product-shine{opacity:.5}
.cinema-reveal{will-change:transform,opacity}
.cinema-accent-line{width:50px;height:2px;background:linear-gradient(90deg,var(--gold),transparent);margin:18px 0 0}
@media(hover:none),(pointer:coarse){.product-card,.hero-product-visual{will-change:auto}.product-card:hover{transform:none}.product-card .product-image,.product-card .product-info{transform:none}}
@media(max-width:800px){.hero-liquid-layer{inset:8% 1%}.hero-liquid-layer svg{opacity:.55}.hero-visual-label{display:none}.hero-ambient-particles{opacity:.5}.hero-floor-shadow{bottom:8%}}
@media(max-width:540px){.hero-liquid-layer{inset:12% 4%}.hero-liquid-layer svg{opacity:.42}.hero-floor-shadow{bottom:10%;height:19px;filter:blur(15px)}.hero-ambient-particles{opacity:.35}}
@media(prefers-reduced-motion:reduce){.hero-product-visual,.hero-liquid-layer svg,.hero-particle,.cinema-reveal,.product-card{animation:none!important;transition:none!important;transform:none!important}.hero-ambient-particles{display:none}}

    </style>
</head>

<body>

<!-- HEADER -->
<header class="header">
    <div class="container navbar">

        <a href="#home" class="brand">
            <div class="brand-logo">
                <i class="fa-solid fa-mug-hot"></i>
            </div>

            <div class="brand-text">
                <strong>THE BREW BOX</strong>
                <small>Hand Made Coffee</small>
            </div>
        </a>

        <nav class="nav-links" id="mainNav">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#menu">Menu</a>
            <a href="#gallery">Gallery</a>
            <a href="#announcements">Updates</a>
            <a href="#contact">Contact</a>
            <span class="mobile-auth-link">
              @guest
    <a href="{{ route('login') }}" class="auth-button">
        <i class="fa-solid fa-right-to-bracket"></i>
        Login / Register
    </a>
@else
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="auth-button">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </button>
    </form>
@endguest
            </span>
        </nav>

        <div class="nav-actions">

            <button type="button"
                    class="icon-button"
                    id="searchButton"
                    aria-label="Search products">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <a href="#menu"
               class="icon-button"
               aria-label="Shopping cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-count">{{ $cartCount }}</span>
            </a>

            @guest
                <a href="{{ route('login') }}" class="auth-button">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login / Register
                </a>
            @else
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="auth-button">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </form>
            @endguest

            <button type="button"
                    class="mobile-toggle"
                    id="menuToggle"
                    aria-label="Toggle navigation"
                    aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>

        </div>
    </div>
</header>

<!-- INTERACTIVE SIGNATURE DRINK HERO -->
<section class="hero hero-experience theme-caramel" id="home" aria-label="Discover our signature drinks">
    <div class="hero-atmosphere" aria-hidden="true"></div>
    <div class="hero-grain" aria-hidden="true"></div>
    <div class="hero-leaf hero-leaf-one" aria-hidden="true"><i data-lucide="leaf"></i></div>
    <div class="hero-leaf hero-leaf-two" aria-hidden="true"><i data-lucide="leaf"></i></div>
    <div class="hero-bean hero-bean-one" aria-hidden="true"></div><div class="hero-bean hero-bean-two" aria-hidden="true"></div>
    <div class="container hero-experience-inner">
        <div class="hero-story">
            <div class="hero-overline"><span class="hero-overline-line"></span> THE BREW BOX · SIGNATURE COLLECTION</div>
            <p class="hero-index"><span id="heroCurrent">01</span> <span>/</span> 04 &nbsp; — &nbsp; HANDCRAFTED DAILY</p>
            <div class="hero-copy-transition" id="heroCopy">
                <p class="hero-small-title">YOUR NEXT FAVORITE SIP</p>
                <h1 id="heroDrinkName">Caramel<br><em>Macchiato.</em></h1>
                <p class="hero-description" id="heroDrinkDescription">Silky espresso, steamed milk and golden caramel.</p>
                <div class="hero-price"><span>STARTING AT</span><strong id="heroDrinkPrice">₱140.00</strong></div>
            </div>
            <div class="hero-buttons">
                <a href="#menu" class="btn btn-gold" id="heroMenuLink"><i data-lucide="coffee"></i> Explore the Menu <i data-lucide="arrow-up-right"></i></a>
                <a href="#about" class="btn btn-outline">Our Story <i data-lucide="arrow-right"></i></a>
            </div>
            <div class="hero-trust"><span><i data-lucide="check-circle-2"></i> Made fresh</span><span><i data-lucide="sparkles"></i> Crafted with care</span></div>
        </div>
        <div class="hero-showcase">
            <div class="hero-image-halo" aria-hidden="true"></div>
            <div class="hero-image-ring" aria-hidden="true"></div>
            <div class="hero-floor-shadow" aria-hidden="true"></div>
            <div class="hero-ambient-particles" id="heroParticles" aria-hidden="true"></div>
            <span class="hero-visual-label" aria-hidden="true">A MOMENT IN EVERY SIP</span>
            <div class="swiper heroSwiper" aria-label="Signature drinks carousel">
                <div class="swiper-wrapper">
                    @foreach($heroProducts as $index => $drink)
                        @php
                            $theme = $heroThemes[$index] ?? 'caramel';
                            $heroFallback = $heroDefaults[$index]->image;
                            $heroPhoto = $imageUrl($drink->image ?? null, $heroFallback);
                        @endphp
                        <div class="swiper-slide hero-slide"
                             data-theme="{{ $theme }}"
                             data-name="{{ $drink->name }}"
                             data-description="{{ $drink->description ?: 'Carefully crafted for a beautiful coffee moment.' }}"
                             data-price="₱{{ number_format((float) $drink->base_price, 2) }}"
                             data-product-id="{{ $drink->id ?? '' }}">
                            <div class="hero-liquid-layer" aria-hidden="true">
                                <svg viewBox="0 0 440 440" preserveAspectRatio="xMidYMid meet" focusable="false">
                                    <path class="liquid-outer" d="M66 284 C3 244 33 129 111 115 S217 32 318 106 S424 228 375 307"/>
                                    <path class="liquid-inner" d="M90 332 C21 299 64 210 106 177 S215 66 308 158 S383 279 338 342"/>
                                    <path class="liquid-highlight" d="M52 266 C24 220 45 151 94 126 M346 102 C395 139 416 193 404 236"/>
                                    <g class="liquid-drops"><circle cx="49" cy="151" r="10"/><circle cx="384" cy="127" r="7"/><circle cx="392" cy="294" r="11"/><circle cx="108" cy="76" r="5"/><circle cx="66" cy="345" r="6"/><circle cx="347" cy="366" r="5"/></g>
                                </svg>
                            </div>
                            <div class="hero-product-visual"><img src="{{ $heroPhoto }}" alt="{{ $drink->name }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" onerror="this.onerror=null;this.src=@js($heroFallback)"></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="hero-stamp"><i data-lucide="award"></i><span>FRESHLY<br>CRAFTED</span></div>
            <div class="hero-carousel-bottom">
                <span class="hero-carousel-label">EXPLORE OUR FLAVORS</span>
                <div class="hero-carousel-controls"><button class="hero-arrow hero-prev" type="button" aria-label="Previous drink"><i data-lucide="arrow-left"></i></button><div class="hero-pagination"></div><button class="hero-arrow hero-next" type="button" aria-label="Next drink"><i data-lucide="arrow-right"></i></button></div>
            </div>
        </div>
    </div>
    <div class="hero-bottom" aria-hidden="true"></div>
</section>

<!-- MENU -->
<section class="section menu-section" id="menu">
<div class="botanical left" aria-hidden="true"><svg viewBox="0 0 180 240" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M84 232C96 170 80 103 104 10" stroke="#738052" stroke-width="3"/><g fill="#83965f" stroke="#6c7949" stroke-width="1"><ellipse cx="57" cy="188" rx="18" ry="39" transform="rotate(-47 57 188)"/><ellipse cx="117" cy="157" rx="17" ry="35" transform="rotate(43 117 157)"/><ellipse cx="61" cy="116" rx="16" ry="34" transform="rotate(-45 61 116)"/><ellipse cx="127" cy="76" rx="16" ry="35" transform="rotate(37 127 76)"/><ellipse cx="78" cy="46" rx="12" ry="27" transform="rotate(-38 78 46)"/></g></svg></div><div class="botanical right" aria-hidden="true"><svg viewBox="0 0 180 240" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M84 232C96 170 80 103 104 10" stroke="#738052" stroke-width="3"/><g fill="#83965f"><ellipse cx="57" cy="188" rx="18" ry="39" transform="rotate(-47 57 188)"/><ellipse cx="117" cy="157" rx="17" ry="35" transform="rotate(43 117 157)"/><ellipse cx="61" cy="116" rx="16" ry="34" transform="rotate(-45 61 116)"/><ellipse cx="127" cy="76" rx="16" ry="35" transform="rotate(37 127 76)"/></g></svg></div><i class="fa-solid fa-seedling bean-decor one" aria-hidden="true"></i><i class="fa-solid fa-seedling bean-decor two" aria-hidden="true"></i>
    <div class="container">

        <div class="section-heading">
            <div class="section-kicker">
                <i class="fa-solid fa-seedling"></i>
                Crafted for your cravings
            </div>

            <h2>Explore Our Menu</h2>

            <p>
                A little indulgence in every sip and every bite.
            </p>
        </div>

        <div class="menu-filters">
            <button class="filter-btn active" data-filter="all" type="button">
                All Products
            </button>

            <button class="filter-btn" data-filter="coffee" type="button">
                Coffee
            </button>

            <button class="filter-btn" data-filter="non-coffee" type="button">
                Non-Coffee
            </button>

            <button class="filter-btn" data-filter="pastries" type="button">
                Pastries
            </button>

            <button class="filter-btn" data-filter="desserts" type="button">
                Desserts
            </button>
        </div>

        <div class="products-grid" id="productsGrid">

            @foreach($displayProducts as $product)
                @php
                    $category = Str::lower(trim($product->category_name ?? ''));
                    $fallback = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&q=85';
                    $photo = $imageUrl($product->image ?? null, $fallback);
                @endphp

                <article class="product-card"
                         data-category="{{ $category }}"
                         data-name="{{ Str::lower($product->name) }}">

                    <span class="product-shine" aria-hidden="true"></span>
                    <div class="product-image">
                        <img src="{{ $photo }}"
                             alt="{{ $product->name }}"
                             loading="lazy">

                        <span class="product-tag">
                            {{ $product->category_name }}
                        </span>
                    </div>

                    <div class="product-info">

                        <h3>{{ $product->name }}</h3>

                        <p>
                            {{ Str::limit($product->description ?: 'Freshly prepared with care.', 85) }}
                        </p>

                        <div class="product-bottom">

                            <span class="product-price">
                                ₱{{ number_format((float)$product->base_price, 2) }}
                            </span>

                            @if($product->id !== null && \Illuminate\Support\Facades\Route::has('cart.add'))
                                <form method="POST" action="{{ route('cart.add') }}">
                                    @csrf

                                    <input type="hidden"
                                           name="product_id"
                                           value="{{ $product->id }}">

                                    <input type="hidden"
                                           name="quantity"
                                           value="1">

                                    <button type="submit" class="add-cart">
                                        <i class="fa-solid fa-plus"></i>
                                        Add to Cart
                                    </button>
                                </form>
                            @else
                                <button type="button"
                                        class="add-cart"
                                        onclick="showToast('Ordering is not available for this item yet.')">
                                    <i class="fa-solid fa-plus"></i>
                                    Add to Cart
                                </button>
                            @endif

                        </div>
                    </div>
                </article>
            @endforeach

            <div class="empty-products" id="emptyProducts">
                <i class="fa-solid fa-mug-hot fa-2x"></i>
                <p style="margin-top:15px;">
                    No products found in this category.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ANNOUNCEMENTS -->
@if($announcements->isNotEmpty())
<section class="section announcements-section" id="announcements">
<div class="botanical left" aria-hidden="true"><svg viewBox="0 0 180 240" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M84 232C96 170 80 103 104 10" stroke="#738052" stroke-width="3"/><g fill="#83965f" stroke="#6c7949" stroke-width="1"><ellipse cx="57" cy="188" rx="18" ry="39" transform="rotate(-47 57 188)"/><ellipse cx="117" cy="157" rx="17" ry="35" transform="rotate(43 117 157)"/><ellipse cx="61" cy="116" rx="16" ry="34" transform="rotate(-45 61 116)"/><ellipse cx="127" cy="76" rx="16" ry="35" transform="rotate(37 127 76)"/><ellipse cx="78" cy="46" rx="12" ry="27" transform="rotate(-38 78 46)"/></g></svg></div><div class="botanical right" aria-hidden="true"><svg viewBox="0 0 180 240" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M84 232C96 170 80 103 104 10" stroke="#738052" stroke-width="3"/><g fill="#83965f"><ellipse cx="57" cy="188" rx="18" ry="39" transform="rotate(-47 57 188)"/><ellipse cx="117" cy="157" rx="17" ry="35" transform="rotate(43 117 157)"/><ellipse cx="61" cy="116" rx="16" ry="34" transform="rotate(-45 61 116)"/><ellipse cx="127" cy="76" rx="16" ry="35" transform="rotate(37 127 76)"/></g></svg></div><i class="fa-solid fa-seedling bean-decor one" aria-hidden="true"></i><i class="fa-solid fa-seedling bean-decor two" aria-hidden="true"></i>
    <div class="container">

        <div class="section-heading">
            <div class="section-kicker">What's brewing</div>
            <h2>Latest Announcements</h2>
            <p>Discover our latest news, offers, and updates.</p>
        </div>

        <div class="announcement-grid">
            @foreach($announcements as $announcement)
                @php
                    $newsPhoto = $announcementImage($announcement);
                    $newsDate = $announcement->published_at ?? $announcement->created_at ?? null;
                @endphp
                <article class="announcement-card">
                    <div class="announcement-image">
                        @if($newsPhoto)
                            <img src="{{ $newsPhoto }}" alt="{{ $announcement->title }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                            <div class="announcement-image-fallback" style="display:none"><i class="fa-solid fa-mug-hot"></i></div>
                        @else
                            <div class="announcement-image-fallback"><i class="fa-solid fa-mug-hot"></i></div>
                        @endif
                    </div>
                    <div class="announcement-body">
                        <span class="type"><i class="fa-solid fa-bullhorn"></i> {{ ucfirst($announcement->type ?? 'Announcement') }}</span>
                        <h3>{{ $announcement->title }}</h3>
                        <p>{{ Str::limit(strip_tags($announcement->content ?? ''), 180) }}</p>
                        <div class="announcement-meta">
                            @if($newsDate)
                                <span><i class="fa-regular fa-calendar"></i> {{ \Illuminate\Support\Carbon::parse($newsDate)->format('M d, Y') }}</span>
                            @endif
                            @if(!empty($announcement->is_pinned))
                                <span class="announcement-pin"><i class="fa-solid fa-thumbtack"></i> Pinned Update</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    </div>
</section>
@endif

<!-- ABOUT -->
<section class="about-section" id="about">

    <div class="about-image"
         role="img"
         aria-label="Freshly brewed coffee"></div>

    <div class="about-content">

        <div class="section-kicker gold-text">
            Our Story
        </div>

        <h2>
            More Than Coffee.<br>
            <span class="gold-text">It's an Experience.</span>
        </h2>

        <p>
            At The Brew Box, we believe that every cup
            tells a story. From carefully selected coffee
            beans to the final pour, every detail reflects
            our passion for quality, creativity, and comfort.
            Come for the coffee. Stay for the moments.
        </p>

        <div>
            <a href="#contact" class="btn btn-gold">
                Get to Know Us
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="about-features">

            <div class="about-feature">
                <i class="fa-solid fa-seedling"></i>
                <strong>Quality Ingredients</strong>
                <small>Carefully selected for every cup.</small>
            </div>

            <div class="about-feature">
                <i class="fa-solid fa-heart"></i>
                <strong>Made With Love</strong>
                <small>Handcrafted with passion.</small>
            </div>

            <div class="about-feature">
                <i class="fa-solid fa-mug-hot"></i>
                <strong>Cozy Experience</strong>
                <small>A space to relax and connect.</small>
            </div>

        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="section" id="why-us">
    <div class="container">

        <div class="section-heading">
            <div class="section-kicker">The Brew Box Difference</div>
            <h2>Why Coffee Lovers Choose Us</h2>
            <p>Thoughtful details that make every visit special.</p>
        </div>

        <div class="why-grid">

            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <h3>Handcrafted Drinks</h3>
                <p>Every beverage is prepared with attention and care.</p>
            </div>

            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h3>Quality Ingredients</h3>
                <p>Carefully chosen ingredients for satisfying flavors.</p>
            </div>

            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-couch"></i>
                </div>
                <h3>Comfortable Space</h3>
                <p>A welcoming atmosphere for your coffee moments.</p>
            </div>

            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <h3>Made With Passion</h3>
                <p>Good coffee, warm service, and memorable experiences.</p>
            </div>

        </div>
    </div>
</section>

<!-- TESTIMONIAL -->
<section class="testimonial-section">
    <div class="container testimonial-box">

        <div class="section-kicker">
            A Taste of Happiness
        </div>

        <div class="testimonial-stars">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
        </div>

        <blockquote>
            “Great coffee is more than a drink.
            It's a little moment of happiness
            in the middle of your day.”
        </blockquote>

        <p class="credit">
            — The Brew Box Philosophy
        </p>

    </div>
</section>

<!-- GALLERY -->
<section class="section gallery-section" id="gallery">
    <div class="container">

        <div class="section-heading">
            <div class="section-kicker">A Glimpse Into Our World</div>
            <h2>Moments at The Brew Box</h2>
            <p>Every cup, every corner, every memorable moment.</p>
        </div>

        <div class="gallery-grid">

            <img src="https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=800&q=85"
                 alt="Coffee preparation"
                 loading="lazy">

            <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800&q=85"
                 alt="Coffee on a table"
                 loading="lazy">

            <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&q=85"
                 alt="Cozy coffee shop"
                 loading="lazy">

            <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&q=85"
                 alt="Freshly brewed coffee"
                 loading="lazy">

        </div>
    </div>
</section>

<!-- NEWSLETTER -->
<section class="newsletter-section">
    <div class="container newsletter-layout">

        <div>
            <div class="section-kicker gold-text">
                Stay Connected
            </div>

            <h2>Something Good Is Brewing.</h2>

            <p>
                Subscribe for coffee updates, announcements, and special offers.
            </p>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('newsletter.subscribe'))
            <form class="newsletter-form"
                  method="POST"
                  action="{{ route('newsletter.subscribe') }}">
                @csrf

                <input type="email"
                       name="email"
                       placeholder="Enter your email address"
                       aria-label="Email address"
                       required>

                <button type="submit">
                    Subscribe
                </button>
            </form>
        @else
            <form class="newsletter-form"
                  onsubmit="event.preventDefault();showToast('Newsletter subscriptions are not configured yet.');">

                <input type="email"
                       placeholder="Enter your email address"
                       aria-label="Email address"
                       required>

                <button type="submit">
                    Subscribe
                </button>
            </form>
        @endif

    </div>
</section>

<!-- FOOTER -->
<footer class="footer" id="contact">
    <div class="container">

        <div class="footer-grid">

            <div class="footer-brand">

                <a href="#home" class="brand">
                    <div class="brand-logo">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>

                    <div class="brand-text">
                        <strong>THE BREW BOX</strong>
                        <small>Hand Made Coffee</small>
                    </div>
                </a>

                <p class="footer-description">
                    Crafted with passion, served with love.
                    Your favorite moments deserve great coffee.
                </p>

                <div class="footer-socials">
                    <i class="fa-brands fa-facebook-f" aria-label="Facebook"></i>
                    <i class="fa-brands fa-instagram" aria-label="Instagram"></i>
                    <i class="fa-brands fa-tiktok" aria-label="TikTok"></i>
                </div>
                <div class="payment-methods">
                    <h4>We Accept</h4>
                    <div class="payment-badges" aria-label="Accepted payment methods">
                        <span class="payment-badge gcash"><i class="fa-solid fa-wallet"></i> GCash</span>
                        <span class="payment-badge maya"><i class="fa-solid fa-mobile-screen"></i> Maya</span>
                        <span class="payment-badge"><i class="fa-regular fa-credit-card"></i> Card</span>
                        <span class="payment-badge visa"><i class="fa-brands fa-cc-visa"></i> VISA</span>
                    </div>
                </div>

            </div>

            <div class="footer-column">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="#about">About Us</a>
                <a href="#menu">Our Menu</a>
                <a href="#gallery">Gallery</a>
                <a href="#contact">Contact</a>
            </div>

            <div class="footer-column">
                <h4>My Account</h4>
                <a href="{{ url('/login') }}">Login</a>
                <a href="{{ url('/register') }}">Create Account</a>
                <a href="#menu">Browse Products</a>
            </div>

            <div class="footer-column">
                <h4>Get in Touch</h4>

                <p>
                    <i class="fa-solid fa-location-dot gold-text"></i>
                    Visit The Brew Box
                </p>

                <p>
                    <i class="fa-solid fa-mug-hot gold-text"></i>
                    Handcrafted Coffee & More
                </p>

                <p>
                    <i class="fa-solid fa-heart gold-text"></i>
                    Made With Love
                </p>
            </div>

        </div>

        <div class="footer-bottom">
            <span>
                &copy; {{ date('Y') }} THE BREW BOX.
                All Rights Reserved.
            </span>

            <span>
                Crafted With Passion. Served With Love.
            </span>
        </div>

    </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const menuToggle = document.getElementById('menuToggle');
        const mainNav = document.getElementById('mainNav');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const productCards = document.querySelectorAll('.product-card');
        const emptyProducts = document.getElementById('emptyProducts');
        const searchButton = document.getElementById('searchButton');

        // Mobile navigation
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', function () {
                const isOpen = mainNav.classList.toggle('open');

                menuToggle.setAttribute(
                    'aria-expanded',
                    isOpen ? 'true' : 'false'
                );

                menuToggle.innerHTML = isOpen
                    ? '<i class="fa-solid fa-xmark"></i>'
                    : '<i class="fa-solid fa-bars"></i>';
            });

            mainNav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    mainNav.classList.remove('open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    menuToggle.innerHTML =
                        '<i class="fa-solid fa-bars"></i>';
                });
            });
        }

        // Menu category filtering
        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {

                filterButtons.forEach(function (item) {
                    item.classList.remove('active');
                });

                button.classList.add('active');

                const filter = button.dataset.filter;
                let visibleCount = 0;

                productCards.forEach(function (card) {
                    const category = (card.dataset.category || '')
                        .toLowerCase();

                    const visible = filter === 'all' ||
                        category === filter;

                    card.style.display = visible ? '' : 'none';

                    if (visible) {
                        visibleCount++;
                    }
                });

                if (emptyProducts) {
                    emptyProducts.style.display =
                        visibleCount === 0 ? 'block' : 'none';
                }
            });
        });

        // Search products
        if (searchButton) {
            searchButton.addEventListener('click', function () {
                const search = window.prompt('Search our menu:');

                if (search === null) {
                    return;
                }

                const keyword = search.trim().toLowerCase();

                filterButtons.forEach(function (button) {
                    button.classList.toggle(
                        'active',
                        button.dataset.filter === 'all'
                    );
                });

                let visibleCount = 0;

                productCards.forEach(function (card) {
                    const name = (card.dataset.name || '').toLowerCase();
                    const category = (card.dataset.category || '').toLowerCase();

                    const visible = keyword === '' ||
                        name.includes(keyword) ||
                        category.includes(keyword);

                    card.style.display = visible ? '' : 'none';

                    if (visible) {
                        visibleCount++;
                    }
                });

                if (emptyProducts) {
                    emptyProducts.style.display =
                        visibleCount === 0 ? 'block' : 'none';
                }

                document.getElementById('menu')
                    ?.scrollIntoView({ behavior: 'smooth' });
            });
        }

        // Show Laravel flash messages
        @if(session('success'))
            showToast(@json(session('success')));
        @endif

        @if(session('error'))
            showToast(@json(session('error')));
        @endif
    });

    let toastTimeout;

    function showToast(message) {
        const toast = document.getElementById('toast');

        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('show');

        clearTimeout(toastTimeout);

        toastTimeout = setTimeout(function () {
            toast.classList.remove('show');
        }, 4000);
    }

    // Interactive Swiper + GSAP: theme, photo, product title, description and price update together.
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
        const hero = document.querySelector('.hero-experience');
        const swiperEl = document.querySelector('.heroSwiper');
        if (!hero || !swiperEl) return;
        const slides = Array.from(swiperEl.querySelectorAll('.swiper-slide'));
        const nameEl = document.getElementById('heroDrinkName');
        const descEl = document.getElementById('heroDrinkDescription');
        const priceEl = document.getElementById('heroDrinkPrice');
        const currentEl = document.getElementById('heroCurrent');
        const copyEl = document.getElementById('heroCopy');
        const menuLink = document.getElementById('heroMenuLink');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const render = (index, animate = true) => {
            const slide = slides[index]; if (!slide) return;
            const name = slide.dataset.name || 'Signature Drink';
            const words = name.trim().split(/\s+/);
            const last = words.length > 1 ? words.pop() : '';
            const first = words.join(' ') || name;
            const update = () => {
                hero.classList.remove('theme-caramel','theme-matcha','theme-mocha','theme-spanish');
                hero.classList.add('theme-' + (slide.dataset.theme || 'caramel'));
                nameEl.textContent = '';
                nameEl.appendChild(document.createTextNode(first));
                if (last) { nameEl.appendChild(document.createElement('br')); const em = document.createElement('em'); em.textContent = last + '.'; nameEl.appendChild(em); }
                descEl.textContent = slide.dataset.description || '';
                priceEl.textContent = slide.dataset.price || '';
                currentEl.textContent = String(index + 1).padStart(2,'0');
                const productId = slide.dataset.productId;
                menuLink.setAttribute('href', productId ? '#menu' : '#menu');
            };
            if (animate && !reduced && window.gsap) {
                window.gsap.killTweensOf(copyEl);
                window.gsap.to(copyEl,{opacity:0,y:18,duration:.23,ease:'power2.in',onComplete:()=>{
                    update(); window.gsap.fromTo(copyEl,{opacity:0,y:18},{opacity:1,y:0,duration:.55,ease:'power3.out'});
                }});
            } else update();
        };
        render(0,false);
        if (window.Swiper) {
            const carousel = new Swiper(swiperEl, {
                slidesPerView:1,loop:slides.length>1,speed:reduced?0:800,
                effect:'slide',grabCursor:true,autoplay:reduced?false:{delay:5800,disableOnInteraction:false,pauseOnMouseEnter:true},
                pagination:{el:'.hero-pagination',clickable:true},
                navigation:{nextEl:'.hero-next',prevEl:'.hero-prev'},
                keyboard:{enabled:true,onlyInViewport:true},
                on:{realIndexChange(swiper){render(swiper.realIndex,true)}}
            });
            swiperEl.addEventListener('mouseenter',()=>carousel.autoplay?.stop());
            swiperEl.addEventListener('mouseleave',()=>{if(!reduced)carousel.autoplay?.start()});
        } else {
            // Basic navigation still works when the Swiper CDN is unavailable.
            let idx=0;
            slides.forEach((slide,i)=>{slide.style.display=i===0?'flex':'none'});
            const step=delta=>{slides[idx].style.display='none';idx=(idx+delta+slides.length)%slides.length;slides[idx].style.display='flex';render(idx,true)};
            document.querySelector('.hero-next')?.addEventListener('click',()=>step(1));
            document.querySelector('.hero-prev')?.addEventListener('click',()=>step(-1));
        }
    });


    // CINEMATIC LUXURY: progressive enhancement only; all existing routes/forms remain unchanged.
    document.addEventListener('DOMContentLoaded', () => {
        const hero = document.querySelector('.hero-experience');
        if (!hero) return;
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        const coarse = window.matchMedia('(pointer: coarse)');
        const isMobile = window.matchMedia('(max-width: 800px)');
        const hasGsap = !!window.gsap;
        const canAnimate = () => !prefersReduced.matches && hasGsap;
        const visualSlides = Array.from(hero.querySelectorAll('.hero-slide'));
        const activeVisual = () => hero.querySelector('.hero-slide.swiper-slide-active .hero-product-visual') || visualSlides.find(slide => slide.style.display !== 'none')?.querySelector('.hero-product-visual');
        const ambient = document.getElementById('heroParticles');

        // Particle coordinates are deterministic; no expensive canvas/WebGL render loop.
        if (ambient) {
            const positions = [[8,17,6,.28],[17,57,9,.33],[28,30,4,.4],[38,75,7,.25],[61,13,5,.4],[74,30,8,.32],[89,64,5,.42],[82,86,11,.2],[49,47,3,.4],[95,19,6,.3]];
            positions.slice(0,isMobile.matches?5:10).forEach(([x,y,size,opacity],i) => {
                const el=document.createElement('span');
                el.className='hero-particle';
                el.style.cssText=`left:${x}%;top:${y}%;--size:${size}px;--opacity:${opacity};--blur:${i%4===0?'2px':'0px'}`;
                ambient.appendChild(el);
                if (canAnimate()) window.gsap.to(el,{y:-20-(i%4)*14,x:(i%2?1:-1)*12,opacity:opacity*.4,duration:3.5+(i%5),repeat:-1,yoyo:true,ease:'sine.inOut',delay:i*.19});
            });
        }

        // Use a gentle looping float for the ACTIVE drink only; inactive slides are never animated.
        let floatTween=null, liquidTween=null;
        const startFloat=()=>{
            if(floatTween){floatTween.kill();floatTween=null}
            if(liquidTween){liquidTween.kill();liquidTween=null}
            visualSlides.forEach(slide=>{
                const cup=slide.querySelector('.hero-product-visual');
                const splash=slide.querySelector('.hero-liquid-layer svg');
                if(cup && hasGsap) window.gsap.set(cup,{clearProps:'transform'});
                if(splash && hasGsap) window.gsap.set(splash,{clearProps:'transform'});
            });
            if(!canAnimate()) return;
            const cup=activeVisual();
            if(!cup) return;
            const splash=cup.closest('.hero-slide')?.querySelector('.hero-liquid-layer svg');
            floatTween=window.gsap.to(cup,{y:-15,rotation:1.8,duration:2.9,ease:'sine.inOut',yoyo:true,repeat:-1});
            if(splash) liquidTween=window.gsap.to(splash,{rotation:8,scale:1.035,duration:4.2,ease:'sine.inOut',yoyo:true,repeat:-1});
        };
        // Swiper dispatches slide changes; mutation fallback also handles its asynchronous init.
        const swiperElement=hero.querySelector('.heroSwiper');
        if(swiperElement && window.MutationObserver){
            let lastSlide=null;
            const observe=new MutationObserver(()=>{
                const current=hero.querySelector('.swiper-slide-active');
                if(current!==lastSlide){lastSlide=current;startFloat()}
            });
            observe.observe(swiperElement,{subtree:true,attributes:true,attributeFilter:['class']});
        }
        startFloat();

        // Mouse parallax: GSAP quickTo avoids creating a tween on every pointer event.
        if(!coarse.matches && canAnimate()){
            const moveX=window.gsap.quickTo(hero.querySelector('.hero-image-ring'), 'x',{duration:.7,ease:'power3.out'});
            const moveY=window.gsap.quickTo(hero.querySelector('.hero-image-ring'), 'y',{duration:.7,ease:'power3.out'});
            hero.addEventListener('pointermove',event=>{
                if(isMobile.matches)return;
                const rect=hero.getBoundingClientRect();
                const x=((event.clientX-rect.left)/rect.width-.5);
                const y=((event.clientY-rect.top)/rect.height-.5);
                moveX(x*22);moveY(y*16);
            },{passive:true});
            hero.addEventListener('pointerleave',()=>{moveX(0);moveY(0)});
        }

        // Lightweight 3D hover cards; no tilt on touch screens or reduced motion.
        if(!coarse.matches && !prefersReduced.matches){
            document.querySelectorAll('.product-card').forEach(card=>{
                let frame=0;
                card.addEventListener('pointermove',e=>{
                    if(frame)cancelAnimationFrame(frame);
                    frame=requestAnimationFrame(()=>{
                        const r=card.getBoundingClientRect();
                        const x=(e.clientX-r.left)/r.width-.5;
                        const y=(e.clientY-r.top)/r.height-.5;
                        card.classList.add('is-tilting');
                        card.style.transform=`perspective(950px) rotateY(${x*9}deg) rotateX(${-y*9}deg) translateY(-5px)`;
                        card.style.setProperty('--shine-x',`${(x+.5)*100}%`);
                        card.style.setProperty('--shine-y',`${(y+.5)*100}%`);
                        frame=0;
                    });
                },{passive:true});
                card.addEventListener('pointerleave',()=>{
                    if(frame)cancelAnimationFrame(frame);
                    card.classList.remove('is-tilting');
                    card.style.transform='';
                });
            });
        }

        // ScrollTrigger animates only nearby elements; refreshes on viewport resize.
        if(canAnimate() && window.ScrollTrigger){
            window.gsap.registerPlugin(window.ScrollTrigger);
            document.querySelectorAll('.section-heading, .about-content, .why-card, .announcement-card, .gallery-grid img, .product-card').forEach((el,i)=>{
                el.classList.add('cinema-reveal');
                window.gsap.fromTo(el,{autoAlpha:0,y:Math.min(38,18+(i%3)*10)},{autoAlpha:1,y:0,duration:.72,ease:'power2.out',clearProps:'transform,opacity,visibility',scrollTrigger:{trigger:el,start:'top 92%',once:true}});
            });
        }
        // If a user changes OS motion preference while browsing, remove running float animations.
        const onMotionChange=()=>{startFloat();if(prefersReduced.matches && ambient)ambient.querySelectorAll('.hero-particle').forEach(el=>window.gsap?.killTweensOf(el))};
        prefersReduced.addEventListener?.('change',onMotionChange);
    });

</script>

</body>
</html>
