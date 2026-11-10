
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | The Brew Box</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        :root {
            --dark: #1c0e08;
            --brown: #3c2215;
            --gold: #d8a86b;
            --cream: #fcf7ef;
            --muted: #86756b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            background: #eee5d8;
        }

        a { color: inherit; text-decoration: none; }

        .auth-layout {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            min-height: 100vh;
        }

        .visual-panel {
            position: relative;
            min-height: 650px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 55px;
            color: white;
            background:
                linear-gradient(180deg, rgba(20,9,3,.78), rgba(20,9,3,.18) 45%, rgba(20,9,3,.91)),
                url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1600&q=90')
                center / cover;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            width: fit-content;
        }

        .brand-icon {
            width: 53px;
            height: 53px;
            border: 1px solid var(--gold);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #f3cf9e;
            font-size: 23px;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 800;
        }

        .brand-caption {
            display: block;
            margin-top: 3px;
            color: #e8c99f;
            letter-spacing: 2.5px;
            font-size: 9px;
            text-transform: uppercase;
        }

        .visual-content { max-width: 570px; }

        .eyebrow {
            color: #f0c68d;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .visual-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(42px, 5vw, 76px);
            line-height: 1.15;
            letter-spacing: -2px;
            margin-bottom: 23px;
        }

        .visual-content h1 span { color: #f0c68d; }

        .visual-content p {
            color: #e6d4c2;
            font-size: 14px;
            line-height: 1.9;
            max-width: 420px;
        }

        .visual-footer {
            color: #e4d0ba;
            font-size: 11px;
            letter-spacing: .5px;
        }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 65px 35px;
            background:
                radial-gradient(circle at 90% 10%, #f0e1cd, transparent 35%),
                var(--cream);
        }

        .form-container { width: min(100%, 430px); }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 12px;
            font-weight: 700;
            color: #806d5d;
            margin-bottom: 52px;
        }

        .back-link:hover { color: var(--brown); }

        .form-kicker {
            color: #a57745;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 11px;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 4vw, 45px);
            letter-spacing: -1.5px;
            margin-bottom: 12px;
        }

        .form-description {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 35px;
        }

        .field { margin-bottom: 22px; }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .input-wrap { position: relative; }

        .input-wrap > i {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            color: #a18a74;
            font-size: 14px;
        }

        .input-wrap input {
            display: block;
            width: 100%;
            height: 56px;
            border: 1px solid #e1d3c3;
            background: white;
            border-radius: 9px;
            padding: 0 48px;
            outline: none;
            font-size: 13px;
            transition: .2s;
        }

        .input-wrap input:focus {
            border-color: #bd8c50;
            box-shadow: 0 0 0 4px rgba(216,168,107,.14);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #8a7666;
            cursor: pointer;
            padding: 5px;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 6px 0 27px;
            font-size: 12px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #685648;
            cursor: pointer;
        }

        .remember input { accent-color: #714321; }

        .submit-btn {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(120deg, #4c2a17, #211007);
            color: white;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: .25s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(40,19,8,.22);
        }

        .register-prompt {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #857267;
        }

        .register-prompt a {
            color: #8c582e;
            font-weight: 800;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 12px;
            line-height: 1.7;
        }

        .alert-error {
            color: #922c24;
            background: #fff0ed;
            border: 1px solid #f0c5bf;
        }

        .alert-success {
            color: #24643d;
            background: #edf9f0;
            border: 1px solid #b9dfc4;
        }

        .field-error {
            color: #b13d35;
            font-size: 11px;
            margin-top: 7px;
        }

        @media (max-width: 850px) {
            .auth-layout { grid-template-columns: 1fr; }
            .visual-panel { min-height: 310px; padding: 30px; }
            .visual-content h1 { font-size: 42px; }
            .visual-content p, .visual-footer { display: none; }
            .form-panel { padding: 45px 24px 70px; }
            .back-link { margin-bottom: 35px; }
        }

        @media (max-width: 480px) {
            .visual-panel { min-height: 270px; }
            .visual-content h1 { font-size: 35px; }
            .form-title { font-size: 33px; }
        }
    </style>
</head>

<body>
<div class="auth-layout">

    <aside class="visual-panel">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-icon">
                <i class="fa-solid fa-mug-hot"></i>
            </span>
            <span>
                <span class="brand-name">THE BREW BOX</span>
                <span class="brand-caption">Hand Made Coffee</span>
            </span>
        </a>

        <div class="visual-content">
            <div class="eyebrow">A Better Coffee Experience</div>
            <h1>Your Daily Ritual, <span>Reimagined.</span></h1>
            <p>
                A warm welcome awaits. Sign in to discover
                your favorite drinks and enjoy every coffee moment.
            </p>
        </div>

        <div class="visual-footer">
            © {{ date('Y') }} THE BREW BOX · Crafted with Passion
        </div>
    </aside>

    <main class="form-panel">
        <div class="form-container">

            <a href="{{ url('/') }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Home
            </a>

            <div class="form-kicker">Welcome Back</div>
            <h2 class="form-title">Sign in to your account.</h2>
            <p class="form-description">
                Enter your credentials to continue your Brew Box experience.
            </p>

            @if(session('status') || session('success'))
                <div class="alert alert-success" role="status">
                    {{ session('status') ?? session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error" role="alert">
                    Please check the information you entered.
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="login">Email or Username</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user"></i>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="Email address or username"
                            autocomplete="username"
                            maxlength="191"
                            required
                            autofocus
                        >
                    </div>
                    @error('login')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button"
                                class="toggle-password"
                                aria-label="Show password"
                                onclick="togglePassword('password', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        Remember me
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    Sign In
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="register-prompt">
                New to The Brew Box?
                <a href="{{ route('register') }}">Create an Account</a>
            </div>
        </div>
    </main>

</div>

<script>
    function togglePassword(id, button) {
        const input = document.getElementById(id);
        const visible = input.type === 'password';

        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.innerHTML = visible
            ? '<i class="fa-regular fa-eye-slash"></i>'
            : '<i class="fa-regular fa-eye"></i>';
    }
</script>
</body>
</html>
