CREATE DATABASE IF NOT EXISTS scentology;
USE scentology;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    phone VARCHAR(50) DEFAULT '',
    city VARCHAR(120) DEFAULT '',
    bio TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(150) NOT NULL,
    city VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    inventory_notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shops_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (full_name, email, password_hash)
SELECT 'Demo User', 'demo@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'demo@scentology.com');

INSERT INTO users (full_name, email, password_hash)
SELECT 'Alice Fragrance', 'alice@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'alice@scentology.com');

INSERT INTO users (full_name, email, password_hash)
SELECT 'Bob Niche', 'bob@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'bob@scentology.com');

INSERT INTO profiles (user_id, phone, city, bio)
SELECT u.id, '0123456789', 'Dhaka', 'Demo profile for testing'
FROM users u
WHERE u.email = 'demo@scentology.com'
AND NOT EXISTS (SELECT 1 FROM profiles p WHERE p.user_id = u.id);

INSERT INTO profiles (user_id, phone, city, bio)
SELECT id, '01711000001', 'Dhaka', 'Lover of floral and citrus notes.' FROM users WHERE email = 'alice@scentology.com'
ON DUPLICATE KEY UPDATE city='Dhaka';

INSERT INTO profiles (user_id, phone, city, bio)
SELECT id, '01711000002', 'Chittagong', 'Collector of rare Ouds and Attars.' FROM users WHERE email = 'bob@scentology.com'
ON DUPLICATE KEY UPDATE city='Chittagong';

INSERT INTO shops (shop_name, city, address, inventory_notes, created_by)
SELECT 'Scent Paradise', 'Dhaka', 'Gulshan 1 Avenue', 'Dior, Chanel, Tom Ford Private Blend', id FROM users WHERE email = 'alice@scentology.com'
WHERE NOT EXISTS (SELECT 1 FROM shops WHERE shop_name = 'Scent Paradise');

INSERT INTO shops (shop_name, city, address, inventory_notes, created_by)
SELECT 'Oud Master', 'Sylhet', 'Zindabazar, 3rd Floor', 'Pure Oud, Attard, Middle eastern collections', id FROM users WHERE email = 'alice@scentology.com'
WHERE NOT EXISTS (SELECT 1 FROM shops WHERE shop_name = 'Oud Master');

INSERT INTO shops (shop_name, city, address, inventory_notes, created_by)
SELECT 'Niche Vault', 'Dhaka', 'Banani 11', 'Xerjoff, Amouage, Roja Parfums, Creed', id FROM users WHERE email = 'bob@scentology.com'
WHERE NOT EXISTS (SELECT 1 FROM shops WHERE shop_name = 'Niche Vault');
