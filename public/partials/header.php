<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/auth.php';

ensure_session_started();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <nav class="nav">
        <a href="/index.php">Home</a>
        <a href="/shops.php">Shops</a>
        <?php if (is_logged_in()): ?>
            <a href="/profile.php">Profile</a>
            <a href="/logout.php">Logout</a>
        <?php else: ?>
            <a href="/login.php">Login</a>
            <a href="/signup.php">Sign Up</a>
        <?php endif; ?>
    </nav>
    <main class="container">
