<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

require_login();

$userId = current_user_id();
if ($userId === null) {
    // Extra defense: should already be handled by require_login()
    header('Location: login.php');
    exit;
}
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

$user = get_user_with_profile((int) $userId);

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

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-button active" onclick="showTab('profile', event)">📝 Profile Info</button>
        <button class="tab-button" onclick="showTab('collection', event)">💎 My Collection</button>
        <button class="tab-button" onclick="showTab('wishlist', event)">🤍 My Wishlist</button>
    </div>

    <!-- Profile Tab -->
    <div id="profile" class="tab-content active">
        <div style="background: #eef2ff; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            <strong>Account Type:</strong> 
            <?php if (!empty($user['is_seller'])): ?>
                Seller (Total Sales: <?= htmlspecialchars((string) ($user['total_sell'] ?? '0')) ?>)
            <?php else: ?>
                General User
            <?php endif; ?>
        </div>

        <form method="POST" action="profile.php">
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

            <button type="submit">Update Profile</button>
        </form>

        <small>Member since: <?= htmlspecialchars((string) ($user['created_at'] ?? 'N/A')) ?></small>
    </div>

    <!-- Collection Tab -->
    <div id="collection" class="tab-content">
        <?php if (empty($user['is_seller'])): ?>
            <div style="background: #f0f0f0; padding: 12px; border-radius: 8px;">
                <p><strong>📖 My Fragrance Collection:</strong></p>
                <p><em>View your <a href="wishlist.php" style="color: #2563eb; font-weight: bold;">wishlist</a> to see perfumes you love.</em></p>
                <p>You can manage your collection through your wishlist and the reviews you post about perfumes you own.</p>
            </div>
        <?php else: ?>
            <div style="background: #fee2e2; padding: 12px; border-radius: 8px;">
                <p>💼 Sellers don't have personal collections. Focus on managing your <a href="listings.php" style="color: #2563eb; font-weight: bold;">market listings</a> instead!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Wishlist Tab -->
    <div id="wishlist" class="tab-content">
        <p><strong>❤️ My Wishlist:</strong></p>
        <?php 
        try {
            $pdo = db();
            $wishlistStmt = $pdo->prepare("
                SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, b.Brand_Name
                FROM Wishlist w
                JOIN Perfume p ON w.Perfume_ID = p.Perfume_ID
                JOIN Brand b ON p.Brand_ID = b.Brand_ID
                WHERE w.User_ID = ?
                ORDER BY b.Brand_Name, p.Name
            ");
            $wishlistStmt->execute([$userId]);
            $wishlistItems = $wishlistStmt->fetchAll();
            
            if (count($wishlistItems) > 0): ?>
                <div class="grid">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="shop-item">
                        <?php if ($item['Image_URL']): ?>
                            <div style="width: 100%; height: 150px; overflow: hidden; border-radius: 8px; margin-bottom: 10px;">
                                <img src="<?= htmlspecialchars(asset_image_url((string) $item['Image_URL'])) ?>" alt="<?= htmlspecialchars((string) $item['Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <strong><a href="perfume-detail.php?id=<?= $item['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars((string) $item['Name']) ?></a></strong><br>
                        <small><?= htmlspecialchars((string) $item['Brand_Name']) ?></small><br>
                        <?php if ($item['Price']): ?>
                            <small style="color: #e74c3c; font-weight: bold;">৳ <?= number_format((float) $item['Price']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><em>Your wishlist is empty. <a href="perfumes.php">Browse perfumes</a> and add some!</em></p>
            <?php endif; ?>
        <?php } catch (Exception $e) { ?>
            <p style="color: #991b1b;">Error loading wishlist: <?= htmlspecialchars((string) $e->getMessage()) ?></p>
        <?php } ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
function showTab(tabName, event) {
    event.preventDefault();
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remove active from all buttons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Mark button as active
    event.target.classList.add('active');
}
</script>
