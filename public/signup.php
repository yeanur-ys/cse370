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
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? 'general'));
    $isSeller = ($role === 'seller');

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (find_user_by_email($email) !== null) {
        $error = 'Email is already registered.';
    } else {
        $userId = create_user($fullName, $email, $password, $isSeller);
        login_user($userId);
        header('Location: profile.php');
        exit;
    }
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="card" style="background: linear-gradient(to right, #10b981, #047857); color: white; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px 0; font-size: 2.5em;">Sign Up</h2>
    <p style="margin: 0; font-size: 1.2em; opacity: 0.9;">Join the Scentology community today.</p>
</div>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
        <label for="full_name">User Name</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="role">Account Type</label>
        <select id="role" name="role" required style="margin-bottom: 14px;">
            <option value="general">General User</option>
            <option value="seller">Seller</option>
        </select>

        <button type="submit">Create Account</button>
    </form>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
