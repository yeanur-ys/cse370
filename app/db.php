<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // MySQL: 1049 = Unknown database
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            $safeDbName = htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8');
            http_response_code(500);
            echo "<!doctype html><html><head><meta charset='utf-8'><title>Database not initialized</title>"
                . "<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:24px;max-width:900px;margin:0 auto;}"
                . ".card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;}"
                . "a{color:#2563eb;text-decoration:none;font-weight:600}</style></head><body>"
                . "<div class='card'><h1>Database not initialized</h1>"
                . "<p>The MySQL database <strong>{$safeDbName}</strong> doesn't exist yet.</p>"
                . "<p><a href='init-db.php'>Click here to initialize the database</a></p>"
                . "<p style='color:#6b7280'>If you're using XAMPP, make sure MySQL is running.</p>"
                . "</div></body></html>";
            exit;
        }

        throw $e;
    }

    return $pdo;
}
