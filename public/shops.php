<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        $error = 'Login required to register a shop.';
    } else {
        $shopName = trim((string) ($_POST['shop_name'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $latitude = trim((string) ($_POST['latitude'] ?? ''));
        $longitude = trim((string) ($_POST['longitude'] ?? ''));
        $inventory = trim((string) ($_POST['inventory_notes'] ?? ''));

        if ($shopName === '' || $city === '' || $address === '') {
            $error = 'Shop name, city, and address are required.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO shops (shop_name, city, address, latitude, longitude, inventory_notes, created_by)
                 VALUES (:shop_name, :city, :address, :latitude, :longitude, :inventory_notes, :created_by)'
            );

            $stmt->execute([
                'shop_name' => $shopName,
                'city' => $city,
                'address' => $address,
                'latitude' => $latitude !== '' ? (float) $latitude : null,
                'longitude' => $longitude !== '' ? (float) $longitude : null,
                'inventory_notes' => $inventory,
                'created_by' => (int) current_user_id(),
            ]);

            $success = 'Shop registered successfully.';
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$cityFilter = trim((string) ($_GET['city'] ?? ''));

$sql = 'SELECT s.id, s.shop_name, s.city, s.address, s.latitude, s.longitude, s.inventory_notes, s.created_at, u.full_name AS owner_name
        FROM shops s
        LEFT JOIN users u ON u.id = s.created_by
        WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (s.shop_name LIKE :q OR s.inventory_notes LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($cityFilter !== '') {
    $sql .= ' AND s.city LIKE :city';
    $params['city'] = '%' . $cityFilter . '%';
}

$sql .= ' ORDER BY s.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$shops = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>
<div class="card">
    <h2>Shops</h2>
    <p>Browse partner shops and basic inventory notes.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="GET" action="/shops.php">
        <label for="q">Search shop or inventory</label>
        <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g. Oud, Dior, niche">

        <label for="city">Filter by city</label>
        <input type="text" id="city" name="city" value="<?= htmlspecialchars($cityFilter) ?>" placeholder="e.g. Dhaka">

        <button type="submit">Search</button>
    </form>
</div>

<div class="card">
    <h3>Register Shop</h3>
    <form method="POST" action="/shops.php">
        <label for="shop_name">Shop Name</label>
        <input type="text" id="shop_name" name="shop_name" required>

        <label for="city_reg">City</label>
        <input type="text" id="city_reg" name="city" required>

        <label for="address">Address</label>
        <input type="text" id="address" name="address" required>

        <label for="latitude">Latitude (optional)</label>
        <input type="text" id="latitude" name="latitude" placeholder="23.8103">

        <label for="longitude">Longitude (optional)</label>
        <input type="text" id="longitude" name="longitude" placeholder="90.4125">

        <label for="inventory_notes">Live Inventory Notes</label>
        <textarea id="inventory_notes" name="inventory_notes" rows="3" placeholder="What perfumes are currently available?"></textarea>

        <button type="submit">Register Shop</button>
    </form>
</div>

<div class="card">
    <h3>Available Shops</h3>

    <?php if (count($shops) === 0): ?>
        <p>No shops found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($shops as $shop): ?>
                <div class="shop-item">
                    <strong><?= htmlspecialchars((string) $shop['shop_name']) ?></strong><br>
                    <small><?= htmlspecialchars((string) $shop['city']) ?> | <?= htmlspecialchars((string) $shop['address']) ?></small><br>
                    <small>Inventory: <?= htmlspecialchars((string) ($shop['inventory_notes'] ?: 'No notes yet')) ?></small><br>
                    <small>Coordinates: <?= htmlspecialchars((string) ($shop['latitude'] ?? 'N/A')) ?>, <?= htmlspecialchars((string) ($shop['longitude'] ?? 'N/A')) ?></small><br>
                    <small>Registered by: <?= htmlspecialchars((string) ($shop['owner_name'] ?? 'Unknown')) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
