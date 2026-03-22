-- Run this in phpMyAdmin → select userSignUp database → SQL tab

-- 1. Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add created_at to user table if missing
ALTER TABLE user ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3. Insert admin account
--    Email:    admin@userspace.com
--    Password: admin123
--    ⚠️ Change the password after first login!
INSERT INTO admin (username, email, password)
VALUES (
    'Admin',
    'admin@userspace.com',
    '$2y$10$DTnVobV9plrFu1dCCtM00OraPNAHGqg6qEsZm3V4H0ajUgV.S/N7C'
);
