<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'joyeux@2010');   // ← CHANGE IN PRODUCTION
define('DB_NAME', 'userSignUp');

function db_connect(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn; // reuse connection within request

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
        http_response_code(500);
        exit("A database error occurred. Please try again later.");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ─── CSRF Helpers ─────────────────────────────────────────────────────────
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

// ─── Login Rate Limiting ──────────────────────────────────────────────────
// Simple DB-based rate limit: max 5 attempts per email per 15 minutes
function rate_limit_check(mysqli $conn, string $email): bool {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?"
    );
    if (!$stmt) return false; // table may not exist yet — fail open
    $stmt->bind_param("ss", $email, $window);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count >= 5;
}

function rate_limit_record(mysqli $conn, string $email): void {
    $stmt = $conn->prepare("INSERT INTO login_attempts (email) VALUES (?)");
    if (!$stmt) return;
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
}
