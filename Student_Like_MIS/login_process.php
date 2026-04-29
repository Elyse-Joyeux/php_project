<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    $result = mysqli_query($conn, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            
            logAction($row['id'], 'Login', 'User logged in successfully');
            
            if ($row['role'] == 'admin') {
                redirect('admin_dashboard.php');
            } else {
                redirect('student_dashboard.php');
            }
        } else {
            redirect('index.php?error=Invalid password');
        }
    } else {
        redirect('index.php?error=User not found');
    }
}
?>