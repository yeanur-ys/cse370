<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$user = is_logged_in() ? get_user_with_profile($_SESSION['user_id']) : null;
$brandFilter = trim((string) ($_GET['brand'] ?? ''));
$noteFilter = trim((string) ($_GET['note'] ?? ''));

// Fetch all perfumes with brands and notes
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
if ($brandFilter) {
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

// Fetch user's wishlist if logged in
$userWishlist = [];
if ($user) {
    $wishlistStmt = $pdo->prepare("SELECT Perfume_ID FROM Wishlist WHERE User_ID = ?");
    $wishlistStmt->execute([$user['id']]);
    $userWishlist = array_column($wishlistStmt->fetchAll(), 'Perfume_ID');
}

// Handle add/remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'add_wishlist' && $perfumeId > 0) {
        $addStmt = $pdo->prepare("INSERT IGNORE INTO Wishlist (Perfume_ID, User_ID) VALUES (?, ?)");
        $addStmt->execute([$perfumeId, $user['id']]);
        header("Location: /perfumes.php");
        exit;
    } elseif ($action === 'remove_wishlist' && $perfumeId > 0) {
        $removeStmt = $pdo->prepare("DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?");
        $removeStmt->execute([$perfumeId, $user['id']]);
        header("Location: /perfumes.php");
        exit;
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Perfume Catalog</h2>
    <p>Browse our collection of premium perfumes.</p>
</div>

<div class="card">
    <h3>Filter by Brand</h3>
    <form method="GET" action="/perfumes.php">
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
                    <?php if ($perfume['Image_URL']): ?>
                        <div class="perfume-image" style="width: 100%; height: 200px; overflow: hidden; border-radius: 8px; margin-bottom: 10px;">
                            <img src="<?= htmlspecialchars((string) $perfume['Image_URL']) ?>" alt="<?= htmlspecialchars((string) $perfume['Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <strong>
                        <a href="/perfume-detail.php?id=<?= $perfume['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars((string) $perfume['Name']) ?>
                        </a>
                    </strong><br>
                    <small><strong>Brand:</strong> <?= htmlspecialchars((string) $perfume['Brand_Name']) ?></small><br>
                    <?php if ($perfume['Price']): ?>
                        <small style="color: #e74c3c; font-weight: bold;">💰 ৳ <?= number_format((float) $perfume['Price']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($perfume['Notes']): ?>
                        <small><strong>Notes:</strong> <?= htmlspecialchars((string) $perfume['Notes']) ?></small><br>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <form method="POST" action="/perfumes.php" style="display: inline;">
                            <input type="hidden" name="perfume_id" value="<?= $perfume['Perfume_ID'] ?>">
                            <?php if (in_array($perfume['Perfume_ID'], $userWishlist)): ?>
                                <button type="submit" name="action" value="remove_wishlist" class="btn-small" style="background: #ff6b6b;">
                                    ❤️ Remove from Wishlist
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="add_wishlist" class="btn-small">
                                    🤍 Add to Wishlist
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <small><a href="/login.php">Login to add to wishlist</a></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
