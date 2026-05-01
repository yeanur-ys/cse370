<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config.php';

echo "<h1>🚀 Database Initialization</h1>";

try {
    // Connect to MySQL (without selecting a database first)
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        DB_HOST,
        DB_PORT
    );
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "<p>✓ Database '" . DB_NAME . "' ready</p>";
    
    // Select database
    $pdo->exec("USE " . DB_NAME);
    echo "<p>✓ Database selected</p>";
    
    // Read schema file
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    echo "<p>✓ Schema file loaded</p>";
    
    // Split by semicolon and execute
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    $executedCount = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executedCount++;
            } catch (PDOException $e) {
                // Ignore "already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<p style='color: orange;'>Warning: " . substr($statement, 0, 40) . "... - " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p>✓ Schema statements executed: $executedCount</p>";
    
    // Read and execute inserts
    $insertsFile = __DIR__ . '/../database/insert_perfumes.sql';
    if (file_exists($insertsFile)) {
        $inserts = file_get_contents($insertsFile);
        $statements = array_filter(array_map('trim', explode(';', $inserts)));
        $insertCount = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && stripos($statement, 'INSERT') !== false) {
                try {
                    $pdo->exec($statement);
                    $insertCount++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "<p style='color: orange;'>Insert warning: " . substr($statement, 0, 40) . "...</p>";
                    }
                }
            }
        }
        
        echo "<p>✓ Perfume data inserted: $insertCount statements</p>";
    }
    
    // Verify data
    $pdo->exec("USE " . DB_NAME);
    
    $brandCount = $pdo->query("SELECT COUNT(*) as cnt FROM Brand")->fetch()['cnt'];
    $perfumeCount = $pdo->query("SELECT COUNT(*) as cnt FROM Perfume")->fetch()['cnt'];
    $userCount = $pdo->query("SELECT COUNT(*) as cnt FROM User")->fetch()['cnt'];
    
    echo "<h2>✓ Database initialized successfully!</h2>";
    echo "<p><strong>Brands:</strong> $brandCount</p>";
    echo "<p><strong>Perfumes:</strong> $perfumeCount</p>";
    echo "<p><strong>Users:</strong> $userCount</p>";
    
    if ($perfumeCount > 0) {
        echo "<h3>Sample Perfume:</h3>";
        $sample = $pdo->query("
            SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, b.Brand_Name
            FROM Perfume p 
            JOIN Brand b ON p.Brand_ID = b.Brand_ID 
            LIMIT 1
        ")->fetch();
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    }
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='perfumes.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>✓ View Perfumes</a> ";
    echo "<a href='index.php' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go Home</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
