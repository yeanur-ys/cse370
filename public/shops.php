<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        $error = 'Login required to register a shop.';
    } elseif (!$user || !$user['is_seller']) {
        $error = 'Only sellers can register a shop.';
    } else {
        $shopName = trim((string) ($_POST['shop_name'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $latitude = trim((string) ($_POST['latitude'] ?? ''));
        $longitude = trim((string) ($_POST['longitude'] ?? ''));
        $stock = trim((string) ($_POST['stock'] ?? ''));

        if ($shopName && $address) {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO Shop (User_ID, Shop_Name, Address, Latitude, Longitude, Stock) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $shopName, $address, $latitude ?: null, $longitude ?: null, $stock])) {
                $success = 'Shop successfully registered.';
            } else {
                $error = 'Failed to register the shop.';
            }
        } else {
            $error = 'Shop name and address are required.';
        }
    }
}

$sql = 'SELECT Shop_ID, Shop_Name, Address, Latitude, Longitude, Stock FROM Shop ORDER BY Shop_ID DESC';
$stmt = db()->prepare($sql);
$stmt->execute();
$shops = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>
<div class="card">
    <h2>Shops</h2>
    <p>Browse partner shops and basic stock properties.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card">
    <h3>Register Shop</h3>
    <form method="POST" action="shops.php">
        <label for="shop_name">Shop Name</label>
        <input type="text" id="shop_name" name="shop_name" required>

        <label for="address">Address</label>
        <input type="text" id="address" name="address" required>

        <label for="latitude">Latitude (optional)</label>
        <input type="text" id="latitude" name="latitude" placeholder="23.8103">

        <label for="longitude">Longitude (optional)</label>
        <input type="text" id="longitude" name="longitude" placeholder="90.4125">

        <label for="stock">Live Stock</label>
        <textarea id="stock" name="stock" rows="3" placeholder="What perfumes are currently available?"></textarea>

        <button type="submit">Register Shop</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Available Shops</h3>

    <?php if (count($shops) === 0): ?>
        <p>No shops found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($shops as $shop): ?>
                <div class="shop-item">
                    <strong><?= htmlspecialchars((string) $shop['Shop_Name']) ?></strong><br>
                    <small>Location: <?= htmlspecialchars((string) $shop['Address']) ?></small><br>
                    <small>Stock: <?= htmlspecialchars((string) ($shop['Stock'] ?: 'No notes yet')) ?></small><br>
                    <small>Coordinates: <?= htmlspecialchars((string) ($shop['Latitude'] ?? 'N/A')) ?>, <?= htmlspecialchars((string) ($shop['Longitude'] ?? 'N/A')) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
