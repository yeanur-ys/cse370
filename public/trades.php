<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$user = is_logged_in() ? get_user_with_profile($_SESSION['user_id']) : null;

// Handle new trade request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_trade') {
    if (!is_logged_in()) {
        $error = 'Login required to post a trade request.';
    } else {
        $offering = trim((string) ($_POST['offering'] ?? ''));
        $desired = trim((string) ($_POST['desired'] ?? ''));

        if ($offering && $desired) {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO Trade (User_ID, Offering, Desired) VALUES (?, ?, ?)");
            if ($stmt->execute([$user['id'], $offering, $desired])) {
                $success = 'Trade request properly posted.';
            } else {
                $error = 'Failed to post trade request.';
            }
        } else {
            $error = 'Please provide both what you are offering and what you desire.';
        }
    }
}

// Fetch all trade requests
$pdo = db();
$stmt = $pdo->prepare("
    SELECT t.*, u.User_Name, u.Email, p.City, p.Number
    FROM Trade t
    JOIN User u ON t.User_ID = u.User_ID 
    LEFT JOIN Profile p ON u.User_ID = p.User_ID
    ORDER BY t.Created_at DESC
");
$stmt->execute();
$trades = $stmt->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Community Trades</h2>
    <p>Offer and request perfume trades with the community.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if (is_logged_in()): ?>
<div class="card">
    <h3>Post Trade Request</h3>
    <form method="POST" action="trades.php">
        <input type="hidden" name="action" value="add_trade">
        
        <label for="offering">What I have (Offering)</label>
        <input type="text" id="offering" name="offering" placeholder="e.g. Dior Sauvage 50ml (90% full)" required>

        <label for="desired">What I want (Desired)</label>
        <input type="text" id="desired" name="desired" placeholder="e.g. Bleu de Chanel EDP" required>

        <button type="submit">Post Trade</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Active Trade Requests</h3>
    <?php if (count($trades) === 0): ?>
        <p>No active trade requests found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($trades as $trade): ?>
                <div class="shop-item">
                    <strong style="color: #2563eb;">👤 <?= htmlspecialchars((string) $trade['User_Name']) ?></strong>
                    <span style="display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; margin: 8px 0;">
                        <?= htmlspecialchars((string) $trade['Status']) ?>
                    </span>
                    <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px;">
                        <small><strong>📤 Offering:</strong></small><br>
                        <small><?= htmlspecialchars((string) $trade['Offering']) ?></small><br>
                        <small style="margin-top: 8px; display: block;"><strong>📥 Looking for:</strong></small><br>
                        <small><?= htmlspecialchars((string) $trade['Desired']) ?></small>
                    </div>
                    <?php if ($trade['City'] || $trade['Number']): ?>
                        <div style="padding: 8px; background: #f0fdf4; border-left: 3px solid #10b981; border-radius: 2px;">
                            <?php if ($trade['City']): ?>
                                <small><strong>📍 Location:</strong> <?= htmlspecialchars((string) $trade['City']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($trade['Number']): ?>
                                <small><strong>📱 Contact:</strong> <?= htmlspecialchars((string) $trade['Number']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($trade['Email']): ?>
                                <small><strong>📧 Email:</strong> <a href="mailto:<?= htmlspecialchars((string) $trade['Email']) ?>" style="color: #2563eb;"><?= htmlspecialchars((string) $trade['Email']) ?></a></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
