<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();

$userId = (int) current_user_id();
$user = get_user_with_profile($userId);

$pdo = db();

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove' && isset($_POST['perfume_id'])) {
        $perfumeId = (int) $_POST['perfume_id'];
        $removeStmt = $pdo->prepare("DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?");
        if ($removeStmt->execute([$perfumeId, $userId])) {
            header("Location: wishlist.php");
            exit;
        }
    }
}

// Fetch user's wishlist
$stmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, p.Release_Year, b.Brand_Name
    FROM Wishlist w
    JOIN Perfume p ON w.Perfume_ID = p.Perfume_ID
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    WHERE w.User_ID = ?
    ORDER BY b.Brand_Name, p.Name
");
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>My Wishlist</h2>
    <p><?= htmlspecialchars((string) $user['full_name']) ?>'s collection of desired perfumes.</p>
</div>

<?php if (count($wishlist) === 0): ?>
    <div class="card">
        <p>Your wishlist is empty. <a href="perfumes.php">Browse perfumes and add to your wishlist</a></p>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Perfumes in Wishlist (<?= count($wishlist) ?>)</h3>
        <div class="grid">
            <?php foreach ($wishlist as $item): ?>
                <div class="shop-item">
                    <strong><?= htmlspecialchars((string) $item['Name']) ?></strong><br>
                    <small><strong>Brand:</strong> <?= htmlspecialchars((string) $item['Brand_Name']) ?></small><br>
                    <?php if ($item['Release_Year']): ?>
                        <small><strong>Year:</strong> <?= htmlspecialchars((string) $item['Release_Year']) ?></small><br>
                    <?php endif; ?>

                    <form method="POST" action="wishlist.php" style="display: inline; margin-top: 8px;">
                        <input type="hidden" name="perfume_id" value="<?= $item['Perfume_ID'] ?>">
                        <button type="submit" name="action" value="remove" class="btn-small" style="background: #ff6b6b;">
                            Remove
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
