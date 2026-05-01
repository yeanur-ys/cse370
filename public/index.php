<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$pdo = db();

// Fetch featured perfumes (highest priced or random selection)
$featuredStmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, b.Brand_Name
    FROM Perfume p
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    ORDER BY p.Price DESC
    LIMIT 6
");
$featuredStmt->execute();
$featuredPerfumes = $featuredStmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="hero">
    <h1>Welcome to Scentology</h1>
    <p>Your personal fragrance vault, marketplace, and community.</p>
    <?php if (!is_logged_in()): ?>
        <a href="/signup.php" class="btn">Join the Community</a>
    <?php else: ?>
        <a href="/perfumes.php" class="btn">Browse Perfumes</a>
    <?php endif; ?>
</div>

<!-- Featured Perfumes -->
<?php if (count($featuredPerfumes) > 0): ?>
<div class="card">
    <h2>✨ Featured Perfumes</h2>
    <div class="grid">
        <?php foreach ($featuredPerfumes as $perfume): ?>
            <div class="shop-item">
                <?php if ($perfume['Image_URL']): ?>
                    <div style="width: 100%; height: 150px; overflow: hidden; border-radius: 8px; margin-bottom: 10px;">
                        <img src="<?= htmlspecialchars((string) $perfume['Image_URL']) ?>" alt="<?= htmlspecialchars((string) $perfume['Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <strong>
                    <a href="/perfume-detail.php?id=<?= $perfume['Perfume_ID'] ?>" style="color: inherit; text-decoration: none;">
                        <?= htmlspecialchars((string) $perfume['Name']) ?>
                    </a>
                </strong><br>
                <small><?= htmlspecialchars((string) $perfume['Brand_Name']) ?></small><br>
                <?php if ($perfume['Price']): ?>
                    <small style="color: #e74c3c; font-weight: bold;">৳ <?= number_format((float) $perfume['Price']) ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="/perfumes.php" style="text-decoration: none; color: #3498db; font-weight: bold; display: block; margin-top: 15px;">
        View All Perfumes →
    </a>
</div>
<?php endif; ?>

<div class="grid features-grid">
    <div class="card">
        <h3>💎 Browse Perfumes</h3>
        <p>Explore our curated collection of premium fragrances from top brands worldwide.</p>
        <a href="/perfumes.php">Browse Catalog &rarr;</a>
    </div>
    
    <div class="card">
        <h3>📍 Discover Shops</h3>
        <p>Find local partner shops and check their live inventory for your desired scents.</p>
        <a href="/shops.php">Browse Shops &rarr;</a>
    </div>
    
    <div class="card">
        <h3>👤 Build Your Profile</h3>
        <p>Set up your personal details and track your collection, reviews, and wishlist.</p>
        <?php if (is_logged_in()): ?>
            <a href="/profile.php">Manage Profile &rarr;</a>
        <?php else: ?>
            <a href="/login.php">Login to Manage &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
