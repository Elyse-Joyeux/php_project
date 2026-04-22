<?php
//  Database Configuration 
// Keep this file OUTSIDE your web root in production, or restrict access via .htaccess
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'joyeux@2010');   // ← change this in production
define('DB_NAME', 'userSignUp');

function db_connect(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // In production, log this error instead of exposing it
        error_log("DB connection failed: " . $conn->connect_error);
        http_response_code(500);
        exit("A database error occurred. Please try again later.");
    }
    return $conn;
}

// CSRF Helpers 
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit("Invalid CSRF token. Please go back and try again.");
    }
}
