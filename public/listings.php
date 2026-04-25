<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$user = is_logged_in() ? get_user_with_profile($_SESSION['user_id']) : null;

// Handle form submission for new listing (Sellers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_listing') {
    if (!is_logged_in()) {
        $error = 'Login required to post a listing.';
    } elseif (!$user || !$user['is_seller']) {
        $error = 'Only sellers can post listings.';
    } else {
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $price = (float) ($_POST['price'] ?? 0.0);
        $condition = trim((string) ($_POST['item_condition'] ?? 'New'));

        if ($itemName && $price >= 0 && $quantity > 0) {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO Listing (User_ID, Item_Name, Quantity, Price, Item_Condition) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $itemName, $quantity, $price, $condition])) {
                $success = 'Listing successfully created.';
            } else {
                $error = 'Failed to create the listing.';
            }
        } else {
            $error = 'Invalid listing details. Please check name, price, and quantity.';
        }
    }
}

// Fetch all listings
$pdo = db();
$stmt = $pdo->prepare("
    SELECT l.*, s.Shop_Name, u.User_Name 
    FROM Listing l
    JOIN User u ON l.User_ID = u.User_ID 
    LEFT JOIN Shop s ON s.User_ID = l.User_ID
");
$stmt->execute();
$listings = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Market Listings</h2>
    <p>Browse available perfumes listed by sellers.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card">
    <h3>Post New Listing</h3>
    <form method="POST" action="/listings.php">
        <input type="hidden" name="action" value="add_listing">
        
        <label for="item_name">Item Name</label>
        <input type="text" id="item_name" name="item_name" required>

        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" value="1" required>

        <label for="price">Price ($)</label>
        <input type="number" step="0.01" id="price" name="price" min="0" required>

        <label for="item_condition">Condition</label>
        <select id="item_condition" name="item_condition">
            <option value="New">New</option>
            <option value="Used - Good">Used - Good</option>
            <option value="Used - Fair">Used - Fair</option>
        </select>

        <button type="submit">Publish Listing</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Current Listings</h3>
    <?php if (count($listings) === 0): ?>
        <p>No listings found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($listings as $listing): ?>
                <div class="shop-item">
                    <strong><?= htmlspecialchars((string) $listing['Item_Name']) ?></strong>
                    <span class="badge"><?= htmlspecialchars((string) $listing['Status']) ?></span><br>
                    <small>Price: $<?= htmlspecialchars((string) $listing['Price']) ?></small><br>
                    <small>Qty: <?= htmlspecialchars((string) $listing['Quantity']) ?> | Condition: <?= htmlspecialchars((string) $listing['Item_Condition']) ?></small><br>
                    <small>Seller: <?= htmlspecialchars((string) $listing['User_Name']) ?> <?= $listing['Shop_Name'] ? '(' . htmlspecialchars((string) $listing['Shop_Name']) . ')' : '' ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
