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
