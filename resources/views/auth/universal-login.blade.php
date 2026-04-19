<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — {{ $appSettings->company_name ?? 'Booklix' }}</title>
    <meta name="description" content="Sign in to {{ $appSettings->company_name ?? 'Booklix' }} operations platform">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --red:        #e71a39;
            --red-dark:   #c41530;
            --red-glow:   rgba(231, 26, 57, 0.25);
            --bg:         #0a0a0f;
            --surface:    #111118;
            --card:       #16161f;
            --border:     rgba(255,255,255,0.07);
            --text:       #f1f1f5;
            --muted:      #8b8b9e;
            --input-bg:   #1c1c28;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ─── Background ─────────────────────────────────── */
        .bg-wrap {
            position: fixed;
            inset: 0;
            z-index: 0;
        }
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.35;
        }
        .bg-orb-1 {
            width: 600px; height: 600px;
            background: var(--red);
            top: -200px; left: -200px;
            animation: float 12s ease-in-out infinite;
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: #7c3aed;
            bottom: -150px; right: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(30px) scale(1.05); }
        }

        /* ─── Layout ─────────────────────────────────────── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ─── Card ───────────────────────────────────────── */
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 40px;
            backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px var(--border),
                0 40px 80px rgba(0,0,0,0.6),
                0 0 60px var(--red-glow);
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── Logo / Brand ───────────────────────────────── */
        .brand {
            text-align: center;
            margin-bottom: 36px;
        }
        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--red), #ff4d6d);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px var(--red-glow);
        }
        .brand-icon svg {
            width: 32px;
            height: 32px;
            fill: white;
        }
        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .brand-sub {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--muted);
            margin-top: 4px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* ─── Divider ────────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .divider-line { flex: 1; height: 1px; background: var(--border); }
        .divider-text { font-size: 0.7rem; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; }

        /* ─── Form ───────────────────────────────────────── */
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.04em;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.925rem;
            padding: 13px 14px 13px 42px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-input:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px var(--red-glow);
            background: #1e1e2c;
        }
        .form-input::placeholder { color: #4a4a5e; }
        .form-input.error-field { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.2); }

        /* ─── Error ──────────────────────────────────────── */
        .error-box {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .error-box svg { flex-shrink: 0; color: #ef4444; margin-top: 1px; }
        .error-box span { font-size: 0.85rem; color: #fca5a5; line-height: 1.4; }

        /* ─── Remember me ────────────────────────────────── */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .remember-checkbox {
            width: 16px;
            height: 16px;
            accent-color: var(--red);
            cursor: pointer;
        }
        .remember-label {
            font-size: 0.82rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }

        /* ─── Submit Button ──────────────────────────────── */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--red), #ff2d55);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            padding: 14px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            box-shadow: 0 4px 24px var(--red-glow);
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 32px var(--red-glow);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-submit .btn-text { transition: opacity 0.2s; }
        .btn-submit .btn-spinner {
            display: none;
            position: absolute;
            inset: 0;
            align-items: center;
            justify-content: center;
        }
        .btn-submit.loading .btn-text { opacity: 0; }
        .btn-submit.loading .btn-spinner { display: flex; }
        .spinner-ring {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── Footer ─────────────────────────────────────── */
        .card-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--muted);
        }
        .card-footer strong { color: var(--red); font-weight: 600; }

        /* ─── Responsive ─────────────────────────────────── */
        @media (max-width: 480px) {
            .card { padding: 32px 24px; border-radius: 16px; }
        }
    </style>
</head>
<body>

<!-- Background -->
<div class="bg-wrap">
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-grid"></div>
</div>

<div class="page">
    <div class="card">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon">
                <!-- Hot Air Balloon Icon -->
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 3.25 2.04 6.03 5 7.18V18h-1a1 1 0 0 0 0 2h1v1a1 1 0 0 0 2 0v-1h1a1 1 0 0 0 0-2h-1v-1.82C14.96 15.03 17 12.25 17 9c0-3.87-3.13-7-5-7zm0 2c2.76 0 5 2.24 5 5s-2.24 5-5 5-5-2.24-5-5 2.24-5 5-5z"/>
                </svg>
            </div>
            <div class="brand-name">{{ $appSettings->company_name ?? 'Booklix' }}</div>
            <div class="brand-sub">Operations Platform</div>
        </div>

        <!-- Divider -->
        <div class="divider">
            <div class="divider-line"></div>
            <div class="divider-text">Sign in to continue</div>
            <div class="divider-line"></div>
        </div>

        <!-- Errors -->
        @if ($errors->any())
        <div class="error-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <!-- Login Form -->
        <form id="loginForm" action="{{ route('login') }}" method="POST" novalidate>
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </span>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="form-input {{ $errors->has('email') ? 'error-field' : '' }}"
                        placeholder="you@company.com"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-input {{ $errors->has('password') ? 'error-field' : '' }}"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" class="remember-checkbox" value="1">
                <label for="remember" class="remember-label">Keep me signed in</label>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit">
                <span class="btn-text">Sign In</span>
                <span class="btn-spinner">
                    <span class="spinner-ring"></span>
                </span>
            </button>
        </form>

        <!-- Footer -->
        <div class="card-footer">
            Powered by <strong>Booklix</strong> &mdash; All rights reserved
        </div>

    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });
</script>

</body>
</html>
