<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

$brandFilter = isset($_GET['brand']) ? (int) $_GET['brand'] : 0;
$noteFilter = trim((string) ($_GET['note'] ?? ''));

// Fetch all perfumes with brands and notes
try {
    $pdo = db();

    $query = "
        SELECT p.Perfume_ID, p.Name, p.Release_Year, p.Price, p.Image_URL, b.Brand_Name, b.Brand_ID,
               GROUP_CONCAT(n.Note_Name SEPARATOR ', ') as Notes
        FROM Perfume p
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
        LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
    ";

    $params = [];
    if ($brandFilter > 0) {
        $query .= " WHERE b.Brand_ID = ?";
        $params[] = $brandFilter;
    }

    $query .= " GROUP BY p.Perfume_ID ORDER BY b.Brand_Name, p.Name";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $perfumes = $stmt->fetchAll();

    // Fetch all brands for filter
    $brandStmt = $pdo->prepare("SELECT Brand_ID, Brand_Name FROM Brand ORDER BY Brand_Name");
    $brandStmt->execute();
    $brands = $brandStmt->fetchAll();
} catch (Exception $e) {
    $perfumes = [];
    $brands = [];
    $dbError = "Database error: " . $e->getMessage() . ". <a href='init-db.php'>Initialize database</a>";
}

// Fetch user's wishlist if logged in
$userWishlist = [];
if ($user && isset($pdo)) {
    $wishlistStmt = $pdo->prepare('SELECT Perfume_ID FROM Wishlist WHERE User_ID = ?');
    $wishlistStmt->execute([(int) $user['id']]);
    $userWishlist = array_map('intval', array_column($wishlistStmt->fetchAll(), 'Perfume_ID'));
}

// Handle add/remove from wishlist and buy perfume
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && $user && isset($pdo)) {
    $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'add_wishlist' && $perfumeId > 0) {
        $addStmt = $pdo->prepare('INSERT IGNORE INTO Wishlist (Perfume_ID, User_ID) VALUES (?, ?)');
        $addStmt->execute([$perfumeId, (int) $user['id']]);
        header("Location: perfumes.php");
        exit;
    } elseif ($action === 'remove_wishlist' && $perfumeId > 0) {
        $removeStmt = $pdo->prepare('DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?');
        $removeStmt->execute([$perfumeId, (int) $user['id']]);
        header("Location: perfumes.php");
        exit;
    } elseif ($action === 'buy_perfume' && $perfumeId > 0) {
        try {
            // Get perfume price
            $priceStmt = $pdo->prepare('SELECT Price FROM Perfume WHERE Perfume_ID = ?');
            $priceStmt->execute([$perfumeId]);
            $perf = $priceStmt->fetch();
            $price = $perf ? (float) $perf['Price'] : 0;
            
            purchase_perfume((int) $user['id'], $perfumeId, $price, 1);
            header("Location: perfumes.php?bought=1");
            exit;
        } catch (Exception $e) {
            // Continue, error will show in page
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card" style="background: linear-gradient(to right, #8b5cf6, #d946ef); color: white; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px 0; font-size: 2.5em;">Perfume Catalog</h2>
    <p style="margin: 0; font-size: 1.2em; opacity: 0.9;">Browse our collection of premium perfumes.</p>
</div>

<?php if (isset($_GET['bought']) && $_GET['bought'] === '1'): ?>
    <div class="alert" style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #16a34a;">
        <strong>✓ Success!</strong> Perfume added to your stock. <a href="profile.php?tab=purchases" style="color: #166534; font-weight: bold;">View your stock</a>
    </div>
<?php endif; ?>

<?php if (isset($dbError)): ?>
    <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <strong>⚠️ Database Error:</strong> <?= $dbError ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Filter by Brand</h3>
    <form method="GET" action="perfumes.php">
        <select name="brand">
            <option value="">All Brands</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['Brand_ID'] ?>" <?= $brandFilter == $brand['Brand_ID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $brand['Brand_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card">
    <h3>All Perfumes (<?= count($perfumes) ?>)</h3>

    <?php if (count($perfumes) === 0): ?>
        <p>No perfumes found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($perfumes as $perfume): ?>
                <div class="shop-item">
                    <div class="perfume-image" style="width: 100%; height: 180px; overflow: hidden; border-radius: 8px; margin-bottom: 10px; background: #e0e0e0;">
                        <?php if ($perfume['Image_URL']): ?>
                            <?php $imgUrl = asset_image_url((string) $perfume['Image_URL']); ?>
                            <img src="<?= $imgUrl ?>" 
                                 alt="<?= htmlspecialchars((string) $perfume['Name']) ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="text-align: center; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">🧴 No image</div>
                        <?php endif; ?>
                    </div>
                    <a href="perfume-detail.php?id=<?= $perfume['Perfume_ID'] ?>" style="color: inherit; text-decoration: none; font-weight: bold; display: block; margin: 8px 0; line-height: 1.3;">
                        <?= htmlspecialchars((string) $perfume['Name']) ?>
                    </a>
                    <small style="display: block; margin: 4px 0;"><strong>Brand:</strong> <?= htmlspecialchars((string) $perfume['Brand_Name']) ?></small>
                    <?php if ($perfume['Price']): ?>
                        <small style="color: #e74c3c; font-weight: bold; display: block; margin: 4px 0;">💰 ৳ <?= number_format((float) $perfume['Price']) ?></small>
                    <?php endif; ?>
                    <?php if ($perfume['Notes']): ?>
                        <small style="display: block; margin: 4px 0;"><strong>Notes:</strong> <?= htmlspecialchars((string) $perfume['Notes']) ?></small>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                            <form method="POST" action="perfumes.php" style="flex: 1; min-width: 120px;">
                                <input type="hidden" name="perfume_id" value="<?= $perfume['Perfume_ID'] ?>">
                                <button type="submit" name="action" value="buy_perfume" class="btn-small" style="background: #10b981; width: 100%; padding: 8px;">
                                    🛍️ Buy
                                </button>
                            </form>
                            <form method="POST" action="perfumes.php" style="flex: 1; min-width: 120px;">
                                <input type="hidden" name="perfume_id" value="<?= $perfume['Perfume_ID'] ?>">
                                <?php if (in_array($perfume['Perfume_ID'], $userWishlist)): ?>
                                    <button type="submit" name="action" value="remove_wishlist" class="btn-small" style="background: #ff6b6b; width: 100%; padding: 8px;">
                                        ❤️ Wishlist
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="add_wishlist" class="btn-small" style="width: 100%; padding: 8px;">
                                        🤍 Wishlist
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php else: ?>
                        <small><a href="login.php" style="color: #3498db; font-weight: bold;">Login</a> to buy or add to wishlist</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
