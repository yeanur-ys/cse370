<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;
$perfumeId = (int) ($_GET['id'] ?? 0);

if ($perfumeId <= 0) {
    header("Location: perfumes.php");
    exit;
}

$pdo = db();

// Fetch perfume details
$perfumeStmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, p.Release_Year, 
           b.Brand_Name, b.Brand_ID,
           GROUP_CONCAT(n.Note_Name SEPARATOR ', ') as Notes
    FROM Perfume p
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
    LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
    WHERE p.Perfume_ID = ?
    GROUP BY p.Perfume_ID
");
$perfumeStmt->execute([$perfumeId]);
$perfume = $perfumeStmt->fetch();

if (!$perfume) {
    header("Location: perfumes.php");
    exit;
}

// Add stats and notes formatting safely
$perfume['Notes'] = $perfume['Notes'] ?? '';
$perfume['Price'] = (float) ($perfume['Price'] ?? 0);
$perfume['Name'] = (string) ($perfume['Name'] ?? 'Unknown Perfume');


// Fetch reviews
$reviewStmt = $pdo->prepare("
    SELECT r.Review_ID, r.Rating, r.Comment, r.Created_at, u.User_Name
    FROM Review r
    JOIN User u ON r.User_ID = u.User_ID
    WHERE r.Perfume_ID = ?
    ORDER BY r.Created_at DESC
");
$reviewStmt->execute([$perfumeId]);
$reviews = $reviewStmt->fetchAll();

// Check if in wishlist
$inWishlist = false;
$inCollection = false;
$inStock = false;
if ($user) {
    $wishStmt = $pdo->prepare("SELECT 1 FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?");
    $wishStmt->execute([$perfumeId, $user['id']]);
    $inWishlist = (bool) $wishStmt->fetch();

    $inCollection = is_in_collection((int)$user['id'], $perfumeId);

    $stockStmt = $pdo->prepare("SELECT 1 FROM Purchases WHERE Perfume_ID = ? AND User_ID = ?");
    $stockStmt->execute([$perfumeId, $user['id']]);
    $inStock = (bool) $stockStmt->fetch();
}

// Handle wishlist and collection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_wishlist') {
        $addStmt = $pdo->prepare("INSERT IGNORE INTO Wishlist (Perfume_ID, User_ID) VALUES (?, ?)");
        $addStmt->execute([$perfumeId, $user['id']]);
        header("Location: perfume-detail.php?id=$perfumeId");
        exit;
    } elseif ($action === 'remove_wishlist') {
        $removeStmt = $pdo->prepare("DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?");
        $removeStmt->execute([$perfumeId, $user['id']]);
        header("Location: perfume-detail.php?id=$perfumeId");
        exit;
    } elseif ($action === 'add_collection') {
        $purchaseDate = trim((string) ($_POST['purchase_date'] ?? ''));
        $notes = trim((string) ($_POST['collection_notes'] ?? ''));
        add_to_collection((int)$user['id'], $perfumeId, $purchaseDate ?: null, $notes);
        header("Location: perfume-detail.php?id=$perfumeId");
        exit;
    } elseif ($action === 'remove_collection') {
        remove_from_collection((int)$user['id'], $perfumeId);
        header("Location: perfume-detail.php?id=$perfumeId");
        exit;
    } elseif ($action === 'add_review') {
        if ($inCollection || $inStock) {
            $rating = (int) ($_POST['rating'] ?? 0);
            $comment = trim((string) ($_POST['comment'] ?? ''));
            
            if ($rating >= 1 && $rating <= 5) {
                $reviewInsertStmt = $pdo->prepare("
                    INSERT INTO Review (Perfume_ID, User_ID, Rating, Comment)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE Rating = ?, Comment = ?, Created_at = NOW()
                ");
                $reviewInsertStmt->execute([$perfumeId, $user['id'], $rating, $comment, $rating, $comment]);
                header("Location: perfume-detail.php?id=$perfumeId");
                exit;
            }
        } else {
            $error = 'You can only review a perfume that is in your collection.';
        }
    } elseif ($action === 'buy_perfume') {
        $price = (float) ($perfume['Price'] ?? 0);
        purchase_perfume((int)$user['id'], $perfumeId, $price, 1);
        header("Location: perfume-detail.php?id=$perfumeId&bought=1");
        exit;
    }
}

// Calculate average rating
$avgRating = 0;
if (count($reviews) > 0) {
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += (int) $review['Rating'];
    }
    $avgRating = round($totalRating / count($reviews), 1);
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <!-- Image Section -->
        <div style="flex: 1; min-width: 300px;">
            <div style="background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; min-height: 400px;">
                <?php if ($perfume['Image_URL']): ?>
                    <?php $imgUrl = asset_image_url((string) $perfume['Image_URL']); ?>
                    <img id="detail-img-<?= $perfume['Perfume_ID'] ?>" src="<?= $imgUrl ?>" 
                         alt="<?= htmlspecialchars((string) $perfume['Name']) ?>"
                         style="width: 100%; max-width: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                         onerror="this.style.display='none'; document.getElementById('fallback-<?= $perfume['Perfume_ID'] ?>').style.display='block';">
                    <div id="fallback-<?= $perfume['Perfume_ID'] ?>" style="display: none; text-align: center; padding: 40px;">
                        <div style="font-size: 48px;">🧴</div>
                        <p>Image unavailable</p>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px;">🧴</div>
                        <p>No image available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details Section -->
        <div style="flex: 1; min-width: 300px;">
            <?php if (isset($_GET['bought']) && $_GET['bought'] === '1'): ?>
                <div class="alert" style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #16a34a;">
                    <strong>✓ Success!</strong> Perfume added to your stock. <a href="profile.php?tab=purchases" style="color: #166534; font-weight: bold;">View your stock</a>
                </div>
            <?php endif; ?>

            <h1><?= htmlspecialchars((string) $perfume['Name']) ?></h1>
            
            <p style="margin: 15px 0;">
                <strong>Brand:</strong> <a href="perfumes.php?brand=<?= $perfume['Brand_ID'] ?>"><?= htmlspecialchars((string) $perfume['Brand_Name']) ?></a>
            </p>

            <?php if ($perfume['Price']): ?>
                <p style="margin: 15px 0; font-size: 24px; color: #e74c3c;">
                    <strong>💰 Price: ৳<?= number_format((float) $perfume['Price']) ?></strong>
                </p>
            <?php endif; ?>

            <?php if ($perfume['Notes']): ?>
                <p style="margin: 15px 0;">
                    <strong>Fragrance Notes:</strong><br>
                    <?= htmlspecialchars((string) $perfume['Notes']) ?>
                </p>
            <?php endif; ?>

            <!-- Buy Button -->
            <?php if (is_logged_in()): ?>
                <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin: 20px 0;">
                    <button type="submit" name="action" value="buy_perfume" class="btn-large" style="background: #10b981; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; width: 100%; font-size: 18px; font-weight: bold; margin-bottom: 10px;">
                        🛍️ Buy Now
                    </button>
                </form>
            <?php endif; ?>

            <!-- Wishlist Button -->
            <?php if (is_logged_in()): ?>
                <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin: 20px 0;">
                    <?php if ($inWishlist): ?>
                        <button type="submit" name="action" value="remove_wishlist" class="btn-large" style="background: #ff6b6b; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white;">
                            ❤️ Remove from Wishlist
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="add_wishlist" class="btn-large" style="background: #3498db; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white;">
                            🤍 Add to Wishlist
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Collection Form -->
                <?php if ($inCollection): ?>
                    <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin: 10px 0;">
                        <button type="submit" name="action" value="remove_collection" class="btn-large" style="background: #e67e22; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; width: 100%;">
                            📚 Remove from My Collection
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin: 10px 0; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                        <h4 style="margin-top: 0;">📚 Add to My Collection</h4>
                        <label for="purchase_date" style="display: block; margin-bottom: 10px;"><strong>Purchase Date (optional):</strong></label>
                        <input type="date" name="purchase_date" id="purchase_date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 10px;">
                        
                        <label for="collection_notes" style="display: block; margin-bottom: 10px;"><strong>Notes (optional):</strong></label>
                        <textarea name="collection_notes" id="collection_notes" rows="2" placeholder="e.g., Gift from Mom, Limited Edition..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 10px;"></textarea>
                        
                        <button type="submit" name="action" value="add_collection" class="btn-large" style="background: #27ae60; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; width: 100%;">
                            ✓ Add to Collection
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p style="margin: 20px 0;">
                    <a href="login.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Login</a> to add to wishlist or collection
                </p>
            <?php endif; ?>

            <!-- Average Rating -->
            <?php if (count($reviews) > 0): ?>
                <div style="margin: 20px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                    <strong>Average Rating:</strong> ⭐ <?= htmlspecialchars((string) $avgRating) ?>/5 (<?= count($reviews) ?> reviews)
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="card">
    <h2>Reviews (<?= count($reviews) ?>)</h2>

    <?php if (is_logged_in()): ?>
        <div style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
            <?php if ($inCollection || $inStock): ?>
                <h3>Add Your Review</h3>
                <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>">
                    <div style="margin-bottom: 15px;">
                        <label for="rating"><strong>Rating:</strong></label><br>
                        <select name="rating" id="rating" required style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;">
                            <option value="">Select rating...</option>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐⭐ Average</option>
                            <option value="2">⭐⭐ Poor</option>
                            <option value="1">⭐ Very Poor</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="comment"><strong>Comment:</strong></label><br>
                        <textarea name="comment" id="comment" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; box-sizing: border-box;"></textarea>
                    </div>
                    <button type="submit" name="action" value="add_review" class="btn-large" style="background: #27ae60; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white;">
                        Submit Review
                    </button>
                </form>
            <?php else: ?>
                <p style="color: #666; font-style: italic; margin: 0;">You must have this perfume in your collection or stock to leave a review.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p><a href="login.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Login</a> to leave a review</p>
    <?php endif; ?>

    <!-- Display Reviews -->
    <?php if (count($reviews) > 0): ?>
        <div style="margin-top: 20px;">
            <?php foreach ($reviews as $review): ?>
                <div style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?= htmlspecialchars((string) $review['User_Name']) ?></strong>
                            <br>
                            <small style="color: #666;">
                                <?= str_repeat('⭐', (int) $review['Rating']) ?> 
                                (<?= htmlspecialchars((string) $review['Rating']) ?>/5)
                            </small>
                        </div>
                        <small style="color: #999;">
                            <?= date('M d, Y', strtotime((string) $review['Created_at'])) ?>
                        </small>
                    </div>
                    <p style="margin-top: 10px; color: #333;">
                        <?= htmlspecialchars((string) $review['Comment']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">No reviews yet. Be the first to review!</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
