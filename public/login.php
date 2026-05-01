<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

$error = '';

if (is_logged_in()) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $user = find_user_by_email($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid credentials.';
        } else {
            login_user((int) $user['id']);
            header('Location: profile.php');
            exit;
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="card">
    <h2>Login</h2>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
