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
