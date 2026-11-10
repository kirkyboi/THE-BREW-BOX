
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account | The Brew Box</title>

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
            background: var(--cream);
        }

        a { color: inherit; text-decoration: none; }

        .auth-layout {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            min-height: 100vh;
        }

        .visual-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 760px;
            padding: 48px 55px;
            color: white;
            background:
                linear-gradient(180deg, rgba(20,9,3,.82), rgba(20,9,3,.15) 45%, rgba(20,9,3,.92)),
                url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1600&q=90')
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
            display: block;
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

        .visual-content { max-width: 560px; }

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
            font-size: clamp(42px, 5vw, 74px);
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
        }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 55px 35px;
            background:
                radial-gradient(circle at 90% 10%, #f0e1cd, transparent 35%),
                var(--cream);
        }

        .form-container { width: min(100%, 440px); }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 12px;
            font-weight: 700;
            color: #806d5d;
            margin-bottom: 35px;
        }

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
            font-size: clamp(32px, 4vw, 43px);
            letter-spacing: -1.5px;
            margin-bottom: 12px;
        }

        .form-description {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .field { margin-bottom: 18px; }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 9px;
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
            height: 53px;
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

        .field-error {
            color: #b13d35;
            font-size: 11px;
            margin-top: 7px;
        }

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #766357;
            font-size: 12px;
            line-height: 1.6;
            margin: 18px 0 25px;
        }

        .terms input {
            margin-top: 3px;
            accent-color: #714321;
        }

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

        .login-prompt {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #857267;
        }

        .login-prompt a {
            color: #8c582e;
            font-weight: 800;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.7;
            color: #922c24;
            background: #fff0ed;
            border: 1px solid #f0c5bf;
        }

        @media (max-width: 850px) {
            .auth-layout { grid-template-columns: 1fr; }
            .visual-panel { min-height: 300px; padding: 30px; }
            .visual-content h1 { font-size: 42px; }
            .visual-content p, .visual-footer { display: none; }
            .form-panel { padding: 40px 24px 65px; }
        }

        @media (max-width: 480px) {
            .visual-panel { min-height: 260px; }
            .visual-content h1 { font-size: 35px; }
            .form-title { font-size: 32px; }
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
            <div class="eyebrow">Join The Brew Box</div>
            <h1>Your Coffee Journey <span>Starts Here.</span></h1>
            <p>
                Create your account and explore handcrafted
                drinks, memorable moments, and everything
                The Brew Box has to offer.
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

            <div class="form-kicker">Become Part of Our Story</div>
            <h2 class="form-title">Create your account.</h2>
            <p class="form-description">
                Just a few details and you're ready to get started.
            </p>

            @if($errors->any())
                <div class="alert" role="alert">
                    Please review the highlighted fields and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field">
                    <label for="name">Full Name</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user"></i>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            maxlength="255"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope"></i>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            autocomplete="email"
                            maxlength="255"
                            required
                        >
                    </div>
                    @error('email')
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
                            placeholder="Minimum 8 characters"
                            autocomplete="new-password"
                            minlength="8"
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

                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                        <button type="button"
                                class="toggle-password"
                                aria-label="Show confirmation password"
                                onclick="togglePassword('password_confirmation', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <label class="terms">
                    <input type="checkbox" required>
                    <span>
                        I agree to create a customer account
                        with The Brew Box.
                    </span>
                </label>

                <button type="submit" class="submit-btn">
                    Create Account
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-prompt">
                Already have an account?
                <a href="{{ route('login') }}">Sign In</a>
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
