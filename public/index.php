<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

try {
    $pdo = db();
    
    // Fetch featured perfumes (highest priced selection)
    $featuredStmt = $pdo->prepare("
        SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, b.Brand_Name,
               GROUP_CONCAT(n.Note_Name SEPARATOR ', ') as Notes
        FROM Perfume p
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
        LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
        GROUP BY p.Perfume_ID
        ORDER BY p.Price DESC
        LIMIT 6
    ");
    $featuredStmt->execute();
    $featuredPerfumes = $featuredStmt->fetchAll();
    
    // Get stats
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM Perfume) as perfumeCount,
            (SELECT COUNT(*) FROM Brand) as brandCount,
            (SELECT COUNT(*) FROM User) as userCount,
            (SELECT COUNT(*) FROM Review) as reviewCount
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
} catch (Exception $e) {
    $featuredPerfumes = [];
    $stats = ['perfumeCount' => 0, 'brandCount' => 0, 'userCount' => 0, 'reviewCount' => 0];
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="hero">
    <h1>🧴 Welcome to Scentology</h1>
    <p>Your personal fragrance vault, marketplace, and community for perfume enthusiasts.</p>
    <?php if (!is_logged_in()): ?>
        <a href="signup.php" class="btn">Join the Community</a>
    <?php else: ?>
        <a href="perfumes.php" class="btn">Browse Our Collection</a>
    <?php endif; ?>
</div>

<!-- Stats Section -->
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; margin-bottom: 24px;">
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 20px; text-align: center;">
        <div>
            <h3 style="margin: 0; font-size: 2rem; color: #ffd700;"><?= (int) $stats['perfumeCount'] ?></h3>
            <p style="margin: 8px 0 0 0; font-size: 0.9rem; opacity: 0.9;">Premium Perfumes</p>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 2rem; color: #ffd700;"><?= (int) $stats['brandCount'] ?></h3>
            <p style="margin: 8px 0 0 0; font-size: 0.9rem; opacity: 0.9;">Top Brands</p>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 2rem; color: #ffd700;"><?= (int) $stats['userCount'] ?></h3>
            <p style="margin: 8px 0 0 0; font-size: 0.9rem; opacity: 0.9;">Community Members</p>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 2rem; color: #ffd700;"><?= (int) $stats['reviewCount'] ?></h3>
            <p style="margin: 8px 0 0 0; font-size: 0.9rem; opacity: 0.9;">Total Reviews</p>
        </div>
    </div>
</div>

<!-- Featured Perfumes -->
<?php if (count($featuredPerfumes) > 0): ?>
<div class="card">
    <h2 style="border-bottom: 3px solid #667eea; padding-bottom: 12px;">✨ Premium Selection - Trending Now</h2>
    <div class="grid">
        <?php foreach ($featuredPerfumes as $perfume): ?>
            <div class="shop-item">
                <?php if ($perfume['Image_URL']): ?>
                    <div style="width: 100%; height: 200px; overflow: hidden; border-radius: 10px; margin-bottom: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <img src="<?= htmlspecialchars((string) $perfume['Image_URL']) ?>" alt="<?= htmlspecialchars((string) $perfume['Name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <strong style="font-size: 1.1rem; display: block; margin-bottom: 8px;">
                    <a href="perfume-detail.php?id=<?= $perfume['Perfume_ID'] ?>" style="color: #2563eb; text-decoration: none;">
                        <?= htmlspecialchars((string) $perfume['Name']) ?>
                    </a>
                </strong>
                <small style="display: block; margin-bottom: 6px;"><strong>Brand:</strong> <?= htmlspecialchars((string) $perfume['Brand_Name']) ?></small>
                <?php if ($perfume['Notes']): ?>
                    <small style="display: block; margin-bottom: 8px; color: #059669;"><strong>Notes:</strong> <?= htmlspecialchars(substr((string) $perfume['Notes'], 0, 40)) ?>...</small>
                <?php endif; ?>
                <?php if ($perfume['Price']): ?>
                    <small style="display: block; color: #e74c3c; font-weight: bold; font-size: 1.1rem; margin-top: auto;">৳ <?= number_format((float) $perfume['Price']) ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="perfumes.php" style="text-decoration: none; color: #2563eb; font-weight: bold; display: block; margin-top: 18px; text-align: center; padding: 12px; background: #f3f4f6; border-radius: 8px; transition: background 0.2s;">
        🔍 View All <?= (int) $stats['perfumeCount'] ?> Perfumes →
    </a>
</div>
<?php endif; ?>

<!-- Features Grid -->
<div class="grid features-grid">
    <div class="card" style="border-left: 4px solid #667eea;">
        <h3>💎 Browse Perfumes</h3>
        <p>Explore our curated collection of premium fragrances from top brands worldwide. Filter by brand and notes.</p>
        <a href="perfumes.php" style="margin-top: auto;">Browse Catalog →</a>
    </div>
    
    <div class="card" style="border-left: 4px solid #764ba2;">
        <h3>📍 Discover Shops</h3>
        <p>Find local partner shops and check their live inventory for your desired scents and products.</p>
        <a href="shops.php" style="margin-top: auto;">Browse Shops →</a>
    </div>
    
    <div class="card" style="border-left: 4px solid #f59e0b;">
        <h3>💬 Community</h3>
        <p>Connect with other fragrance enthusiasts, share reviews, and trade perfumes with the community.</p>
        <a href="reviews.php" style="margin-top: auto;">Read Reviews →</a>
    </div>
    
    <div class="card" style="border-left: 4px solid #10b981;">
        <h3>🏪 Marketplace</h3>
        <p>Buy and sell perfumes on our secure marketplace. Sellers can list their inventory directly.</p>
        <a href="listings.php" style="margin-top: auto;">View Listings →</a>
    </div>
    
    <div class="card" style="border-left: 4px solid #ef4444;">
        <h3>🤍 Wishlist</h3>
        <p>Build your personal collection and track perfumes you love. Get notifications when prices drop.</p>
        <?php if (is_logged_in()): ?>
            <a href="wishlist.php" style="margin-top: auto;">My Wishlist →</a>
        <?php else: ?>
            <a href="login.php" style="margin-top: auto;">Login to Use →</a>
        <?php endif; ?>
    </div>
    
    <div class="card" style="border-left: 4px solid #3b82f6;">
        <h3>👤 Build Your Profile</h3>
        <p>Set up your personal details, manage your collection, reviews, and wishlist all in one place.</p>
        <?php if (is_logged_in()): ?>
            <a href="profile.php" style="margin-top: auto;">Manage Profile →</a>
        <?php else: ?>
            <a href="login.php" style="margin-top: auto;">Login to Manage →</a>
        <?php endif; ?>
    </div>
</div>

<!-- CTA Section -->
<div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); color: white; padding: 32px; text-align: center; margin-top: 24px;">
    <h2 style="margin-top: 0;">Ready to Join the Fragrance Community?</h2>
    <p style="font-size: 1.1rem; opacity: 0.9;">Discover, share, and trade your favorite perfumes with enthusiasts around the world.</p>
    <?php if (!is_logged_in()): ?>
        <a href="signup.php" class="btn" style="background: white; color: #f59e0b; margin-top: 12px;">Create Your Account</a>
    <?php else: ?>
        <a href="perfumes.php" class="btn" style="background: white; color: #f59e0b; margin-top: 12px;">Explore Now</a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
