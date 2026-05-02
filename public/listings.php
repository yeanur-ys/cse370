<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_listing') {
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
                try {
                    $pdo = db();
                    $stmt = $pdo->prepare("INSERT INTO Listing (User_ID, Item_Name, Quantity, Price, Item_Condition) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([(int) $user['id'], $itemName, $quantity, $price, $condition])) {
                        $success = 'Listing successfully created.';
                    } else {
                        $error = 'Failed to create the listing.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = 'Invalid listing details. Please check name, price, and quantity.';
            }
        }
    } elseif ($_POST['action'] === 'purchase_listing') {
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        if ($listingId > 0 && is_logged_in() && $user) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare("UPDATE Listing SET Status = 'Sold', Purchased_By_User_ID = ?, Purchased_At = NOW() WHERE Listing_ID = ?");
                if ($stmt->execute([(int) $user['id'], $listingId])) {
                    $success = 'Purchase successful! The seller has been notified.';
                } else {
                    $error = 'Failed to purchase listing.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Unable to purchase listing. Please ensure you are logged in.';
        }
    }
}

// Fetch all listings
try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT l.*, s.Shop_Name, u.User_Name, u.Email, p.City, p.Number
        FROM Listing l
        JOIN User u ON l.User_ID = u.User_ID 
        LEFT JOIN Shop s ON s.User_ID = l.User_ID
        LEFT JOIN Profile p ON u.User_ID = p.User_ID
        ORDER BY l.Listing_ID DESC
    ");
    $stmt->execute();
    $listings = $stmt->fetchAll();
} catch (Exception $e) {
    $listings = [];
    if (!$error) {
        $error = 'Failed to load listings: ' . htmlspecialchars($e->getMessage());
    }
}

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
    <form method="POST" action="listings.php">
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
                    <strong style="color: #2563eb;">🏷️ <?= htmlspecialchars((string) $listing['Item_Name']) ?></strong>
                    <span style="display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; margin: 8px 0;">
                        <?= htmlspecialchars((string) $listing['Status']) ?>
                    </span>
                    <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px;">
                        <small><strong>💰 Price:</strong> ৳<?= number_format((float) $listing['Price']) ?></small><br>
                        <small><strong>📦 Qty:</strong> <?= (int) $listing['Quantity'] ?></small><br>
                        <small><strong>✨ Condition:</strong> <?= htmlspecialchars((string) $listing['Item_Condition']) ?></small>
                    </div>
                    <div style="padding: 12px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 2px; margin: 8px 0;">
                        <small><strong>👤 Seller:</strong> <?= htmlspecialchars((string) $listing['User_Name']) ?></small><br>
                        <?php if ($listing['Shop_Name']): ?>
                            <small><strong>🏪 Shop:</strong> <?= htmlspecialchars((string) $listing['Shop_Name']) ?></small><br>
                        <?php endif; ?>
                    </div>
                    <?php if ($listing['City'] || $listing['Number'] || $listing['Email']): ?>
                        <div style="padding: 8px; background: #f0fdf4; border-left: 3px solid #10b981; border-radius: 2px;">
                            <?php if ($listing['City']): ?>
                                <small><strong>📍</strong> <?= htmlspecialchars((string) $listing['City']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($listing['Number']): ?>
                                <small><strong>📱</strong> <?= htmlspecialchars((string) $listing['Number']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($listing['Email']): ?>
                                <small><strong>📧</strong> <a href="mailto:<?= htmlspecialchars((string) $listing['Email']) ?>" style="color: #10b981;"><?= htmlspecialchars((string) $listing['Email']) ?></a></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $listingStatus = $listing['Status'] ?? 'Available';
                    ?>
                    <?php if (is_logged_in() && $listingStatus === 'Available' && (int) $listing['User_ID'] !== $userId): ?>
                        <form method="POST" action="listings.php" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="purchase_listing">
                            <input type="hidden" name="listing_id" value="<?= $listing['Listing_ID'] ?>">
                            <button type="submit" style="background: #2563eb; width: 100%; padding: 10px;">🛒 Purchase</button>
                        </form>
                    <?php elseif ($listingStatus !== 'Available'): ?>
                        <div style="margin-top: 12px; padding: 10px; background: #e0e7ff; border-radius: 6px; text-align: center; color: #4338ca; font-weight: bold;">
                            ✓ <?= htmlspecialchars((string) $listingStatus) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
