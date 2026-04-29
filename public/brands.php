<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$pdo = db();

// Fetch all brands with perfume count
$stmt = $pdo->prepare("
    SELECT b.Brand_ID, b.Brand_Name, COUNT(p.Perfume_ID) as Perfume_Count
    FROM Brand b
    LEFT JOIN Perfume p ON b.Brand_ID = p.Brand_ID
    GROUP BY b.Brand_ID
    ORDER BY b.Brand_Name
");
$stmt->execute();
$brands = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Perfume Brands</h2>
    <p>Explore all premium perfume brands in our catalog.</p>
</div>

<div class="card">
    <h3>Brands (<?= count($brands) ?>)</h3>

    <?php if (count($brands) === 0): ?>
        <p>No brands found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($brands as $brand): ?>
                <div class="shop-item">
                    <strong>
                        <a href="/perfumes.php?brand=<?= $brand['Brand_ID'] ?>" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars((string) $brand['Brand_Name']) ?>
                        </a>
                    </strong><br>
                    <small>Perfumes: <?= htmlspecialchars((string) $brand['Perfume_Count']) ?></small><br>
                    <a href="/perfumes.php?brand=<?= $brand['Brand_ID'] ?>" style="text-decoration: none; color: #007bff;">
                        View Perfumes →
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
