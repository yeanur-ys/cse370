<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Check</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .good { color: green; }
        .bad { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Scentology System Check</h1>
    
    <?php
    echo "<h2>1. Configuration Check</h2>";
    
    // Check if files exist
    $files = [
        'app/config.php',
        'app/db.php',
        'app/auth.php',
        'database/schema.sql',
        'database/insert_perfumes.sql'
    ];
    
    foreach ($files as $file) {
        $path = __DIR__ . '/../' . $file;
        if (file_exists($path)) {
            echo "<p class='good'>✓ $file exists</p>";
        } else {
            echo "<p class='bad'>✗ $file NOT FOUND</p>";
        }
    }
    
    echo "<h2>2. Database Connection Check</h2>";
    
    try {
        require_once __DIR__ . '/../app/config.php';
        require_once __DIR__ . '/../app/db.php';
        
        $pdo = db();
        echo "<p class='good'>✓ Database connected successfully</p>";
        
        // List tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables in database: <strong>" . count($tables) . "</strong></p>";
        if (count($tables) > 0) {
            echo "<pre>" . implode("\n", $tables) . "</pre>";
        }
        
        // Check key tables
        foreach (['Brand', 'Perfume', 'User', 'Wishlist', 'Review'] as $table) {
            $exists = in_array($table, $tables);
            $status = $exists ? "<span class='good'>✓ exists</span>" : "<span class='bad'>✗ missing</span>";
            echo "<p>Table <strong>$table</strong>: $status</p>";
        }
        
        // Count records
        echo "<h2>3. Data Check</h2>";
        
        try {
            $counts = [
                'Brand' => $pdo->query("SELECT COUNT(*) FROM Brand")->fetchColumn(),
                'Perfume' => $pdo->query("SELECT COUNT(*) FROM Perfume")->fetchColumn(),
                'User' => $pdo->query("SELECT COUNT(*) FROM User")->fetchColumn(),
            ];
            
            foreach ($counts as $table => $count) {
                echo "<p><strong>$table:</strong> $count records</p>";
            }
        } catch (Exception $e) {
            echo "<p class='warning'>Could not count records: " . $e->getMessage() . "</p>";
        }
        
        // Show sample perfume
        if ($counts['Perfume'] > 0) {
            echo "<h2>4. Sample Perfume</h2>";
            try {
                $sample = $pdo->query("
                    SELECT p.*, b.Brand_Name 
                    FROM Perfume p 
                    JOIN Brand b ON p.Brand_ID = b.Brand_ID 
                    LIMIT 1
                ")->fetch(PDO::FETCH_ASSOC);
                
                echo "<pre>";
                print_r($sample);
                echo "</pre>";
            } catch (Exception $e) {
                echo "<p class='warning'>Error fetching sample: " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='bad'>✗ Database Error: " . $e->getMessage() . "</p>";
        echo "<p class='bad'>Make sure MySQL is running and configured correctly in app/config.php</p>";
    }
    
    echo "<h2>5. File Permissions</h2>";
    $dirs = ['public/assets', 'database', 'app'];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/../' . $dir;
        if (is_writable($path)) {
            echo "<p class='good'>✓ $dir is writable</p>";
        } else {
            echo "<p class='warning'>⚠ $dir may not be writable</p>";
        }
    }
    
    echo "<h2>Actions</h2>";
    echo "<p><a href='setup.php' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>▶ Initialize Database & Import Data</a></p>";
    echo "<p><a href='perfumes.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>▶ View Perfumes</a></p>";
    echo "<p><a href='index.php' style='background: gray; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>▶ Go Home</a></p>";
    ?>
</body>
</html>
