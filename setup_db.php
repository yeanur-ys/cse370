<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

try {
    $pdo = db();
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Schema created successfully\n";
    
    // Read and execute inserts
    $inserts = file_get_contents(__DIR__ . '/database/insert_perfumes.sql');
    $statements = array_filter(array_map('trim', explode(';', $inserts)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Perfume data inserted successfully\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
