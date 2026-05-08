<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

require_login();

$userId = current_user_id();
if ($userId === null) {
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

    if ($fullName === '') {
        $error = 'Full name is required.';
    } else {
        update_profile((int) $userId, $fullName, $phone, $city, $bio);
        $success = 'Profile updated successfully.';
    }
}

$user = get_user_with_profile((int) $userId);
$collection = get_user_collection((int) $userId);
$myReviews = get_user_reviews((int) $userId);
$purchases = get_user_purchases((int) $userId);

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
        <button class="tab-button" onclick="showTab('purchases', event)">🛍️ My Stock (<?= count($purchases) ?>)</button>
        <button class="tab-button" onclick="showTab('reviews', event)">⭐ My Reviews</button>
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
        <p><strong>📚 My Fragrance Collection:</strong></p>
        <?php if (count($collection) > 0): ?>
            <div class="grid">
            <?php foreach ($collection as $item): ?>
                <div class="shop-item">
                    <?php if ($item['Image_URL']): ?>
                        <div style="width: 100%; height: 150px; overflow: hidden; border-radius: 8px; margin-bottom: 10px;">
                            <img src="<?= htmlspecialchars(asset_image_url((string) $item['Image_URL'])) ?>" alt="<?= htmlspecialchars((string) $item['Perfume_Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <strong><a href="perfume-detail.php?id=<?= $item['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars((string) $item['Perfume_Name']) ?></a></strong><br>
                    <small><?= htmlspecialchars((string) $item['Brand_Name']) ?></small><br>
                    <?php if ($item['Price']): ?>
                        <small style="color: #e74c3c; font-weight: bold;">৳ <?= number_format((float) $item['Price']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($item['Purchase_Date']): ?>
                        <small style="color: #666;">📅 <?= date('M d, Y', strtotime((string) $item['Purchase_Date'])) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><em>Your collection is empty. <a href="perfumes.php" style="color: #2563eb; font-weight: bold;">Add perfumes</a> from the catalog!</em></p>
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
            $wishlistStmt->execute([(int) $userId]);
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

    <!-- Purchases/Stock Tab -->
    <div id="purchases" class="tab-content">
        <p><strong>🛍️ My Stock (<?= count($purchases) ?> purchases):</strong></p>
        <?php if (count($purchases) > 0): ?>
            <div class="grid">
            <?php foreach ($purchases as $purchase): ?>
                <div class="shop-item">
                    <?php if ($purchase['Image_URL']): ?>
                        <div style="width: 100%; height: 150px; overflow: hidden; border-radius: 8px; margin-bottom: 10px;">
                            <img src="<?= htmlspecialchars(asset_image_url((string) $purchase['Image_URL'])) ?>" alt="<?= htmlspecialchars((string) $purchase['Perfume_Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <strong><a href="perfume-detail.php?id=<?= $purchase['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars((string) $purchase['Perfume_Name']) ?></a></strong><br>
                    <small><?= htmlspecialchars((string) $purchase['Brand_Name']) ?></small><br>
                    <?php if ($purchase['Price']): ?>
                        <small style="color: #e74c3c; font-weight: bold;">৳ <?= number_format((float) $purchase['Price']) ?></small><br>
                    <?php endif; ?>
                    <small style="color: #666;">
                        📦 Qty: <?= (int) $purchase['Quantity'] ?><br>
                        📅 <?= date('M d, Y', strtotime((string) $purchase['Purchase_Date'])) ?>
                    </small>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><em>You haven't purchased any perfumes yet. <a href="perfumes.php" style="color: #2563eb; font-weight: bold;">Shop now</a>!</em></p>
        <?php endif; ?>
    </div>

    <!-- Reviews Tab -->
    <div id="reviews" class="tab-content">
        <p><strong>⭐ My Reviews:</strong></p>
        <?php if (count($myReviews) > 0): ?>
            <?php foreach ($myReviews as $review): ?>
                <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <strong><a href="perfume-detail.php?id=<?= $review['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars((string) $review['Brand_Name']) ?> - <?= htmlspecialchars((string) $review['Perfume_Name']) ?>
                            </a></strong><br>
                            <small style="color: #666;">
                                <?= str_repeat('⭐', (int) $review['Rating']) ?> (<?= htmlspecialchars((string) $review['Rating']) ?>/5)
                            </small>
                        </div>
                        <small style="color: #999;">
                            <?= date('M d, Y', strtotime((string) $review['Created_at'])) ?>
                        </small>
                    </div>
                    <?php if ($review['Comment']): ?>
                        <p style="margin-top: 10px; color: #333;">
                            <?= htmlspecialchars((string) $review['Comment']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><em>You haven't written any reviews yet. <a href="perfumes.php" style="color: #2563eb; font-weight: bold;">Review some perfumes</a>!</em></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
function showTab(tabName, event) {
    event.preventDefault();
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

