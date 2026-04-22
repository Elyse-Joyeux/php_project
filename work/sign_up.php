<?php
session_start();
require_once 'db.php';
$err = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — UserSpace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --night:    #0c0b0a;
        --surface:  #141210;
        --border:   #2a2520;
        --gold:     #c9974a;
        --gold-lt:  #e0b870;
        --mist:     #f7f3ed;
        --fog:      #9a9088;
        --white:    #ffffff;
        --err:      #e05252;
    }

    html { scroll-behavior: smooth; }

    body {
        min-height: 100vh;
        background: var(--night);
        font-family: 'Outfit', sans-serif;
        color: var(--mist);
        display: grid;
        grid-template-columns: 1fr 1.1fr;
    }

    /* ── LEFT PANEL ── */
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

    /* Decorative grid lines */
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
        width: 24px;
        height: 1px;
        background: var(--gold);
    }

    .panel-center h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.6rem, 3.8vw, 4.2rem);
        line-height: 1.08;
        color: var(--mist);
        margin-bottom: 1.5rem;
    }

    .panel-center h1 em {
        color: var(--gold);
        font-style: italic;
    }

    .panel-center p {
        color: var(--fog);
        font-size: 0.95rem;
        line-height: 1.8;
        max-width: 300px;
        font-weight: 300;
    }

    .panel-bottom {
        position: relative; z-index: 1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
        backdrop-filter: blur(8px);
    }

    .stat-item {
        padding: 1.25rem;
        border-right: 1px solid var(--border);
    }

    .stat-item:last-child { border-right: none; }

    .stat-item .num {
        font-family: 'Playfair Display', serif;
        font-size: 1.6rem;
        color: var(--gold);
        margin-bottom: 0.2rem;
    }

    .stat-item .lbl {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--fog);
    }

    /* ── RIGHT PANEL ── */
    .panel-right {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 3rem 2.5rem;
        overflow-y: auto;
        background: var(--surface);
    }

    .form-wrap {
        width: 100%;
        max-width: 460px;
        padding-top: 0.5rem;
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
        letter-spacing: -0.01em;
    }

    .form-sub {
        color: var(--fog);
        font-size: 0.875rem;
        margin-bottom: 2.25rem;
        font-weight: 300;
    }

    /* ── ERROR BOX ── */
    .error-box {
        background: rgba(224, 82, 82, 0.1);
        border: 1px solid rgba(224, 82, 82, 0.3);
        border-left: 3px solid var(--err);
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #f08080;
        margin-bottom: 1.5rem;
        display: none;
    }
    .error-box.show { display: block; }

    /* ── ROWS ── */
    .row-two {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.9rem;
    }

    /* ── FIELDS ── */
    .field {
        margin-bottom: 1.1rem;
    }

    .field label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--fog);
        margin-bottom: 0.5rem;
    }

    .input-wrap {
        position: relative;
    }

    .input-wrap input {
        width: 100%;
        padding: 0.85rem 1rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        color: var(--mist);
        outline: none;
        transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    }

    .input-wrap input::placeholder { color: rgba(154,144,136,0.5); }

    .input-wrap input:focus {
        border-color: var(--gold);
        background: rgba(201,151,74,0.05);
        box-shadow: 0 0 0 3px rgba(201,151,74,0.12);
    }

    /* ── GENDER ── */
    .gender-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.6rem;
    }

    .gender-option {
        position: relative;
    }

    .gender-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .gender-option label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.65rem 0.5rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        color: var(--fog);
        cursor: pointer;
        transition: all 0.18s;
        text-align: center;
    }

    .gender-option input:checked + label {
        background: rgba(201,151,74,0.12);
        border-color: var(--gold);
        color: var(--gold-lt);
    }

    /* ── STRENGTH BAR ── */
    .strength-bar {
        display: flex;
        gap: 3px;
        margin-top: 6px;
        height: 3px;
    }

    .strength-bar span {
        flex: 1;
        background: var(--border);
        border-radius: 2px;
        transition: background 0.3s;
    }

    .strength-bar.s1 span:nth-child(1) { background: var(--err); }
    .strength-bar.s2 span:nth-child(-n+2) { background: #e09952; }
    .strength-bar.s3 span:nth-child(-n+3) { background: var(--gold); }
    .strength-bar.s4 span { background: #52c47a; }

    /* ── SUBMIT ── */
    .btn-submit {
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
        margin-top: 0.75rem;
        position: relative;
        overflow: hidden;
        transition: background 0.2s, transform 0.1s;
    }

    .btn-submit::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 50%);
        pointer-events: none;
    }

    .btn-submit:hover { background: var(--gold-lt); }
    .btn-submit:active { transform: scale(0.99); }

    /* ── FOOTER LINK ── */
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

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        body { grid-template-columns: 1fr; }
        .panel-left { display: none; }
        .panel-right { background: var(--night); padding: 2.5rem 1.5rem; align-items: flex-start; }
    }

    @media (max-width: 480px) {
        .row-two { grid-template-columns: 1fr; }
        .gender-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>

<div class="panel-left">
    <div class="brand"><span class="brand-dot"></span> UserSpace</div>
    <div class="panel-center">
        <div class="eyebrow">Begin your journey</div>
        <h1>Create your<br><em>own space.</em></h1>
        <p>Set up your profile in seconds. Your personal dashboard, your data, always under your control.</p>
    </div>
    <div class="panel-bottom">
        <div class="stat-item">
            <div class="num">Free</div>
            <div class="lbl">Always</div>
        </div>
        <div class="stat-item">
            <div class="num">Fast</div>
            <div class="lbl">Setup</div>
        </div>
        <div class="stat-item">
            <div class="num">100%</div>
            <div class="lbl">Yours</div>
        </div>
    </div>
</div>

<div class="panel-right">
    <div class="form-wrap">
        <h2 class="form-title">Sign Up</h2>
        <p class="form-sub">Fill in your details to get started</p>

        <?php if ($err): ?>
        <div class="error-box show"><?= $err ?></div>
        <?php endif; ?>
        <div class="error-box" id="errorBox"></div>

        <form action="create.php" method="POST" id="signupForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="row-two">
                <div class="field">
                    <label for="userFname">First Name</label>
                    <div class="input-wrap">
                        <input type="text" name="userFname" id="userFname"
                               placeholder="Jane" required autocomplete="given-name">
                    </div>
                </div>
                <div class="field">
                    <label for="userLname">Last Name</label>
                    <div class="input-wrap">
                        <input type="text" name="userLname" id="userLname"
                               placeholder="Doe" required autocomplete="family-name">
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="userEmail">Email Address</label>
                <div class="input-wrap">
                    <input type="email" name="userEmail" id="userEmail"
                           placeholder="you@example.com" required autocomplete="email">
                </div>
            </div>

            <div class="field">
                <label for="userName">Username</label>
                <div class="input-wrap">
                    <input type="text" name="userName" id="userName"
                           placeholder="janedoe" required autocomplete="username">
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" name="userPassword" id="password"
                           placeholder="Min. 8 chars, 1 uppercase, 1 number"
                           required autocomplete="new-password" oninput="checkStrength(this.value)">
                </div>
                <div class="strength-bar" id="strengthBar">
                    <span></span><span></span><span></span><span></span>
                </div>
            </div>

            <div class="field">
                <label for="Cpassword">Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" name="userPasswordConfirm" id="Cpassword"
                           placeholder="Repeat your password" required autocomplete="new-password">
                </div>
            </div>

            <div class="field">
                <label>Gender</label>
                <div class="gender-grid">
                    <div class="gender-option">
                        <input type="radio" name="gender" id="g-male" value="male">
                        <label for="g-male">Male</label>
                    </div>
                    <div class="gender-option">
                        <input type="radio" name="gender" id="g-female" value="female">
                        <label for="g-female">Female</label>
                    </div>
                    <div class="gender-option">
                        <input type="radio" name="gender" id="g-other" value="other">
                        <label for="g-other">Other</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create Account →</button>
        </form>

        <div class="divider">or</div>
        <div class="footer-link">Already have an account? <a href="login.php">Sign in</a></div>
    </div>
</div>

<script>
    // Show query-string errors
    const params = new URLSearchParams(window.location.search);
    const qErr = params.get('error');
    if (qErr) {
        const b = document.getElementById('errorBox');
        b.textContent = decodeURIComponent(qErr);
        b.classList.add('show');
    }

    function checkStrength(v) {
        const bar = document.getElementById('strengthBar');
        let score = 0;
        if (v.length >= 8)           score++;
        if (/[A-Z]/.test(v))         score++;
        if (/[0-9]/.test(v))         score++;
        if (/[^A-Za-z0-9]/.test(v))  score++;
        bar.className = 'strength-bar' + (score ? ' s' + score : '');
    }

    document.getElementById('signupForm').addEventListener('submit', e => {
        const box      = document.getElementById('errorBox');
        const fname    = document.getElementById('userFname').value.trim();
        const lname    = document.getElementById('userLname').value.trim();
        const email    = document.getElementById('userEmail').value.trim();
        const username = document.getElementById('userName').value.trim();
        const pwd      = document.getElementById('password').value;
        const cpwd     = document.getElementById('Cpassword').value;

        const show = msg => {
            e.preventDefault();
            box.textContent = msg;
            box.classList.add('show');
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };

        if (!fname || !lname || !email || !username || !pwd || !cpwd)
            return show("All fields are required.");
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
            return show("Please enter a valid email address.");
        if (pwd.length < 8)
            return show("Password must be at least 8 characters.");
        if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd))
            return show("Password must contain at least one uppercase letter and one number.");
        if (pwd !== cpwd)
            return show("Passwords do not match.");
    });
</script>
</body>
</html>
