<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

$statusFilter = trim((string) ($_GET['status'] ?? 'Available'));
$allowedStatuses = ['Available', 'Sold', 'Cancelled', 'All'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'Available';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_listing') {
        if (!is_logged_in()) {
            $error = 'Login required to post a listing.';
        } elseif (!$user || !$user['is_seller']) {
            $error = 'Only sellers can post listings.';
        } else {
            $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 1);
            $price = (float) ($_POST['price'] ?? 0.0);
            $condition = trim((string) ($_POST['item_condition'] ?? 'New'));

            if ($perfumeId > 0 && $price >= 0 && $quantity > 0) {
                try {
                    $pdo = db();
                    $stmt = $pdo->prepare("INSERT INTO Listing (User_ID, Perfume_ID, Quantity, Price, Item_Condition) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([(int) $user['id'], $perfumeId, $quantity, $price, $condition])) {
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
    } elseif ($_POST['action'] === 'cancel_listing') {
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        if ($listingId > 0 && is_logged_in() && $user) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare("UPDATE Listing SET Status = 'Cancelled' WHERE Listing_ID = ? AND User_ID = ?");
                if ($stmt->execute([$listingId, (int) $user['id']])) {
                    $success = 'Listing cancelled successfully.';
                } else {
                    $error = 'Failed to cancel listing. You may only cancel your own listings.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Unable to cancel listing. Please ensure you are logged in.';
        }
    } elseif ($_POST['action'] === 'purchase_listing') {
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        if ($listingId > 0 && is_logged_in() && $user) {
            try {
                $pdo = db();
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE Listing SET Status = 'Sold', Purchased_By_User_ID = ?, Purchased_At = NOW() WHERE Listing_ID = ?");
                if ($stmt->execute([(int) $user['id'], $listingId])) {
                     if ($sellerId > 0) {
                         $sellerStmt = $pdo->prepare("UPDATE Seller SET Total_Sell = Total_Sell + 1 WHERE User_ID = ?");
                         $sellerStmt->execute([$sellerId]);
                     }
                     $pdo->commit();
                    $success = 'Purchase successful! The seller has been notified.';
                } else {
                    $pdo->rollBack();
                    $error = 'Failed to purchase listing.';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Unable to purchase listing. Please ensure you are logged in.';
        }
    }
}

// Fetch listings with status filter
try {
    $pdo = db();
    $sql = "
        SELECT l.*, s.Shop_Name, u.User_Name, u.Email, p.City, p.Number,
               perf.Name as Item_Name, b.Brand_Name
        FROM Listing l
        JOIN User u ON l.User_ID = u.User_ID 
        LEFT JOIN Shop s ON s.User_ID = l.User_ID
        LEFT JOIN Profile p ON u.User_ID = p.User_ID
        JOIN Perfume perf ON l.Perfume_ID = perf.Perfume_ID
        JOIN Brand b ON perf.Brand_ID = b.Brand_ID
    ";
    $filterParams = [];
    if ($statusFilter !== 'All') {
        $sql .= " WHERE l.Status = ?";
        $filterParams[] = $statusFilter;
    }
    $sql .= " ORDER BY l.Listing_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filterParams);
    $listings = $stmt->fetchAll();
} catch (Exception $e) {
    $listings = [];
    if (!$error) {
        $error = 'Failed to load listings: ' . htmlspecialchars($e->getMessage());
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card" style="background: linear-gradient(to right, #2563eb, #3b82f6); color: white; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 20px;">
    <h2 style="margin: 0 0 10px 0; font-size: 2.5em;">Market Listings</h2>
    <p style="margin: 0; font-size: 1.2em; opacity: 0.9;">Browse perfumes listed by sellers. Find the best deals.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error" style="margin-top: 20px; text-align: left; background: rgba(220, 38, 38, 0.9); color: white; border: none;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success" style="margin-top: 20px; text-align: left; background: rgba(5, 150, 105, 0.9); color: white; border: none;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 30px; box-shadow: none;">
    <h3 style="margin-top: 0; font-size: 1.1em; color: #475569; margin-bottom: 15px;">Filter by Status</h3>
    <!-- Status filter tabs -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php foreach ($allowedStatuses as $st): ?>
            <a href="?status=<?= urlencode($st) ?>" 
               style="padding: 10px 20px; background: <?= $st === $statusFilter ? '#2563eb' : 'white' ?>; color: <?= $st === $statusFilter ? 'white' : '#475569' ?>; text-decoration: none; border-radius: 8px; font-weight: <?= $st === $statusFilter ? 'bold' : 'normal' ?>; border: 1px solid <?= $st === $statusFilter ? '#2563eb' : '#cbd5e1' ?>; transition: all 0.2s; box-shadow: <?= $st === $statusFilter ? '0 4px 6px rgba(37,99,235,0.2)' : '0 1px 2px rgba(0,0,0,0.05)' ?>;">
                <?= htmlspecialchars($st) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card" style="border: 2px solid #3b82f6; border-radius: 12px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);">
    <h3 style="color: #1d4ed8; margin-top: 0; border-bottom: 1px solid #bfdbfe; padding-bottom: 10px; margin-bottom: 20px;">📦 Post New Listing</h3>
    <form method="POST" action="listings.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <input type="hidden" name="action" value="add_listing">
        
        <div style="grid-column: 1 / -1;">
            <label for="perfume_id" style="font-weight: bold; margin-bottom: 5px; display: block;">Select Perfume</label>
            <select id="perfume_id" name="perfume_id" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="">-- Choose Perfume --</option>
                <?php 
                $perfumesQuery = db()->query("SELECT p.Perfume_ID, p.Name, b.Brand_Name FROM Perfume p JOIN Brand b ON p.Brand_ID = b.Brand_ID ORDER BY b.Brand_Name, p.Name")->fetchAll();
                foreach($perfumesQuery as $pq) {
                    echo '<option value="'.$pq['Perfume_ID'].'">'.htmlspecialchars($pq['Brand_Name'].' - '.$pq['Name']).'</option>';
                }
                ?>
            </select>
        </div>

        <div>
            <label for="quantity" style="font-weight: bold; margin-bottom: 5px; display: block;">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div>
            <label for="price" style="font-weight: bold; margin-bottom: 5px; display: block;">Price ($)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="grid-column: 1 / -1;">
            <label for="item_condition" style="font-weight: bold; margin-bottom: 5px; display: block;">Item Condition</label>
            <select id="item_condition" name="item_condition" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="New">New</option>
                <option value="Like New">Like New</option>
                <option value="Good">Good</option>
                <option value="Acceptable">Acceptable</option>
            </select>
        </div>

        <div style="grid-column: 1 / -1; margin-top: 10px;">
            <button type="submit" style="width: 100%; background: #2563eb; color: white; padding: 12px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em; transition: background 0.2s;">Post Listing</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card" style="box-shadow: none; background: transparent; padding: 0;">
    <h3 style="margin-bottom: 20px;">Listings (<?= count($listings) ?>)</h3>

    <?php if (count($listings) === 0): ?>
        <p style="padding: 20px; background: white; border-radius: 10px; text-align: center; color: #64748b;">No listings found matching the current filter.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($listings as $listing): ?>
                <div class="listing-item" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; position: relative;">
                    <?php
                        $badgeColor = '#3b82f6';
                        if ($listing['Status'] === 'Sold') $badgeColor = '#10b981';
                        if ($listing['Status'] === 'Cancelled') $badgeColor = '#ef4444';
                    ?>
                    <span style="position: absolute; top: 15px; right: 15px; background: <?= $badgeColor ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold;">
                        <?= htmlspecialchars((string) $listing['Status']) ?>
                    </span>
                    
                    <strong style="display: block; font-size: 1.3em; margin-bottom: 2px; color: #1e293b; padding-right: 60px;">
                        <?= htmlspecialchars((string) $listing['Item_Name']) ?>
                    </strong>
                    <small style="display: block; margin-bottom: 12px; color: #64748b; font-weight: bold;">
                        <?= htmlspecialchars((string) $listing['Brand_Name']) ?>
                    </small>
                    
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #f1f5f9;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #2563eb; margin-bottom: 5px;">$<?= number_format((float) $listing['Price'], 2) ?></div>
                        <div style="color: #64748b; font-size: 0.9em; display: flex; justify-content: space-between;">
                            <span>📦 Qty: <?= (int)$listing['Quantity'] ?></span>
                            <span>✨ <?= htmlspecialchars((string) $listing['Item_Condition']) ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="font-size: 0.85em; color: #94a3b8; text-transform: uppercase;">Seller Info</strong>
                        <div style="font-weight: 500; color: #334155; margin-top: 5px;">
                            👤 <?= htmlspecialchars((string) ($listing['Shop_Name'] ?: $listing['User_Name'])) ?>
                        </div>
                        <div style="font-size: 0.9em; color: #64748b; margin-top: 3px;">
                            📍 <?= htmlspecialchars((string) ($listing['City'] ?? 'Location unknown')) ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <?php 
                    $listingStatus = $listing['Status'] ?? 'Available';
                    $isOwner = is_logged_in() && (int) $listing['User_ID'] === $userId;
                    ?>
                    <?php if (is_logged_in() && $listingStatus === 'Available' && !$isOwner): ?>
                        <form method="POST" action="listings.php" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="purchase_listing">
                            <input type="hidden" name="listing_id" value="<?= $listing['Listing_ID'] ?>">
                            <input type="hidden" name="seller_id" value="<?= $listing['User_ID'] ?>">
                            <button type="submit" style="background: #2563eb; width: 100%; padding: 10px; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">🛒 Purchase</button>
                        </form>
                    <?php elseif ($isOwner && $listingStatus === 'Available'): ?>
                        <form method="POST" action="listings.php" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="cancel_listing">
                            <input type="hidden" name="listing_id" value="<?= $listing['Listing_ID'] ?>">
                            <button type="submit" style="background: #e74c3c; width: 100%; padding: 10px; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">✕ Cancel Listing</button>
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

