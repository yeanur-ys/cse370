<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php'; // Required for is_logged_in() in header logic underneath
require_once __DIR__ . '/partials/header.php';
?>

<div class="hero">
    <h1>Welcome to Scentology</h1>
    <p>Your personal fragrance vault, marketplace, and community.</p>
    <?php if (!is_logged_in()): ?>
        <a href="/signup.php" class="btn">Join the Community</a>
    <?php endif; ?>
</div>

<div class="grid features-grid">
    <div class="card">
        <h3>📍 Discover Shops</h3>
        <p>Find local partner shops and check their live inventory for your desired scents before you visit.</p>
        <a href="/shops.php">Browse Shops &rarr;</a>
    </div>
    
    <div class="card">
        <h3>👤 Build Your Profile</h3>
        <p>Set up your personal details, and prepare to track your collection, reviews, and wishlist.</p>
        <?php if (is_logged_in()): ?>
            <a href="/profile.php">Manage Profile &rarr;</a>
        <?php else: ?>
            <a href="/login.php">Login to Manage &rarr;</a>
        <?php endif; ?>
    </div>
    
    <div class="card disabled-card">
        <h3>🤝 Trade & Marketplace</h3>
        <p><em>Coming in Phase 2</em>: Exchange perfumes with other enthusiasts or buy/sell condition-aware listings.</p>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
