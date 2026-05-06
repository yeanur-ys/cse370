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
<div class="card" style="background: linear-gradient(to right, #059669, #10b981); color: white; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px 0; font-size: 2.5em;">Partner Shops</h2>
    <p style="margin: 0; font-size: 1.2em; opacity: 0.9;">Browse retail locations and live stock.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error" style="margin-top: 15px; background: rgba(220, 38, 38, 0.9); color: white; border: none;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success" style="margin-top: 15px; background: rgba(5, 150, 105, 0.9); color: white; border: none;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card" style="border: 2px solid #10b981; border-radius: 12px; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);">
    <h3 style="color: #047857; margin-top: 0; border-bottom: 1px solid #a7f3d0; padding-bottom: 10px;">Register New Shop</h3>
    <form method="POST" action="shops.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div style="grid-column: 1 / -1;">
            <label for="shop_name" style="font-weight: bold;">Shop Name</label>
            <input type="text" id="shop_name" name="shop_name" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="grid-column: 1 / -1;">
            <label for="address" style="font-weight: bold;">Address</label>
            <input type="text" id="address" name="address" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div>
            <label for="latitude" style="font-weight: bold;">Latitude (optional)</label>
            <input type="text" id="latitude" name="latitude" placeholder="23.8103" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div>
            <label for="longitude" style="font-weight: bold;">Longitude (optional)</label>
            <input type="text" id="longitude" name="longitude" placeholder="90.4125" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
        </div>

        <div style="grid-column: 1 / -1;">
            <label for="stock" style="font-weight: bold;">Live Stock Summary</label>
            <textarea id="stock" name="stock" rows="3" placeholder="What perfumes are currently available?" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;"></textarea>
        </div>

        <div style="grid-column: 1 / -1;">
            <button type="submit" style="width: 100%; background: #10b981; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1.1em;">Register Shop</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card" style="box-shadow: none; background: transparent; padding: 0; margin-top: 30px;">
    <h3 style="margin-bottom: 20px;">Available Shops</h3>

    <?php if (count($shops) === 0): ?>
        <p>No shops found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($shops as $shop): ?>
                <div class="shop-item" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <div style="background: #ecfdf5; padding: 12px; border-radius: 50%; margin-right: 15px;">
                            🛒
                        </div>
                        <div>
                            <strong style="font-size: 1.25em; color: #111827; display: block; margin-bottom: 5px;"><?= htmlspecialchars((string) $shop['Shop_Name']) ?></strong>
                            <span style="color: #6b7280; font-size: 0.9em; display: flex; align-items: center; gap: 5px;">📍 <?= htmlspecialchars((string) $shop['Address']) ?></span>
                        </div>
                    </div>
                    <div style="background: #f9fafb; padding: 12px; border-radius: 8px; flex-grow: 1;">
                        <strong style="font-size: 0.85em; text-transform: uppercase; color: #4b5563; letter-spacing: 0.05em;">📦 Stock</strong>
                        <p style="margin: 5px 0 0 0; font-size: 0.95em; color: #374151;"><?= htmlspecialchars((string) ($shop['Stock'] ?: 'No inventory listed yet')) ?></p>
                    </div>
                    <div style="margin-top: 15px; font-size: 0.8em; color: #9ca3af; display: flex; justify-content: space-between;">
                        <span>Lat: <?= htmlspecialchars((string) ($shop['Latitude'] ?? '--')) ?></span>
                        <span>Lon: <?= htmlspecialchars((string) ($shop['Longitude'] ?? '--')) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
