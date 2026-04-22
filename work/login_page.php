<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
if (!empty($_SESSION['admin_id'])) { header("Location: admin.php"); exit; }

$err = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — UserSpace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --night:   #0c0b0a;
        --surface: #141210;
        --border:  #2a2520;
        --gold:    #c9974a;
        --gold-lt: #e0b870;
        --mist:    #f7f3ed;
        --fog:     #9a9088;
        --err:     #e05252;
    }

    body {
        min-height: 100vh;
        background: var(--night);
        font-family: 'Outfit', sans-serif;
        color: var(--mist);
        display: grid;
        grid-template-columns: 1fr 1.1fr;
    }

    /* ── LEFT ── */
    .panel-left {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 3rem;
        overflow: hidden;
        border-right: 1px solid var(--border);
    }

    .panel-left::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 70% 50% at 20% 30%, rgba(201,151,74,0.18) 0%, transparent 70%),
            radial-gradient(ellipse 50% 60% at 80% 80%, rgba(201,151,74,0.08) 0%, transparent 60%);
        pointer-events: none;
    }

    .panel-left::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(201,151,74,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,151,74,0.04) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    .brand {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--gold);
        letter-spacing: 0.05em;
        position: relative; z-index: 1;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .brand-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }

    .panel-center { position: relative; z-index: 1; }

    .panel-center .eyebrow {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: var(--gold);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .panel-center .eyebrow::before {
        content: '';
        display: inline-block;
        width: 24px; height: 1px;
        background: var(--gold);
    }

    .panel-center h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.6rem, 3.8vw, 4.2rem);
        line-height: 1.08;
        color: var(--mist);
        margin-bottom: 1.5rem;
    }

    .panel-center h1 em { color: var(--gold); font-style: italic; }

    .panel-center p {
        color: var(--fog);
        font-size: 0.95rem;
        line-height: 1.8;
        max-width: 300px;
        font-weight: 300;
    }

    .trust-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
        position: relative; z-index: 1;
    }

    .trust-item {
        padding: 1.25rem;
        border-right: 1px solid var(--border);
    }

    .trust-item:last-child { border-right: none; }

    .trust-item .num {
        font-family: 'Playfair Display', serif;
        font-size: 1.6rem;
        color: var(--gold);
        margin-bottom: 0.2rem;
    }

    .trust-item .lbl {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--fog);
    }

    /* ── RIGHT ── */
    .panel-right {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 2.5rem;
        background: var(--surface);
    }

    .form-wrap {
        width: 100%;
        max-width: 400px;
        animation: riseIn 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    @keyframes riseIn {
        from { opacity: 0; transform: translateY(28px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .form-title {
        font-family: 'Playfair Display', serif;
        font-size: 2.1rem;
        color: var(--mist);
        margin-bottom: 0.4rem;
    }

    .form-sub {
        color: var(--fog);
        font-size: 0.875rem;
        margin-bottom: 2.25rem;
        font-weight: 300;
    }

    .error-box {
        background: rgba(224,82,82,0.1);
        border: 1px solid rgba(224,82,82,0.3);
        border-left: 3px solid var(--err);
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #f08080;
        margin-bottom: 1.5rem;
        display: none;
    }
    .error-box.show { display: block; }

    .field { margin-bottom: 1.25rem; }

    .field label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--fog);
        margin-bottom: 0.5rem;
    }

    .field input {
        width: 100%;
        padding: 0.9rem 1rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        color: var(--mist);
        outline: none;
        transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    }

    .field input::placeholder { color: rgba(154,144,136,0.5); }

    .field input:focus {
        border-color: var(--gold);
        background: rgba(201,151,74,0.05);
        box-shadow: 0 0 0 3px rgba(201,151,74,0.12);
    }

    .btn-login {
        width: 100%;
        padding: 1rem;
        background: var(--gold);
        color: var(--night);
        border: none;
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        cursor: pointer;
        margin-top: 0.5rem;
        position: relative;
        overflow: hidden;
        transition: background 0.2s, transform 0.1s;
    }

    .btn-login::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 50%);
        pointer-events: none;
    }

    .btn-login:hover { background: var(--gold-lt); }
    .btn-login:active { transform: scale(0.99); }

    .divider {
        text-align: center;
        font-size: 0.8rem;
        color: var(--border);
        margin: 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    .footer-link {
        text-align: center;
        font-size: 0.875rem;
        color: var(--fog);
    }

    .footer-link a {
        color: var(--gold);
        font-weight: 500;
        text-decoration: none;
        transition: color 0.15s;
    }

    .footer-link a:hover { color: var(--gold-lt); }

    @media (max-width: 768px) {
        body { grid-template-columns: 1fr; }
        .panel-left { display: none; }
        .panel-right { background: var(--night); padding: 2.5rem 1.5rem; }
    }
    </style>
</head>
<body>

<div class="panel-left">
    <div class="brand"><span class="brand-dot"></span> UserSpace</div>
    <div class="panel-center">
        <div class="eyebrow">Welcome back</div>
        <h1>Back to your<br><em>own space.</em></h1>
        <p>Sign in to access your personal dashboard and pick up right where you left off.</p>
    </div>
    <div class="trust-row">
        <div class="trust-item">
            <div class="num">100%</div>
            <div class="lbl">Secure</div>
        </div>
        <div class="trust-item">
            <div class="num">Fast</div>
            <div class="lbl">Access</div>
        </div>
        <div class="trust-item">
            <div class="num">Yours</div>
            <div class="lbl">Always</div>
        </div>
    </div>
</div>

<div class="panel-right">
    <div class="form-wrap">
        <h2 class="form-title">Sign In</h2>
        <p class="form-sub">Enter your credentials to continue</p>

        <?php if ($err): ?>
        <div class="error-box show"><?= $err ?></div>
        <?php endif; ?>
        <div class="error-box" id="errorBox"></div>

        <form action="login.php" method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="field">
                <label for="userEmail">Email Address</label>
                <input type="email" name="userEmail" id="userEmail"
                       placeholder="you@example.com" required autocomplete="email">
            </div>
            <div class="field">
                <label for="userPassword">Password</label>
                <input type="password" name="userPassword" id="userPassword"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Sign In →</button>
        </form>

        <div class="divider">or</div>
        <div class="footer-link">No account yet? <a href="sign_up.php">Create one</a></div>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', e => {
        const email = document.getElementById('userEmail').value.trim();
        const pwd   = document.getElementById('userPassword').value;
        const box   = document.getElementById('errorBox');
        if (!email || !pwd) {
            e.preventDefault();
            box.textContent = 'Please fill in all fields.';
            box.classList.add('show');
        }
    });
</script>
</body>
</html>
