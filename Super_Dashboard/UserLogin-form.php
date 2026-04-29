<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id']))  { header("Location: Userdashboard.php"); exit; }
if (!empty($_SESSION['admin_id'])) { header("Location: Admin.php");     exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="UserLogin.css">
    <style>
        .forgot-link { text-align:right; font-size:.8rem; margin-top:-.5rem; margin-bottom:1rem; }
        .forgot-link a { color:var(--rust); text-decoration:none; font-weight:500; }
        .forgot-link a:hover { text-decoration:underline; }
    </style>
</head>
<body>

<div class="panel-left" aria-hidden="true">
    <div class="brand">RCA Portal.</div>
    <div class="panel-tagline">
        <h1>Welcome <em>back</em> to your space.</h1>
        <p>Sign in to access your student dashboard, track your progress, and manage your profile.</p>
    </div>
    <div class="panel-bottom">
        <div class="stat"><div class="num">100%</div><div class="label">Secure</div></div>
        <div class="stat"><div class="num">Fast</div><div class="label">Access</div></div>
        <div class="stat"><div class="num">Yours</div><div class="label">Always</div></div>
    </div>
</div>

<div class="panel-right">
    <div class="form-card">
        <h2>Sign In</h2>
        <p class="sub">Enter your credentials to continue</p>

        <div class="error-box" id="errorBox" role="alert" aria-live="polite"></div>

        <form action="UserLogin.php" method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="field">
                <label for="userEmail">Email Address</label>
                <input type="email" name="userEmail" id="userEmail"
                       placeholder="you@rca.ac.rw" required autocomplete="email"
                       aria-required="true">
            </div>

            <div class="field">
                <label for="userPassword">Password</label>
                <div class="password-wrap">
                    <input type="password" name="userPassword" id="userPassword"
                           placeholder="••••••••" required autocomplete="current-password"
                           aria-required="true">
                    <button type="button" class="toggle-pwd" aria-label="Toggle password visibility"
                            onclick="togglePwd('userPassword', this)">👁</button>
                </div>
            </div>

            <!-- Forgot password link -->
            <div class="forgot-link">
                <a href="ForgotPassword.php">Forgot your password?</a>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <span>Sign In →</span>
            </button>
        </form>

        <div class="divider">or</div>
        <div class="link-signup">Don't have an account? <a href="sign_up.php">Create one</a></div>
    </div>
</div>

<script>
    const params = new URLSearchParams(window.location.search);
    const err = params.get('error');
    if (err) {
        const box = document.getElementById('errorBox');
        box.textContent = decodeURIComponent(err);
        box.classList.add('show');
        history.replaceState(null, '', window.location.pathname);
    }

    document.getElementById('loginForm').addEventListener('submit', e => {
        const email = document.getElementById('userEmail').value.trim();
        const pwd   = document.getElementById('userPassword').value;
        const box   = document.getElementById('errorBox');
        const show  = msg => { e.preventDefault(); box.textContent = msg; box.classList.add('show'); };

        if (!email || !pwd)                                          return show('Please fill in all fields.');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))             return show('Please enter a valid email address.');

        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').querySelector('span').textContent = 'Signing in…';
    });

    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.textContent = show ? '🙈' : '👁';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    }
</script>
</body>
</html>
