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

-- Insert Perfumes with Prices and Image URLs
INSERT INTO Perfume (Brand_ID, Name, Price, Image_URL) VALUES
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Goldfield & Banks'), 'Pacific Rock Moss', 24000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Pacific%20Rock%20Moss.jpg'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Initio'), 'Side Effect', 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Side%20Effect.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Stronger With You Intensely', 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Stronger%20With%20You.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Yves Saint Laurent'), 'YSL Y', 14000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/YSL%20Y.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Creed'), 'Aventus', 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Creed%20Aventus.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Essential Parfums'), 'Bois Imperial', 12000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Bois%20Imperial.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Parfums de Marley'), 'Layton', 36000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Layton.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Maison Francis Kurkdjian'), 'Grand Soir', 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Grand%20Soir.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dior'), 'Dior Homme', 16000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Dior%20Homme.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Hermes'), 'Terre d''Hermes', 15000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Terre%20d_Hermes.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Neroli Portofino', 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Neroli%20Portofino.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Soleil Blanc', 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Soleil%20Blanc.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Tom Ford'), 'Cherry Smoke', 55000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Cherry%20Smoke.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Acqua di Gio Profumo', 16000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Acqua%20di%20Gio%20Profumo.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Giorgio Armani'), 'Armani Code', 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Armani%20Code.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Guerlan'), 'Herbes Troublantes', 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Herbes%20Troublantes.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Amouage'), 'Outlands', 51000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Outlands.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Guerlan'), 'Tonka Sarrapia', 42000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Tonka%20Sarrapia.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dior'), 'Allure Homme Sport', 13000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Allure%20Homme%20Sport.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Imagination', 38000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Imagination.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Meteore', 40000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Meteore.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Symphony', 60000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Symphony.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Louis Vuitton'), 'Le Sables Roses', 50000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/Le%20Sables%20Roses.webp'),
((SELECT Brand_ID FROM Brand WHERE Brand_Name = 'Dolce & Gabbana'), 'The One', 11000, 'https://raw.githubusercontent.com/yeanur-ys/cse370/refs/heads/main/public/assets/images/The%20One.webp');

-- Link Perfumes to their Notes
INSERT INTO Has_Notes (Perfume_ID, Note_ID) 
SELECT p.Perfume_ID, n.Note_ID FROM Perfume p, Notes n
WHERE (p.Name = 'Pacific Rock Moss' AND n.Note_Name IN ('Lemon', 'Sage', 'Geranium', 'Moss', 'Amber'))
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
ON DUPLICATE KEY UPDATE Note_ID = Note_ID;
