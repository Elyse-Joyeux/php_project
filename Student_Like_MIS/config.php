<?php

session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'joyeux@2010');
define('DB_NAME', 'sms_db');

// Connect to database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create tables if not exists
$create_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'student') DEFAULT 'student',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_users);

$create_logs = "CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_logs);

$create_results = "CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject VARCHAR(100),
    exam_type VARCHAR(50),
    marks INT,
    grade VARCHAR(2),
    term INT,
    year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_results);

$create_settings = "CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    theme VARCHAR(10) DEFAULT 'light',
    notifications TINYINT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_settings);

$create_announcements = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    content TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
mysqli_query($conn, $create_announcements);

// Create default admin if not exists
$check_admin = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
if (mysqli_num_rows($check_admin) == 0) {
    // used my own password, in your code use yours!
    $admin_pass = password_hash('admin@elyjoyx$', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (username, email, password, full_name, role) VALUES ('admin', 'elysejoyeux590@gmail.com', '$admin_pass', 'System Administrator', 'admin')");
    $admin_id = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO user_settings (user_id, theme) VALUES ($admin_id, 'light')");
}

// Helper functions
function logAction($user_id, $action, $details) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = mysqli_prepare($conn, "INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>