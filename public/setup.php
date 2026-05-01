<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

echo "<h1>🔧 Database Setup Tool</h1>";

try {
    $pdo = db();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Tables found:</strong> " . count($tables) . "</p>";
    echo "<pre>" . implode("\n", $tables) . "</pre>";
    
    // Check perfume count
    $perfumeCount = $pdo->query("SELECT COUNT(*) FROM Perfume")->fetchColumn();
    echo "<p><strong>Perfumes in database:</strong> $perfumeCount</p>";
    
    if ($perfumeCount == 0) {
        echo "<h2>Setting up perfume data...</h2>";
        
        // Read and execute schema
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                    // Skip duplicate key errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
        
        echo "<p style='color: green;'>✓ Schema applied</p>";
        
        // Read and execute inserts
        $inserts = file_get_contents(__DIR__ . '/database/insert_perfumes.sql');
        $statements = array_filter(array_map('trim', explode(';', $inserts)));
        
        $count = 0;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $count++;
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>Warning executing: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<p style='color: green;'>✓ Inserted $count statements</p>";
        
        // Verify
        $perfumeCount = $pdo->query("SELECT COUNT(*) FROM Perfume")->fetchColumn();
        echo "<p><strong>Perfumes now in database:</strong> $perfumeCount</p>";
    }
    
    // Display sample perfume
    if ($perfumeCount > 0) {
        $sample = $pdo->query("SELECT p.*, b.Brand_Name FROM Perfume p JOIN Brand b ON p.Brand_ID = b.Brand_ID LIMIT 1")->fetch();
        echo "<h2>Sample Perfume:</h2>";
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
