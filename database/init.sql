CREATE DATABASE IF NOT EXISTS scentology;
USE scentology;

-- Disable foreign key checks during schema reset
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables in correct order (reverse of creation, respecting foreign keys)
DROP TABLE IF EXISTS Dependent;
DROP TABLE IF EXISTS Collection;
DROP TABLE IF EXISTS Trade;
DROP TABLE IF EXISTS Review;
DROP TABLE IF EXISTS Wishlist;
DROP TABLE IF EXISTS Available;
DROP TABLE IF EXISTS Has_Notes;
DROP TABLE IF EXISTS Listing;
DROP TABLE IF EXISTS Shop;
DROP TABLE IF EXISTS Seller;
DROP TABLE IF EXISTS Profile;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Perfume;
DROP TABLE IF EXISTS Notes;
DROP TABLE IF EXISTS Brand;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS User (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_Name VARCHAR(120) NOT NULL,
    Email VARCHAR(190) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Profile (
    User_ID INT NOT NULL PRIMARY KEY,
    Number VARCHAR(50) DEFAULT '',
    City VARCHAR(120) DEFAULT '',
    BIO TEXT,
    Curator_Level VARCHAR(50) DEFAULT 'Novice',
    CONSTRAINT fk_profile_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Dependent (
    Dependent_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Name VARCHAR(150) NOT NULL,
    Relationship VARCHAR(100) NOT NULL,
    CONSTRAINT fk_dependent_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Seller (
    User_ID INT NOT NULL PRIMARY KEY,
    Total_Sell INT DEFAULT 0,
    CONSTRAINT fk_seller_profile FOREIGN KEY (User_ID) REFERENCES Profile(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Shop (
    Shop_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NULL, 
    Shop_Name VARCHAR(150) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    Latitude DECIMAL(10, 7) NULL,
    Longitude DECIMAL(10, 7) NULL,
    Stock TEXT,
    CONSTRAINT fk_shop_seller FOREIGN KEY (User_ID) REFERENCES Seller(User_ID) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Brand (
    Brand_ID INT AUTO_INCREMENT PRIMARY KEY,
    Brand_Name VARCHAR(150) NOT NULL UNIQUE,
    No_of_Perfumes INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Perfume (
    Perfume_ID INT AUTO_INCREMENT PRIMARY KEY,
    Brand_ID INT NOT NULL,
    Name VARCHAR(200) NOT NULL,
    Release_Year INT,
    Note_ID INT,
    Price DECIMAL(10,2) DEFAULT 0,
    Image_URL VARCHAR(500),
    CONSTRAINT fk_perfume_brand FOREIGN KEY (Brand_ID) REFERENCES Brand(Brand_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Listing (
    Listing_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL, 
    Perfume_ID INT NOT NULL,
    Quantity INT DEFAULT 1,
    Price DECIMAL(10,2) NOT NULL,
    Item_Condition VARCHAR(100) DEFAULT 'New',
    Status VARCHAR(50) DEFAULT 'Available',
    Purchased_By_User_ID INT NULL,
    Purchased_At TIMESTAMP NULL,
    CONSTRAINT fk_listing_seller FOREIGN KEY (User_ID) REFERENCES Seller(User_ID) ON DELETE CASCADE,
    CONSTRAINT fk_listing_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Notes (
    Note_ID INT AUTO_INCREMENT PRIMARY KEY,
    Note_Name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS Trade (
    Trade_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL, 
    Offering_Perfume_ID INT NOT NULL,
    Quantity INT DEFAULT 1,
    Desired_Note_ID INT NULL,
    Desired_Perfume_ID INT NULL,
    Status VARCHAR(50) DEFAULT 'Pending',
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Accepted_By_User_ID INT NULL,
    Accepted_At TIMESTAMP NULL,
    CONSTRAINT fk_trade_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE,
    CONSTRAINT fk_trade_offering FOREIGN KEY (Offering_Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    CONSTRAINT fk_trade_desired_note FOREIGN KEY (Desired_Note_ID) REFERENCES Notes(Note_ID) ON DELETE CASCADE,
    CONSTRAINT fk_trade_desired_perfume FOREIGN KEY (Desired_Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Collection (
    Collection_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Perfume_ID INT NOT NULL,
    Purchase_Date DATE NULL,
    Notes VARCHAR(255) NULL,
    Added_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_collection_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE,
    CONSTRAINT fk_collection_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_user_perfume (User_ID, Perfume_ID)
);

CREATE TABLE IF NOT EXISTS Has_Notes (
    Perfume_ID INT NOT NULL,
    Note_ID INT NOT NULL,
    PRIMARY KEY (Perfume_ID, Note_ID),
    CONSTRAINT fk_has_notes_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    CONSTRAINT fk_has_notes_notes FOREIGN KEY (Note_ID) REFERENCES Notes(Note_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Available (
    Perfume_ID INT NOT NULL,
    Shop_ID INT NOT NULL,
    PRIMARY KEY (Perfume_ID, Shop_ID),
    CONSTRAINT fk_available_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    CONSTRAINT fk_available_shop FOREIGN KEY (Shop_ID) REFERENCES Shop(Shop_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Wishlist (
    Perfume_ID INT NOT NULL,
    User_ID INT NOT NULL,
    PRIMARY KEY (Perfume_ID, User_ID),
    CONSTRAINT fk_wishlist_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Review (
    Review_ID INT AUTO_INCREMENT PRIMARY KEY,
    Perfume_ID INT NOT NULL,
    User_ID INT NOT NULL,
    Rating INT CHECK (Rating >= 1 AND Rating <= 5),
    Comment TEXT,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_review (Perfume_ID, User_ID)
);

INSERT INTO User (User_Name, Email, Password)
SELECT 'Demo User', 'demo@scentology.com', '\\\.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'demo@scentology.com');

INSERT INTO User (User_Name, Email, Password)
SELECT 'Alice Fragrance', 'alice@scentology.com', '\\\.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'alice@scentology.com');

INSERT INTO User (User_Name, Email, Password)
SELECT 'Bob Niche', 'bob@scentology.com', '\\\.vMR2'
WHERE NOT EXISTS (SELECT 1 FROM User WHERE Email = 'bob@scentology.com');

INSERT INTO Profile (User_ID, Number, City, BIO, Curator_Level)
SELECT User_ID, '0123456789', 'Dhaka', 'Demo profile for testing', 'Expert'
FROM User u
WHERE u.Email = 'demo@scentology.com'
AND NOT EXISTS (SELECT 1 FROM Profile p WHERE p.User_ID = u.User_ID);

INSERT INTO Profile (User_ID, Number, City, BIO, Curator_Level)
SELECT User_ID, '01711000001', 'Dhaka', 'Lover of floral and citrus notes.', 'Novice' FROM User WHERE Email = 'alice@scentology.com'
ON DUPLICATE KEY UPDATE City='Dhaka';

INSERT INTO Profile (User_ID, Number, City, BIO, Curator_Level)
SELECT User_ID, '01711000002', 'Chittagong', 'Collector of rare Ouds and Attars.', 'Master' FROM User WHERE Email = 'bob@scentology.com'
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

CREATE TABLE IF NOT EXISTS Purchases (
    Purchase_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Perfume_ID INT NOT NULL,
    Purchase_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Price DECIMAL(10, 2),
    Quantity INT DEFAULT 1,
    CONSTRAINT fk_purchases_user FOREIGN KEY (User_ID) REFERENCES User(User_ID) ON DELETE CASCADE,
    CONSTRAINT fk_purchases_perfume FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE
);

-- Insert perfume note linkages using direct IDs
INSERT IGNORE INTO Has_Notes (Perfume_ID, Note_ID) VALUES
-- Pacific Rock Moss (ID 1)
(1, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lemon' LIMIT 1)),
(1, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sage' LIMIT 1)),
(1, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Geranium' LIMIT 1)),
(1, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),

-- Side Effect (ID 2)
(2, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cinnamon' LIMIT 1)),
(2, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tobacco' LIMIT 1)),
(2, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Saffron' LIMIT 1)),
(2, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(2, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sandalwood' LIMIT 1)),

-- Stronger With You Intensely (ID 3)
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pink Pepper' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Juniper' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Violet' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lavender' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sage' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(3, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),

-- YSL Y (ID 4)
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Apple' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Ginger' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sage' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Juniper' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Geranium' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),
(4, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vetiver' LIMIT 1)),

-- Aventus (ID 5)
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Apple' LIMIT 1)),
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Rose' LIMIT 1)),
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Musk' LIMIT 1)),
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),
(5, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),

-- Bois Imperial (ID 6)
(6, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pepper' LIMIT 1)),
(6, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vetiver' LIMIT 1)),
(6, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),

-- Layton (ID 7)
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Apple' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lavender' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Mandarin' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Geranium' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Violet' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Jasmine' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cardamom' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sandalwood' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pepper' LIMIT 1)),
(7, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),

-- Grand Soir (ID 8)
(8, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange' LIMIT 1)),
(8, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(8, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(8, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),

-- Dior Homme (ID 9)
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lavender' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Iris' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cacao' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Leather' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vetiver' LIMIT 1)),
(9, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),

-- Terre d'Hermes (ID 10)
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange' LIMIT 1)),
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Grapefruit' LIMIT 1)),
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pepper' LIMIT 1)),
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vetiver' LIMIT 1)),
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cedar' LIMIT 1)),
(10, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),

-- Neroli Portofino (ID 11)
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lemon' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Mandarin' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Neroli' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange Blossom' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Jasmine' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(11, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Musk' LIMIT 1)),

-- Soleil Blanc (ID 12)
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cardamom' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tuberose' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Ylang-Ylang' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Jasmine' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Coconut' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(12, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),

-- Cherry Smoke (ID 13)
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cherry' LIMIT 1)),
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Saffron' LIMIT 1)),
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Olive' LIMIT 1)),
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Leather' LIMIT 1)),
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Smoke' LIMIT 1)),
(13, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Wood' LIMIT 1)),

-- Acqua di Gio Profumo (ID 14)
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Marine Notes' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Rosemary' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sage' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Geranium' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Patchouli' LIMIT 1)),
(14, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Incense' LIMIT 1)),

-- Armani Code (ID 15)
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lemon' LIMIT 1)),
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Star Anise' LIMIT 1)),
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Leather' LIMIT 1)),
(15, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tobacco' LIMIT 1)),

-- Herbes Troublantes (ID 16)
(16, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Mint' LIMIT 1)),
(16, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lemon' LIMIT 1)),
(16, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Green Tea' LIMIT 1)),
(16, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Musk' LIMIT 1)),

-- Outlands (ID 17)
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Lemon' LIMIT 1)),
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Spices' LIMIT 1)),
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Frankincense' LIMIT 1)),
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(17, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Woods' LIMIT 1)),

-- Tonka Sarrapia (ID 18)
(18, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Almond' LIMIT 1)),
(18, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Spices' LIMIT 1)),
(18, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),
(18, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(18, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tobacco' LIMIT 1)),

-- Allure Homme Sport (ID 19)
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Sea Notes' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pepper' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Neroli' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cedar' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tonka Bean' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vanilla' LIMIT 1)),
(19, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Musk' LIMIT 1)),

-- Imagination (ID 20)
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Bergamot' LIMIT 1)),
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange' LIMIT 1)),
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tea' LIMIT 1)),
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Neroli' LIMIT 1)),
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Ginger' LIMIT 1)),
(20, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Guaiac Wood' LIMIT 1)),

-- Meteore (ID 21)
(21, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Mandarin' LIMIT 1)),
(21, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange' LIMIT 1)),
(21, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Pepper' LIMIT 1)),
(21, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Neroli' LIMIT 1)),
(21, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Vetiver' LIMIT 1)),

-- Symphony (ID 22)
(22, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Grapefruit' LIMIT 1)),
(22, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Ginger' LIMIT 1)),
(22, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Musk' LIMIT 1)),

-- Le Sables Roses (ID 23)
(23, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Rose' LIMIT 1)),
(23, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Oud' LIMIT 1)),
(23, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(23, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Saffron' LIMIT 1)),

-- The One (ID 24)
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Grapefruit' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Coriander' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Basil' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Ginger' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cardamom' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Orange Blossom' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Tobacco' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Amber' LIMIT 1)),
(24, (SELECT Note_ID FROM Notes WHERE Note_Name = 'Cedar' LIMIT 1));
-- Insert Brands
INSERT INTO Brand (Brand_Name) VALUES 
('Goldfield & Banks'),
('Initio'),
('Giorgio Armani'),
('Yves Saint Laurent'),
('Creed'),
('Essential Parfums'),
('Parfums de Marley'),
('Maison Francis Kurkdjian'),
('Dior'),
('Hermes'),
('Tom Ford'),
('Guerlan'),
('Amouage'),
('Louis Vuitton'),
('Dolce & Gabbana')
ON DUPLICATE KEY UPDATE Brand_Name = Brand_Name;

-- Insert Notes
INSERT INTO Notes (Note_Name) VALUES 
('Lemon'),
('Sage'),
('Geranium'),
('Moss'),
('Amber'),
('Rum'),
('Cinnamon'),
('Tobacco'),
('Saffron'),
('Vanilla'),
('Sandalwood'),
('Pink Pepper'),
('Juniper'),
('Violet'),
('Toffee'),
('Lavender'),
('Tonka Bean'),
('Suede'),
('Apple'),
('Ginger'),
('Bergamot'),
('Juniper'),
('Amberwood'),
('Vetiver'),
('Thai Basil'),
('Pepper'),
('Haitian Vetiver'),
('Akigalawood'),
('Ambroxan'),
('Patchouli'),
('Mandarin'),
('Jasmine'),
('Cardamom'),
('Orange'),
('Labdanum'),
('Benzoin'),
('Iris'),
('Cacao'),
('Leather'),
('Grapefruit'),
('Pelargonium'),
('Cedar'),
('Lemon'),
('Orange Blossom'),
('Musk'),
('Pistachio'),
('Tuberose'),
('Ylang-Ylang'),
('Coconut'),
('Cherry'),
('Olive'),
('Chinese Osmanthus'),
('Smoke'),
('Wood'),
('Cypriol'),
('Marine Notes'),
('Rosemary'),
('Incense'),
('Olive Blossom'),
('Star Anise'),
('Mint'),
('Green Tea'),
('Spices'),
('Frankincense'),
('Woods'),
('Almond'),
('Aldehydes'),
('Sea Notes'),
('Neroli'),
('Citrus Accord'),
('Tea'),
('Guaiac Wood'),
('Rose'),
('Oud'),
('Saffron'),
('Coriander'),
('Basil'),
('Orange Blossom'),
('Tobacco'),
('Amber'),
('Cedar')
ON DUPLICATE KEY UPDATE Note_Name = Note_Name;

-- Insert Perfumes with Prices, Release Years, and Image URLs
INSERT INTO Perfume (Brand_ID, Name, Release_Year, Price, Image_URL) VALUES
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Goldfield & Banks'), 'Pacific Rock Moss', 2015, 24000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Pacific%20Rock%20Moss.jpg'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Initio'), 'Side Effect', 2019, 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Side%20Effect.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Stronger With You Intensely', 2018, 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Stronger%20With%20You.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Yves Saint Laurent'), 'YSL Y', 2017, 14000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/YSL%20Y.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Creed'), 'Aventus', 2010, 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Creed%20Aventus.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Essential Parfums'), 'Bois Imperial', 2016, 12000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Bois%20Imperial.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Parfums de Marley'), 'Layton', 2016, 36000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Layton.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Maison Francis Kurkdjian'), 'Grand Soir', 2016, 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Grand%20Soir.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dior'), 'Dior Homme', 2005, 16000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Dior%20Homme.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Hermes'), 'Terre d''Hermes', 2006, 15000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Terre%20d_Hermes.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Neroli Portofino', 2011, 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Neroli%20Portofino.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Soleil Blanc', 2015, 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Soleil%20Blanc.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Cherry Smoke', 2019, 55000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Cherry%20Smoke.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Acqua di Gio Profumo', 2015, 16000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Acqua%20di%20Gio%20Profumo.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Armani Code', 2004, 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Armani%20Code.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Guerlan'), 'Herbes Troublantes', 2013, 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Herbes%20Troublantes.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Amouage'), 'Outlands', 2018, 51000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Outlands.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Guerlan'), 'Tonka Sarrapia', 2014, 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Tonka%20Sarrapia.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dior'), 'Allure Homme Sport', 2007, 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Allure%20Homme%20Sport.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Imagination', 2015, 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Imagination.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Meteore', 2016, 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Meteore.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Symphony', 2018, 60000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Symphony.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Le Sables Roses', 2018, 50000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Le%20Sables%20Roses.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dolce & Gabbana'), 'The One', 2003, 11000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/The%20One.webp');

-- Link Perfumes to their Notes
INSERT INTO Has_Notes (Perfume_ID, Note_ID) 
SELECT p.Perfume_ID, n.Note_ID FROM Perfume p JOIN Notes n ON
(p.Name = 'Pacific Rock Moss' AND n.Note_Name IN ('Lemon', 'Sage', 'Geranium', 'Moss', 'Amber'))
   OR (p.Name = 'Side Effect' AND n.Note_Name IN ('Rum', 'Cinnamon', 'Tobacco', 'Saffron', 'Vanilla', 'Sandalwood'))
   OR (p.Name = 'Stronger With You Intensely' AND n.Note_Name IN ('Pink Pepper', 'Juniper', 'Violet', 'Toffee', 'Cinnamon', 'Lavender', 'Sage', 'Vanilla', 'Amber', 'Tonka Bean', 'Suede'))
   OR (p.Name = 'YSL Y' AND n.Note_Name IN ('Apple', 'Ginger', 'Bergamot', 'Sage', 'Juniper', 'Geranium', 'Amberwood', 'Tonka Bean', 'Vetiver'))
   OR (p.Name = 'Aventus' AND n.Note_Name IN ('Apple', 'Bergamot', 'Pineapple', 'Black Currant', 'Birch', 'Patchouli', 'Moroccan Jasmine', 'Rose', 'Musk', 'Oakmoss', 'Ambergris', 'Vanilla'))
   OR (p.Name = 'Bois Imperial' AND n.Note_Name IN ('Thai Basil', 'Pepper', 'Haitian Vetiver', 'Akigalawood', 'Ambroxan', 'Patchouli'))
   OR (p.Name = 'Layton' AND n.Note_Name IN ('Apple', 'Lavender', 'Bergamot', 'Mandarin', 'Geranium', 'Violet', 'Jasmine', 'Vanilla', 'Cardamom', 'Sandalwood', 'Pepper', 'Patchouli'))
   OR (p.Name = 'Grand Soir' AND n.Note_Name IN ('Orange', 'Labdanum', 'Benzoin', 'Vanilla', 'Amber', 'Tonka Bean'))
   OR (p.Name = 'Dior Homme' AND n.Note_Name IN ('Lavender', 'Bergamot', 'Iris', 'Cacao', 'Leather', 'Vetiver', 'Patchouli'))
   OR (p.Name = 'Terre d''Hermes' AND n.Note_Name IN ('Orange', 'Grapefruit', 'Pepper', 'Pelargonium', 'Vetiver', 'Cedar', 'Patchouli', 'Benzoin'))
   OR (p.Name = 'Neroli Portofino' AND n.Note_Name IN ('Bergamot', 'Lemon', 'Mandarin', 'Neroli', 'Orange Blossom', 'Jasmine', 'Amber', 'Musk'))
   OR (p.Name = 'Soleil Blanc' AND n.Note_Name IN ('Pistachio', 'Bergamot', 'Cardamom', 'Tuberose', 'Ylang-Ylang', 'Jasmine', 'Coconut', 'Amber', 'Tonka Bean'))
   OR (p.Name = 'Cherry Smoke' AND n.Note_Name IN ('Cherry', 'Saffron', 'Olive', 'Leather', 'Chinese Osmanthus', 'Smoke', 'Wood', 'Cypriol'))
   OR (p.Name = 'Acqua di Gio Profumo' AND n.Note_Name IN ('Bergamot', 'Marine Notes', 'Rosemary', 'Sage', 'Geranium', 'Patchouli', 'Incense'))
   OR (p.Name = 'Armani Code' AND n.Note_Name IN ('Lemon', 'Bergamot', 'Olive Blossom', 'Star Anise', 'Tonka Bean', 'Leather', 'Tobacco'))
   OR (p.Name = 'Herbes Troublantes' AND n.Note_Name IN ('Mint', 'Lemon', 'Herbs', 'Green Tea', 'Musk'))
   OR (p.Name = 'Outlands' AND n.Note_Name IN ('Lemon', 'Bergamot', 'Spices', 'Frankincense', 'Amber', 'Woods'))
   OR (p.Name = 'Tonka Sarrapia' AND n.Note_Name IN ('Almond', 'Spices', 'Tonka Bean', 'Vanilla', 'Tobacco'))
   OR (p.Name = 'Allure Homme Sport' AND n.Note_Name IN ('Orange', 'Aldehydes', 'Sea Notes', 'Pepper', 'Neroli', 'Cedar', 'Tonka Bean', 'Vanilla', 'Musk'))
   OR (p.Name = 'Imagination' AND n.Note_Name IN ('Citron', 'Bergamot', 'Orange', 'Tea', 'Neroli', 'Ginger', 'Ambroxan', 'Guaiac Wood'))
   OR (p.Name = 'Meteore' AND n.Note_Name IN ('Mandarin', 'Orange', 'Pepper', 'Neroli', 'Vetiver'))
   OR (p.Name = 'Symphony' AND n.Note_Name IN ('Grapefruit', 'Ginger', 'Citrus Accord', 'Musk'))
   OR (p.Name = 'Le Sables Roses' AND n.Note_Name IN ('Rose', 'Oud', 'Amber', 'Saffron'))
    OR (p.Name = 'The One' AND n.Note_Name IN ('Grapefruit', 'Coriander', 'Basil', 'Ginger', 'Cardamom', 'Orange Blossom', 'Tobacco', 'Amber', 'Cedar'))
ON DUPLICATE KEY UPDATE Note_ID = VALUES(Note_ID);