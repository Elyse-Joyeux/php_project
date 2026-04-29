<?php

require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
$query = mysqli_query($conn, "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
$user = mysqli_fetch_assoc($query);

if (!$user && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $error = "Invalid or expired reset link";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $token = $_POST['token'];
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $query = mysqli_query($conn, "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
    if ($user = mysqli_fetch_assoc($query)) {
        mysqli_query($conn, "UPDATE users SET password = '$new_password', reset_token = NULL, reset_expires = NULL WHERE id = {$user['id']}");
        $success = "Password reset successful! <a href='index.php'>Login here</a>";
    } else {
        $error = "Invalid or expired reset link";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .form-group { margin-bottom: 20px; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; cursor: pointer; }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #fed7d7; color: #c53030; }
        .alert-success { background: #c6f6d5; color: #276749; }
    </style>
</head>
<body>
<div class="reset-container">
    <h2>Reset Password</h2>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(!$error && !$success): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <input type="password" name="password" placeholder="New Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>