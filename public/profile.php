<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$userId = (int) current_user_id();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $collection = trim((string) ($_POST['collection'] ?? ''));

    if ($fullName === '') {
        $error = 'Full name is required.';
    } else {
        update_profile($userId, $fullName, $phone, $city, $bio, $collection);
        $success = 'Profile updated successfully.';
    }
}

$user = get_user_with_profile($userId);

require_once __DIR__ . '/partials/header.php';
?>
<div class="card">
    <h2>My Profile</h2>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="background: #eef2ff; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
        <strong>Account Type:</strong> 
        <?php if (!empty($user['is_seller'])): ?>
            Seller (Total Sales: <?= htmlspecialchars((string) ($user['total_sell'] ?? '0')) ?>)
        <?php else: ?>
            General User
        <?php endif; ?>
    </div>

    <form method="POST" action="/profile.php">
        <label for="full_name">User Name</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars((string) ($user['full_name'] ?? '')) ?>" required>

        <label>Email</label>
        <input type="email" value="<?= htmlspecialchars((string) ($user['email'] ?? '')) ?>" disabled>

        <label for="phone">Number</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars((string) ($user['phone'] ?? '')) ?>">

        <label for="city">City</label>
        <input type="text" id="city" name="city" value="<?= htmlspecialchars((string) ($user['city'] ?? '')) ?>">

        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars((string) ($user['bio'] ?? '')) ?></textarea>

        <?php if (empty($user['is_seller'])): ?>
            <label for="collection">My Collection Notes (General Users Only)</label>
            <textarea id="collection" name="collection" rows="3" placeholder="What perfumes do you own?"><?= htmlspecialchars((string) ($user['collection'] ?? '')) ?></textarea>
        <?php else: ?>
            <input type="hidden" name="collection" value="<?= htmlspecialchars((string) ($user['collection'] ?? '')) ?>">
        <?php endif; ?>

        <button type="submit">Update Profile</button>
    </form>

    <small>Member since: <?= htmlspecialchars((string) ($user['created_at'] ?? 'N/A')) ?></small>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
