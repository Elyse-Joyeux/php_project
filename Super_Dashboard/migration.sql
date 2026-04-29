-- Run this in phpMyAdmin or MySQL CLI to add the password reset table
-- and a 'details' column to activity_log if it does not already exist.

USE userSignUp;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(180) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at TIMESTAMP    NOT NULL,
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add 'details' column to activity_log if missing (safe to run twice)
ALTER TABLE activity_log
    ADD COLUMN IF NOT EXISTS details TEXT DEFAULT NULL AFTER action;

-- Add 'logged_at' alias column if your table uses 'created_at' instead
-- (the dashboard queries use logged_at — make sure one of these exists)
ALTER TABLE activity_log
    ADD COLUMN IF NOT EXISTS logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER details;
