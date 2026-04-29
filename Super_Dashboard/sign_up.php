<?php
session_start();
require_once __DIR__ . '/db.php';
if (!empty($_SESSION['user_id'])) { header("Location: Userdashboard.php"); exit; }
$err = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="UserSignUp.css">
</head>
<body>

<div class="panel-left" aria-hidden="true">
    <div class="brand">RCA Portal.</div>
    <div class="panel-tagline">
        <h1>Create your <em>own</em> space.</h1>
        <p>Join Rwanda Coding Academy's student portal. Set up your profile and take control of your academic journey.</p>
    </div>
    <div class="panel-bottom">
        <div class="stat"><div class="num">Free</div><div class="label">Always</div></div>
        <div class="stat"><div class="num">Fast</div><div class="label">Setup</div></div>
        <div class="stat"><div class="num">100%</div><div class="label">Yours</div></div>
    </div>
</div>

<div class="panel-right">
    <div class="form-card">
        <h2>Sign Up</h2>
        <p class="sub">Fill in your details to get started</p>

        <?php if ($err): ?>
        <div class="error-box show" role="alert"><?= $err ?></div>
        <?php endif; ?>
        <div class="error-box" id="errorBox" role="alert" aria-live="polite"></div>

        <form action="Create.php" method="POST" id="signupForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="section-label">Personal Information</div>

            <div class="row-two">
                <div class="field">
                    <label for="userFname">First Name <span class="req">*</span></label>
                    <input type="text" name="userFname" id="userFname"
                           placeholder="Jane" required autocomplete="given-name" maxlength="80">
                </div>
                <div class="field">
                    <label for="userLname">Last Name <span class="req">*</span></label>
                    <input type="text" name="userLname" id="userLname"
                           placeholder="Doe" required autocomplete="family-name" maxlength="80">
                </div>
            </div>

            <div class="field">
                <label for="userEmail">Email Address <span class="req">*</span></label>
                <input type="email" name="userEmail" id="userEmail"
                       placeholder="you@rca.ac.rw" required autocomplete="email">
            </div>

            <div class="row-two">
                <div class="field">
                    <label for="userName">Username <span class="req">*</span></label>
                    <input type="text" name="userName" id="userName"
                           placeholder="janedoe" required autocomplete="username"
                           minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
                    <span class="hint">Letters, numbers, underscores only</span>
                </div>
                <div class="field">
                    <label for="userPhone">Phone Number</label>
                    <input type="tel" name="phone" id="userPhone"
                           placeholder="+250 7XX XXX XXX" autocomplete="tel">
                </div>
            </div>

            <div class="field">
                <label>Gender</label>
                <div class="gender-row">
                    <label class="radio-label"><input type="radio" name="gender" value="male"> Male</label>
                    <label class="radio-label"><input type="radio" name="gender" value="female"> Female</label>
                    <label class="radio-label"><input type="radio" name="gender" value="other" checked> Prefer not to say</label>
                </div>
            </div>

            <div class="section-label">Academic Information <span class="optional">(optional)</span></div>

            <div class="row-two">
                <div class="field">
                    <label for="studentId">Student ID</label>
                    <input type="text" name="studentId" id="studentId"
                           placeholder="RCA/2024/001" maxlength="30">
                </div>
                <div class="field">
                    <label for="cohort">Cohort / Year</label>
                    <input type="text" name="cohort" id="cohort"
                           placeholder="Year 2 · 2024" maxlength="50">
                </div>
            </div>

            <div class="field">
                <label for="track">Track / Programme</label>
                <select name="track" id="track">
                    <option value="">— Select your track —</option>
                    <option value="Software Development">Software Development</option>
                    <option value="Data Science & AI">Data Science & AI</option>
                    <option value="Cybersecurity">Cybersecurity</option>
                    <option value="Embedded Systems">Embedded Systems</option>
                    <option value="Network & Infrastructure">Network & Infrastructure</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="section-label">Security</div>

            <div class="field">
                <label for="password">Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" name="userPassword" id="password"
                           placeholder="Min. 8 chars, 1 uppercase, 1 number"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-pwd" aria-label="Toggle password"
                            onclick="togglePwd('password', this)">👁</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <span class="hint" id="strengthLabel"></span>
            </div>

            <div class="field">
                <label for="Cpassword">Confirm Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" name="userPasswordConfirm" id="Cpassword"
                           placeholder="Repeat your password"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-pwd" aria-label="Toggle password"
                            onclick="togglePwd('Cpassword', this)">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-signup" id="submitBtn"><span>Create Account →</span></button>
        </form>

        <div class="divider">or</div>
        <div class="link-login">Already have an account? <a href="UserLogin-form.php">Sign in</a></div>
    </div>
</div>

<script>
    // Show server-side error from query string
    const params = new URLSearchParams(window.location.search);
    const qErr = params.get('error');
    if (qErr) {
        const box = document.getElementById('errorBox');
        box.textContent = decodeURIComponent(qErr);
        box.classList.add('show');
        history.replaceState(null, '', window.location.pathname);
    }

    // Password strength meter
    document.getElementById('password').addEventListener('input', function () {
        const v = this.value;
        let score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        const fill   = document.getElementById('strengthFill');
        const label  = document.getElementById('strengthLabel');
        const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['', '#d63a3a', '#e8a020', '#2a9d2a', '#1a6e1a'];
        fill.style.width = (score * 25) + '%';
        fill.style.background = colors[score] || 'transparent';
        label.textContent = levels[score] || '';
        label.style.color = colors[score] || '';
    });

    // Toggle password visibility
    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.textContent = show ? '🙈' : '👁';
    }

    // Client-side form validation
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
            return show("All required fields must be filled.");
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
            return show("Please enter a valid email address.");
        if (!/^[a-zA-Z0-9_]{3,50}$/.test(username))
            return show("Username: 3–50 characters, letters/numbers/underscores only.");
        if (pwd.length < 8)
            return show("Password must be at least 8 characters.");
        if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd))
            return show("Password must contain at least one uppercase letter and one number.");
        if (pwd !== cpwd)
            return show("Passwords do not match.");

        // Disable to prevent double-submit
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').querySelector('span').textContent = 'Creating account…';
    });
</script>
</body>
</html>
