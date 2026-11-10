@php
    $currentUser = auth()->user();
    $isSuperadmin = false;

    if ($currentUser && $currentUser->status === 'active') {
        $isSuperadmin = \Illuminate\Support\Facades\DB::table('roles')
            ->where('id', $currentUser->role_id)
            ->where('slug', 'superadmin')
            ->exists();
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'THE BREW BOX')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #100d0b; color: #f5ede1; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.55; }
        a { color: inherit; }
        .site-header { background: #1b1511; border-bottom: 1px solid #4d3926; }
        .nav-shell { max-width: 1280px; margin: 0 auto; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .brand { text-decoration: none; font-size: 1.2rem; font-weight: 800; letter-spacing: .09em; color: #e9b96c; }
        .nav-links { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
        .nav-link { display: inline-block; padding: 10px 14px; border-radius: 9px; text-decoration: none; color: #eee0cf; font-size: .9rem; font-weight: 600; transition: background .15s ease; }
        .nav-link:hover, .nav-link:focus-visible { background: #35271c; outline-color: #e9b96c; }
        .nav-link--primary { background: #c18b42; color: #1a1008; }
        .nav-link--primary:hover, .nav-link--primary:focus-visible { background: #e9b96c; }
        .page-shell { max-width: 1280px; margin: 0 auto; padding: 32px 24px 60px; }
        .flash { margin-bottom: 24px; padding: 14px 18px; border: 1px solid #477b5b; background: #153626; color: #d7f6e2; border-radius: 10px; }
        .flash--error { border-color: #9b4848; background: #3a1a1a; color: #ffd8d8; }
        .footer { border-top: 1px solid #35281e; padding: 20px 24px; text-align: center; color: #a99886; font-size: .85rem; }
        @media (max-width: 640px) { .nav-shell { align-items: flex-start; } .nav-links { width: 100%; } .page-shell { padding: 22px 16px 40px; } }
    </style>
    @stack('styles')
</head>
<body>
    <header class="site-header">
        <nav class="nav-shell" aria-label="Main navigation">
            <a class="brand" href="{{ url('/') }}">THE BREW BOX</a>
            <div class="nav-links">
                <a class="nav-link" href="{{ url('/') }}">Home</a>
                @if ($isSuperadmin)
                    <a class="nav-link" href="{{ url('/superadmin/dashboard') }}">Dashboard</a>
                    @if (\Illuminate\Support\Facades\Route::has('superadmin.announcements.index'))
                        <a class="nav-link nav-link--primary" href="{{ route('superadmin.announcements.index') }}">Manage Announcements</a>
                    @endif
                @endif
                @guest
                    @if (\Illuminate\Support\Facades\Route::has('login'))
                        <a class="nav-link" href="{{ route('login') }}">Log in</a>
                    @endif
                @endguest
            </div>
        </nav>
    </header>

    <main class="page-shell">
        @if (session('success'))
            <div class="flash" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="flash flash--error" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="footer">&copy; {{ date('Y') }} THE BREW BOX. All rights reserved.</footer>
    @stack('scripts')
</body>
</html>
