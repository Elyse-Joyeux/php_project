<?php
//  Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'joyeux@2010');
define('DB_NAME', 'userSignUp');

function db_connect(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
        http_response_code(500);
        exit("A database error occurred. Please try again later.");
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

//  CSRF Helpers
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

//  Rate Limiting — only checks count, does NOT record
function rate_limit_check(mysqli $conn, string $email, int $maxAttempts = 5, int $windowSeconds = 900): bool {
    $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);

    $clean = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $clean->bind_param("s", $cutoff);
    $clean->execute();
    $clean->close();

    $check = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?");
    $check->bind_param("ss", $email, $cutoff);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    return $count >= $maxAttempts;
}

// Records a failed login attempt — call only on failure
function rate_limit_record(mysqli $conn, string $email): void {
    $record = $conn->prepare("INSERT INTO login_attempts (email) VALUES (?)");
    $record->bind_param("s", $email);
    $record->execute();
    $record->close();
}

//  Password Reset Tokens
function create_reset_token(mysqli $conn, string $email): ?string {
    // Check user exists
    $check = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) { $check->close(); return null; }
    $check->close();

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    // Delete any existing token for this email
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    $del->close();

    $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $email, $token, $expires);
    $ins->execute();
    $ins->close();

    return $token;
}

function verify_reset_token(mysqli $conn, string $token): ?string {
    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ? AND used = 0");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();
    return $email ?: null;
}

function consume_reset_token(mysqli $conn, string $token): void {
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
}

//  Activity Logging
function log_activity(mysqli $conn, ?int $userId, string $action, ?string $details = null): bool {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("iss", $userId, $action, $details);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

//  Password Verification
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

//  User Helpers
function get_user_by_id(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare(
        "SELECT id, fname, lname, email, username, gender, student_id, cohort, track, phone, bio, avatar_url, status, role, created_at, updated_at
         FROM user WHERE id = ?"
    );
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function update_user(mysqli $conn, int $id, array $updates): bool {
    $allowed_fields = ['fname', 'lname', 'email', 'username', 'gender', 'student_id', 'cohort', 'track', 'phone', 'bio', 'avatar_url', 'status'];
    $set_clauses = [];
    $params      = [];
    $types       = '';

    foreach ($updates as $field => $value) {
        if (!in_array($field, $allowed_fields, true)) continue;
        $set_clauses[] = "$field = ?";
        $params[]      = $value;
        $types        .= is_int($value) ? 'i' : 's';
    }

    if (empty($set_clauses)) return false;

    $params[] = $id;
    $types   .= 'i';

    $query = "UPDATE user SET " . implode(", ", $set_clauses) . " WHERE id = ?";
    $stmt  = $conn->prepare($query);
    if (!$stmt) return false;
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
