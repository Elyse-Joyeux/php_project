<?php

require_once 'config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('student_dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-primary);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f7f9fc;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --input-bg: #ffffff;
        }

        body.dark {
            --bg-primary: #1a202c;
            --bg-secondary: #2d3748;
            --text-primary: #f7fafc;
            --text-secondary: #a0aec0;
            --border: #4a5568;
            --card-bg: #2d3748;
            --input-bg: #4a5568;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
        }

        .login-header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }

        .login-form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .register-link, .forgot-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a, .forgot-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-bg);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            color: var(--text-primary);
            font-size: 20px;
        }
    </style>
</head>
<body>
<button class="theme-toggle" onclick="toggleTheme()" id="themeToggle">
    <i class="fas fa-moon"></i>
</button>

<div class="login-container">
    <div class="login-header">
        <h1><i class="fas fa-graduation-cap"></i> Student Management</h1>
        <p>Login to your account</p>
    </div>
    <div class="login-form">
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login_process.php">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username or Email</label>
                <input type="text" name="username" required placeholder="Enter username or email">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
        <div class="register-link">
            <a href="register.php">Don't have an account? Register</a>
        </div>
        <div class="forgot-link">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>
</div>

<script>
    function toggleTheme() {
        document.body.classList.toggle('dark');
        const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        const icon = document.querySelector('#themeToggle i');
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
    
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
        const icon = document.querySelector('#themeToggle i');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
</script>
</body>
</html>