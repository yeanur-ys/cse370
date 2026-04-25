CREATE DATABASE IF NOT EXISTS scentology;
USE scentology;

DROP TABLE IF EXISTS Trade;
DROP TABLE IF EXISTS Listing;
DROP TABLE IF EXISTS shops;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS Seller;
DROP TABLE IF EXISTS Shop;
DROP TABLE IF EXISTS Profile;
DROP TABLE IF EXISTS User;

CREATE TABLE IF NOT EXISTS User (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_Name VARCHAR(120) NOT NULL,
    Email VARCHAR(190) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Collection TEXT -- Stores the basic user's collection items list
);

CREATE TABLE IF NOT EXISTS Profile (
    User_ID INT NOT NULL PRIMARY KEY,
    Number VARCHAR(50) DEFAULT '',
    City VARCHAR(120) DEFAULT '',
    BIO TEXT,
    CONSTRAINT fk_profile_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Seller (
    User_ID INT NOT NULL PRIMARY KEY,
    Total_Sell INT DEFAULT 0,
    CONSTRAINT fk_seller_profile FOREIGN KEY (User_ID) REFERENCES Profile(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Shop (
    Shop_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NULL, -- Tracks which seller owns it explicitly based on the business logic constraint
    Shop_Name VARCHAR(150) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    Latitude DECIMAL(10, 7) NULL,
    Longitude DECIMAL(10, 7) NULL,
    Stock TEXT,
    CONSTRAINT fk_shop_seller FOREIGN KEY (User_ID) REFERENCES Seller(User_ID) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Listing (
    Listing_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL, -- Only sellers post these
    Item_Name VARCHAR(200) NOT NULL,
    Quantity INT DEFAULT 1,
    Price DECIMAL(10,2) NOT NULL,
    Item_Condition VARCHAR(100) DEFAULT 'New',
    Status VARCHAR(50) DEFAULT 'Available',
    CONSTRAINT fk_listing_seller FOREIGN KEY (User_ID) REFERENCES Seller(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Trade (
    Trade_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL, -- Initiated by user
    Offering VARCHAR(255) NOT NULL,
    Desired VARCHAR(255) NOT NULL,
    Status VARCHAR(50) DEFAULT 'Pending',
    CONSTRAINT fk_trade_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE
);

INSERT INTO User (User_Name, Email, Password)
SELECT 'Demo User', 'demo@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'demo@scentology.com');

INSERT INTO User (User_Name, Email, Password)
SELECT 'Alice Fragrance', 'alice@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'alice@scentology.com');

INSERT INTO User (User_Name, Email, Password)
SELECT 'Bob Niche', 'bob@scentology.com', '$2y$10$pvNExjAztfhNMEb5NdAfjOtVFhSKps1GRZkwqpmYchKQ56G3.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'bob@scentology.com');

INSERT INTO Profile (User_ID, Number, City, BIO)
SELECT User_ID, '0123456789', 'Dhaka', 'Demo profile for testing'
FROM User u
WHERE u.Email = 'demo@scentology.com'
AND NOT EXISTS (SELECT 1 FROM Profile p WHERE p.User_ID = u.User_ID);

INSERT INTO Profile (User_ID, Number, City, BIO)
SELECT User_ID, '01711000001', 'Dhaka', 'Lover of floral and citrus notes.' FROM User WHERE Email = 'alice@scentology.com'
ON DUPLICATE KEY UPDATE City='Dhaka';

INSERT INTO Profile (User_ID, Number, City, BIO)
SELECT User_ID, '01711000002', 'Chittagong', 'Collector of rare Ouds and Attars.' FROM User WHERE Email = 'bob@scentology.com'
ON DUPLICATE KEY UPDATE City='Chittagong';

INSERT INTO Shop (Shop_Name, Address, Stock)
SELECT 'Scent Paradise', 'Gulshan 1 Avenue, Dhaka', 'Dior, Chanel, Tom Ford Private Blend' 
WHERE NOT EXISTS (SELECT 1 FROM Shop WHERE Shop_Name = 'Scent Paradise');

INSERT INTO Shop (Shop_Name, Address, Stock)
SELECT 'Oud Master', 'Zindabazar, 3rd Floor, Sylhet', 'Pure Oud, Attard, Middle eastern collections' 
WHERE NOT EXISTS (SELECT 1 FROM Shop WHERE Shop_Name = 'Oud Master');

INSERT INTO Shop (Shop_Name, Address, Stock)
SELECT 'Niche Vault', 'Banani 11, Dhaka', 'Xerjoff, Amouage, Roja Parfums, Creed' 
WHERE NOT EXISTS (SELECT 1 FROM Shop WHERE Shop_Name = 'Niche Vault');

