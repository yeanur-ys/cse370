<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

if (is_logged_in() && $user === null) {
    header("Location: logout.php");
    exit;
}
$pdo = db();

// Fetch all reviews with ratings
$stmt = $pdo->prepare("
    SELECT r.Review_ID, r.Rating, r.Comment, r.Created_at,
           p.Perfume_ID, p.Name, b.Brand_Name, u.User_Name,
           COALESCE((SELECT AVG(Rating) FROM Review WHERE Perfume_ID = p.Perfume_ID), 0) as Avg_Rating,
           (SELECT COUNT(*) FROM Review WHERE Perfume_ID = p.Perfume_ID) as Review_Count
    FROM Review r
    JOIN Perfume p ON r.Perfume_ID = p.Perfume_ID
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    JOIN User u ON r.User_ID = u.User_ID
    ORDER BY r.Created_at DESC
    LIMIT 50
");
$stmt->execute();
$reviews = $stmt->fetchAll();

// Handle new review submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && $user !== null) {
    $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if ($perfumeId <= 0 || $rating < 1 || $rating > 5) {
        $error = 'Invalid perfume or rating.';
    } else {
        // Check if user has it in collection or purchases
        $hasInCollection = is_in_collection($user['id'], $perfumeId);
        $hasInStock = false;
        
        $stockCheck = $pdo->prepare("SELECT 1 FROM Purchases WHERE User_ID = ? AND Perfume_ID = ?");
        $stockCheck->execute([$user['id'], $perfumeId]);
        if ($stockCheck->fetch()) {
            $hasInStock = true;
        }

        if (!$hasInCollection && !$hasInStock) {
            $error = 'You can only review perfumes that you have in your collection or stock.';
        } else {
            // Check if user already reviewed this perfume
            $checkStmt = $pdo->prepare("SELECT Review_ID FROM Review WHERE Perfume_ID = ? AND User_ID = ?");
            $checkStmt->execute([$perfumeId, $user['id']]);
            if ($checkStmt->fetch()) {
                $error = 'You have already reviewed this perfume.';
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO Review (Perfume_ID, User_ID, Rating, Comment) VALUES (?, ?, ?, ?)");
                if ($insertStmt->execute([$perfumeId, $user['id'], $rating, $comment])) {
                    $success = 'Review submitted successfully!';
                    header("Refresh:2; url=reviews.php");
                } else {
                    $error = 'Failed to submit review.';
                }
            }
        }
    }
}

// Fetch all perfumes for selection
$perfumeStmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, b.Brand_Name 
    FROM Perfume p
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    ORDER BY b.Brand_Name, p.Name
");
$perfumeStmt->execute();
$perfumes = $perfumeStmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Community Reviews</h2>
    <p>Read and write reviews for your favorite perfumes.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if (is_logged_in()): ?>
    <div class="card">
        <h3>Post a Review</h3>
        <form method="POST" action="reviews.php">
            <label for="perfume_id">Select Perfume</label>
            <select id="perfume_id" name="perfume_id" required>
                <option value="">-- Choose a Perfume --</option>
                <?php foreach ($perfumes as $perfume): ?>
                    <option value="<?= $perfume['Perfume_ID'] ?>">
                        <?= htmlspecialchars((string) $perfume['Brand_Name']) ?> - <?= htmlspecialchars((string) $perfume['Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="rating">Rating (1-5 stars)</label>
            <select id="rating" name="rating" required>
                <option value="">-- Select --</option>
                <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                <option value="3">⭐⭐⭐ (3 stars)</option>
                <option value="2">⭐⭐ (2 stars)</option>
                <option value="1">⭐ (1 star)</option>
            </select>

            <label for="comment">Your Review (Optional)</label>
            <textarea id="comment" name="comment" rows="4" placeholder="Share your thoughts about this perfume..."></textarea>

            <button type="submit">Submit Review</button>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <p><a href="login.php">Login</a> to post a review.</p>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Recent Reviews</h3>

    <?php if (count($reviews) === 0): ?>
        <p>No reviews yet. Be the first to review!</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div style="border-left: 4px solid #007bff; padding: 12px; margin-bottom: 12px; background: #f8f9fa; border-radius: 4px;">
                <strong><?= htmlspecialchars((string) $review['Brand_Name']) ?> - <?= htmlspecialchars((string) $review['Name']) ?></strong><br>
                <small>Reviewed by: <?= htmlspecialchars((string) $review['User_Name']) ?> on <?= date('M d, Y', strtotime((string) $review['Created_at'])) ?></small><br>
                <small>Rating: <?= str_repeat('⭐', $review['Rating']) ?> (<?= $review['Rating'] ?>/5)</small>
                <?php if ($review['Avg_Rating'] > 0): ?>
                    <small style="display: block; margin-top: 4px; color: #059669;"><strong>✓ Average: <?= round((float) $review['Avg_Rating'], 1) ?>/5 (<?= (int) $review['Review_Count'] ?> reviews)</strong></small>
                <?php endif; ?>
                <?php if ($review['Comment']): ?>
                    <p style="margin-top: 8px; font-style: italic;">
                        "<?= htmlspecialchars((string) $review['Comment']) ?>"
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
