
CREATE DATABASE IF NOT EXISTS userSignUp
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE userSignUp;

--  users 
CREATE TABLE IF NOT EXISTS user (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fname        VARCHAR(80)  NOT NULL,
    lname        VARCHAR(80)  NOT NULL,
    email        VARCHAR(180) UNIQUE NOT NULL,
    username     VARCHAR(50)  UNIQUE NOT NULL,
    gender       ENUM('male','female','other') DEFAULT 'other',
    password     VARCHAR(255) NOT NULL,
    --  Student-specific fields 
    student_id   VARCHAR(30)  UNIQUE DEFAULT NULL,  -- e.g. RCA/2024/001
    cohort       VARCHAR(50)  DEFAULT NULL,          -- e.g. "Year 2 · 2024"
    track        VARCHAR(80)  DEFAULT NULL,          -- e.g. "Software Development"
    phone        VARCHAR(20)  DEFAULT NULL,
    bio          TEXT         DEFAULT NULL,
    avatar_url   VARCHAR(500) DEFAULT NULL,
    --  Status 
    status       ENUM('active','suspended','graduated') DEFAULT 'active',
    role         ENUM('student','admin') DEFAULT 'student',
    --  Timestamps 
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email    (email),
    INDEX idx_username (username),
    INDEX idx_status   (status),
    INDEX idx_cohort   (cohort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--  admins 
CREATE TABLE IF NOT EXISTS admin (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL,
    email        VARCHAR(180) UNIQUE NOT NULL,
    password     VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--  login_attempts (rate-limiting) 
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(180) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--  activity_log 
CREATE TABLE IF NOT EXISTS activity_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    action       VARCHAR(200) NOT NULL,
    logged_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════
--  Seed Admin Account
--  1. Generate hash in PHP:
--     php -r "echo password_hash('YourStrongPassword!1', PASSWORD_BCRYPT, ['cost'=>12]);"
--  2. Replace REPLACE_THIS_WITH_BCRYPT_HASH below.
--  3. NEVER commit real credentials to version control.
-- ═══════════════════════════════════════════════════════════════
INSERT IGNORE INTO admin (username, email, password) VALUES (
    'Admin',
    'schoollyse12@gmail.com',
    'REPLACE_THIS_WITH_BCRYPT_HASH'
);
