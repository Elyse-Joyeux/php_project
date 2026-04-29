<?php

require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = mysqli_query($conn, "SELECT id, username FROM users WHERE email = '$email'");
    
    if ($user = mysqli_fetch_assoc($query)) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = {$user['id']}");
        
        // In a real application, send email here
        // For demo, we'll show the reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        $message = "Password reset link: <a href='$reset_link'>$reset_link</a><br><small>(In production, this would be sent to your email)</small>";
    } else {
        $error = "Email not found in our system";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        }
        
        body.dark {
            --bg-primary: #1a202c;
            --text-primary: #f7fafc;
            --border: #4a5568;
        }
        
        .forgot-container {
            background: var(--bg-primary);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        
        .forgot-container h2 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .btn-reset {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #276749;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #c53030;
        }
    </style>
</head>
<body>
<div class="forgot-container">
    <h2><i class="fas fa-key"></i> Forgot Password</h2>
    <p style="color: #718096; margin-bottom: 20px;">Enter your email to receive a reset link</p>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn-reset">Send Reset Link</button>
    </form>
    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: #667eea;">Back to Login</a>
    </div>
</div>
</body>
</html>