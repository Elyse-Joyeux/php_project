-- ─── UserSpace: Admin Setup ──────────────────────────────────────────────────

-- 1. Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add created_at to user table if missing
ALTER TABLE user ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3. Insert admin account
--    Email:    admin@userspace.com
--    ⚠️  IMPORTANT: Generate a fresh hash BEFORE running this script.
--       PHP: echo password_hash('YourStrongPassword', PASSWORD_BCRYPT, ['cost' => 12]);
--       Replace the placeholder below. NEVER commit real credentials to version control.
INSERT INTO admin (username, email, password)
VALUES (
    'Admin',
    'admin@userspace.com',
    'REPLACE_THIS_WITH_YOUR_BCRYPT_HASH'
);
