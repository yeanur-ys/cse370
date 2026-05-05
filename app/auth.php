<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_logged_in(): bool
{
    ensure_session_started();
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    ensure_session_started();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function login_user(int $userId): void
{
    ensure_session_started();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT User_ID as id, User_Name as full_name, Email as email, Password as password_hash FROM User WHERE Email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function create_user(string $fullName, string $email, string $password, bool $isSeller = false): int
{
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('INSERT INTO User (User_Name, Email, Password) VALUES (:full_name, :email, :password_hash)');
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        $userId = (int) $pdo->lastInsertId();

        $profileStmt = $pdo->prepare('INSERT INTO Profile (User_ID, Number, City, BIO) VALUES (:user_id, "", "", "")');
        $profileStmt->execute(['user_id' => $userId]);

        if ($isSeller) {
            $sellerStmt = $pdo->prepare('INSERT INTO Seller (User_ID, Total_Sell) VALUES (:user_id, 0)');
            $sellerStmt->execute(['user_id' => $userId]);
        }

        $pdo->commit();

        return $userId;
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function get_user_with_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT u.User_ID as id, u.User_Name as full_name, u.Email as email, u.Created_at as created_at,
                p.Number as phone, p.City as city, p.BIO as bio, 
                s.User_ID as is_seller, s.Total_Sell as total_sell
         FROM User u
         LEFT JOIN Profile p ON p.User_ID = u.User_ID
         LEFT JOIN Seller s ON s.User_ID = u.User_ID
         WHERE u.User_ID = :user_id
         LIMIT 1'
    );

    $stmt->execute(['user_id' => $userId]);
    $result = $stmt->fetch();

    return $result ?: null;
}

function update_profile(int $userId, string $fullName, string $phone, string $city, string $bio, string $collection = ''): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $userStmt = $pdo->prepare('UPDATE User SET User_Name = :full_name WHERE User_ID = :id');
        $userStmt->execute([
            'full_name' => $fullName,
            'id' => $userId,
        ]);

        $profileStmt = $pdo->prepare(
            'UPDATE Profile SET Number = :phone, City = :city, BIO = :bio WHERE User_ID = :user_id'
        );
        $profileStmt->execute([
            'phone' => $phone,
            'city' => $city,
            'bio' => $bio,
            'user_id' => $userId,
        ]);

        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

// ── Collection helpers ────────────────────────────────────────────────────

function get_user_collection(int $userId): array
{
    $stmt = db()->prepare("
        SELECT c.Perfume_ID, c.Purchase_Date,
               p.Name as Perfume_Name, p.Price, p.Image_URL, b.Brand_Name
        FROM Collection c
        JOIN Perfume p ON c.Perfume_ID = p.Perfume_ID
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        WHERE c.User_ID = ?
        ORDER BY c.Purchase_Date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function is_in_collection(int $userId, int $perfumeId): bool
{
    $stmt = db()->prepare("SELECT 1 FROM Collection WHERE User_ID = ? AND Perfume_ID = ?");
    $stmt->execute([$userId, $perfumeId]);
    return (bool) $stmt->fetch();
}

function add_to_collection(int $userId, int $perfumeId, ?string $purchaseDate, string $notes = ''): void
{
    $stmt = db()->prepare("
        INSERT IGNORE INTO Collection (User_ID, Perfume_ID, Purchase_Date, Notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $perfumeId, $purchaseDate ?: null, $notes]);
}

function remove_from_collection(int $userId, int $perfumeId): void
{
    $stmt = db()->prepare("DELETE FROM Collection WHERE User_ID = ? AND Perfume_ID = ?");
    $stmt->execute([$userId, $perfumeId]);
}

// ── Review history helper ─────────────────────────────────────────────────

function get_user_reviews(int $userId): array
{
    $stmt = db()->prepare("
        SELECT r.Review_ID, r.Rating, r.Comment, r.Created_at,
               p.Perfume_ID, p.Name as Perfume_Name, b.Brand_Name
        FROM Review r
        JOIN Perfume p ON r.Perfume_ID = p.Perfume_ID
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        WHERE r.User_ID = ?
        ORDER BY r.Created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ── Purchase helpers ────────────────────────────────────────────────────

function get_user_purchases(int $userId): array
{
    $stmt = db()->prepare("
        SELECT pu.Purchase_ID, pu.Perfume_ID, pu.Purchase_Date, pu.Price, pu.Quantity,
               p.Name as Perfume_Name, p.Image_URL, b.Brand_Name
        FROM Purchases pu
        JOIN Perfume p ON pu.Perfume_ID = p.Perfume_ID
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        WHERE pu.User_ID = ?
        ORDER BY pu.Purchase_Date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function purchase_perfume(int $userId, int $perfumeId, float $price = 0.0, int $quantity = 1): void
{
    $stmt = db()->prepare("
        INSERT INTO Purchases (User_ID, Perfume_ID, Price, Quantity)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $perfumeId, $price, $quantity]);
}
