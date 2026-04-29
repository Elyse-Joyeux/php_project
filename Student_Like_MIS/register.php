<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if user exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username or email already exists";
    } else {
        $query = "INSERT INTO users (username, email, password, full_name, role) VALUES ('$username', '$email', '$password', '$full_name', 'student')";
        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO user_settings (user_id) VALUES ($user_id)");
            redirect('index.php?success=Registration successful! Please login.');
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Management System</title>
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
        
        :root {
            --bg-primary: #ffffff;
            --text-primary: #2d3748;
            --border: #e2e8f0;
            --input-bg: #ffffff;
        }
        
        body.dark {
            --bg-primary: #1a202c;
            --text-primary: #f7fafc;
            --border: #4a5568;
            --input-bg: #4a5568;
        }
        
        .register-container {
            background: var(--bg-primary);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            color: white;
            font-size: 24px;
        }
        
        .register-form {
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
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
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
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            font-size: 20px;
        }
    </style>
</head>
<body>
<button class="theme-toggle" onclick="toggleTheme()">
    <i class="fas fa-moon"></i>
</button>

<div class="register-container">
    <div class="register-header">
        <h1><i class="fas fa-user-plus"></i> Create Account</h1>
        <p style="color: rgba(255,255,255,0.9);">Join our school community</p>
    </div>
    <div class="register-form">
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label><i class="fas fa-user-circle"></i> Full Name</label>
                <input type="text" name="full_name" required placeholder="Enter your full name">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required placeholder="Create a password">
            </div>
            <button type="submit" class="btn-register"><i class="fas fa-check-circle"></i> Register</button>
        </form>
        <div class="login-link">
            <a href="index.php">Already have an account? Login</a>
        </div>
    </div>
</div>

<script>
    function toggleTheme() {
        document.body.classList.toggle('dark');
        const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
    }
    
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
    }
</script>
</body>
</html>