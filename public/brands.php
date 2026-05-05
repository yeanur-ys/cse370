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

<div class="card" style="background: linear-gradient(to right, #4f46e5, #7c3aed); color: white; padding: 40px 20px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px 0; font-size: 2.5em;">Perfume Brands</h2>
    <p style="margin: 0; font-size: 1.2em; opacity: 0.9;">Explore all premium perfume brands in our catalog.</p>
</div>

<div class="card" style="box-shadow: none; background: transparent; padding: 0;">
    <h3 style="margin-bottom: 20px;">Brands (<?= count($brands) ?>)</h3>

    <?php if (count($brands) === 0): ?>
        <p>No brands found.</p>
    <?php else: ?>
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($brands as $brand): ?>
                <a href="perfumes.php?brand=<?= $brand['Brand_ID'] ?>" class="shop-item" style="text-decoration: none; color: inherit; padding: 25px 20px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; align-items: center; text-align: center; border: 1px solid #f3f4f6;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 15px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.05)';">
                    <strong style="font-size: 1.3em; color: #1f2937; margin-bottom: 10px;">
                        <?= htmlspecialchars((string) $brand['Brand_Name']) ?>
                    </strong>
                    <span style="font-size: 0.9em; color: #6b7280; background: #f3f4f6; padding: 4px 12px; border-radius: 20px;">
                        <?= htmlspecialchars((string) $brand['Perfume_Count']) ?> Perfumes
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
