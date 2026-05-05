<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error = '';
$userId = current_user_id();
$user = $userId !== null ? get_user_with_profile($userId) : null;

// Handle new trade request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!is_logged_in()) {
        $error = 'Login required.';
    } elseif ($_POST['action'] === 'add_trade') {
        $offeringPerfumeId = (int) ($_POST['offering_perfume_id'] ?? 0);
        $desiredType = trim((string) ($_POST['desired_type'] ?? 'perfume'));
        $desiredPerfumeId = (int) ($_POST['desired_perfume_id'] ?? 0);
        $desiredNoteId = (int) ($_POST['desired_note_id'] ?? 0);

        if ($offeringPerfumeId > 0 && (($desiredType === 'perfume' && $desiredPerfumeId > 0) || ($desiredType === 'note' && $desiredNoteId > 0))) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare("
                    INSERT INTO Trade (User_ID, Offering_Perfume_ID, Desired_Perfume_ID, Desired_Note_ID, Status) 
                    VALUES (?, ?, ?, ?, 'Pending')
                ");
                if ($stmt->execute([(int) $user['id'], $offeringPerfumeId, ($desiredType === 'perfume' ? $desiredPerfumeId : null), ($desiredType === 'note' ? $desiredNoteId : null)])) {
                    $success = 'Trade request posted successfully!';
                } else {
                    $error = 'Failed to post trade request.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Please select a perfume to offer and what you desire.';
        }
    } elseif ($_POST['action'] === 'accept_trade') {
        $tradeId = (int) ($_POST['trade_id'] ?? 0);
        if ($tradeId > 0 && is_logged_in() && $user) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare("UPDATE Trade SET Status = 'Accepted', Accepted_By_User_ID = ?, Accepted_At = NOW() WHERE Trade_ID = ?");
                if ($stmt->execute([(int) $user['id'], $tradeId])) {
                    $success = 'Trade accepted! The other party has been notified.';
                } else {
                    $error = 'Failed to accept trade.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Unable to accept trade. Please ensure you are logged in.';
        }
    } elseif ($_POST['action'] === 'cancel_trade') {
        $tradeId = (int) ($_POST['trade_id'] ?? 0);
        if ($tradeId > 0 && is_logged_in() && $user) {
            try {
                $pdo = db();
                $stmt = $pdo->prepare("UPDATE Trade SET Status = 'Cancelled' WHERE Trade_ID = ? AND User_ID = ?");
                if ($stmt->execute([$tradeId, (int) $user['id']])) {
                    $success = 'Trade cancelled successfully.';
                } else {
                    $error = 'Failed to cancel trade. You may only cancel your own pending trades.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Unable to cancel trade. Please ensure you are logged in.';
        }
    }
}

// Fetch all trade requests
try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT t.Trade_ID, t.User_ID, t.Offering_Perfume_ID, t.Desired_Perfume_ID, t.Desired_Note_ID, t.Status, t.Created_at,
               u.User_Name, u.Email, p.City, p.Number,
               perfume_off.Name as Offering_Perfume_Name, perfume_off.Brand_ID,
               GROUP_CONCAT(n_off.Note_Name SEPARATOR ', ') as Offering_Notes,
               perfume_des.Name as Desired_Perfume_Name,
               note_des.Note_Name as Desired_Note_Name
        FROM Trade t
        JOIN User u ON t.User_ID = u.User_ID 
        LEFT JOIN Profile p ON u.User_ID = p.User_ID
        LEFT JOIN Perfume perfume_off ON t.Offering_Perfume_ID = perfume_off.Perfume_ID
        LEFT JOIN Has_Notes hn ON perfume_off.Perfume_ID = hn.Perfume_ID
        LEFT JOIN Notes n_off ON hn.Note_ID = n_off.Note_ID
        LEFT JOIN Perfume perfume_des ON t.Desired_Perfume_ID = perfume_des.Perfume_ID
        LEFT JOIN Notes note_des ON t.Desired_Note_ID = note_des.Note_ID
        GROUP BY t.Trade_ID
        ORDER BY t.Created_at DESC
    ");
    $stmt->execute();
    $trades = $stmt->fetchAll();
} catch (Exception $e) {
    $trades = [];
    if (!$error) {
        $error = 'Failed to load trades: ' . htmlspecialchars($e->getMessage());
    }
}

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
    <h3>Post a Trade Request</h3>
    <form method="POST" action="trades.php">
        <input type="hidden" name="action" value="add_trade">
        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="flex: 1; min-width: 250px; padding: 20px; border: 1px solid #10b981; border-radius: 8px; background: #ecfdf5;">
                <h4 style="color: #047857; margin-top: 0; margin-bottom: 15px;">📤 I am Offering</h4>
                <label for="offering_perfume_id" style="display: block; font-weight: bold; margin-bottom: 8px;">Select Perfume:</label>
                <select id="offering_perfume_id" name="offering_perfume_id" required style="width: 100%; padding: 10px; border: 1px solid #a7f3d0; border-radius: 6px;">
                    <option value="">-- Choose a Perfume --</option>
                    <?php 
                    $perfumeList = $pdo->query("SELECT p.Perfume_ID, p.Name, b.Brand_Name FROM Perfume p JOIN Brand b ON p.Brand_ID = b.Brand_ID ORDER BY b.Brand_Name, p.Name")->fetchAll();
                    foreach ($perfumeList as $perf): ?>
                        <option value="<?= $perf['Perfume_ID'] ?>">
                            <?= htmlspecialchars((string) $perf['Brand_Name']) ?> - <?= htmlspecialchars((string) $perf['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 250px; padding: 20px; border: 1px solid #f59e0b; border-radius: 8px; background: #fffbeb;">
                <h4 style="color: #b45309; margin-top: 0; margin-bottom: 15px;">📥 I want to Get</h4>
                <p style="margin-top: 0; margin-bottom: 10px; font-size: 0.9em; color: #92400e;">Choose either a specific perfume OR a preferred note.</p>
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label for="desired_perfume_id" style="display: block; font-weight: bold; margin-bottom: 8px;">Perfume:</label>
                        <select id="desired_perfume_id" name="desired_perfume_id" style="width: 100%; padding: 10px; border: 1px solid #fde68a; border-radius: 6px;">
                            <option value="">-- None --</option>
                            <?php foreach ($perfumeList as $perf): ?>
                                <option value="<?= $perf['Perfume_ID'] ?>">
                                    <?= htmlspecialchars((string) $perf['Brand_Name']) ?> - <?= htmlspecialchars((string) $perf['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="desired_note_id" style="display: block; font-weight: bold; margin-bottom: 8px;">Note:</label>
                        <select id="desired_note_id" name="desired_note_id" style="width: 100%; padding: 10px; border: 1px solid #fde68a; border-radius: 6px;">
                            <option value="">-- None --</option>
                            <?php 
                            $noteList = $pdo->query("SELECT Note_ID, Note_Name FROM Notes ORDER BY Note_Name")->fetchAll();
                            foreach ($noteList as $note): ?>
                                <option value="<?= $note['Note_ID'] ?>">
                                    <?= htmlspecialchars((string) $note['Note_Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="desired_type" id="hidden_desired_type" value="perfume">
            </div>
        </div>

        <button type="submit" style="width: 100%; font-size: 1.1em; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Post Trade Request</button>
    </form>
    
    <script>
        const DP = document.getElementById('desired_perfume_id');
        const DN = document.getElementById('desired_note_id');
        const DT = document.getElementById('hidden_desired_type');
        
        DP.addEventListener('change', () => {
            if (DP.value) { DN.value = ""; DT.value = "perfume"; }
        });
        DN.addEventListener('change', () => {
            if (DN.value) { DP.value = ""; DT.value = "note"; }
        });
    </script>
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
                        <small><?= htmlspecialchars((string) ($trade['Offering_Perfume_Name'] ?? 'Unknown')) ?></small>
                        <?php if ($trade['Offering_Notes']): ?>
                            <br><small style="color: #6b7280; font-size: 0.9rem;">📝 Notes: <?= htmlspecialchars((string) $trade['Offering_Notes']) ?></small>
                        <?php endif; ?>
                        <br><br>
                        <small><strong>📥 Looking for:</strong></small><br>
                        <?php if ($trade['Desired_Perfume_Name']): ?>
                            <small><?= htmlspecialchars((string) $trade['Desired_Perfume_Name']) ?></small>
                        <?php elseif ($trade['Desired_Note_Name']): ?>
                            <small><?= htmlspecialchars((string) $trade['Desired_Note_Name']) ?></small>
                        <?php else: ?>
                            <small>Unknown</small>
                        <?php endif; ?>
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
                                <small><strong>📧 Email:</strong> <a href="mailto:<?= htmlspecialchars((string) $trade['Email']) ?>" style="color: #2563eb;"><?= htmlspecialchars((string) $trade['Email']) ?></a></small><br>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (is_logged_in() && $trade['Status'] === 'Pending' && (int) $trade['User_ID'] !== $userId): ?>
                        <form method="POST" action="trades.php" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="accept_trade">
                            <input type="hidden" name="trade_id" value="<?= $trade['Trade_ID'] ?>">
                            <button type="submit" style="background: #10b981; width: 100%; padding: 10px;">✓ Accept This Trade</button>
                        </form>
                    <?php elseif (is_logged_in() && $trade['Status'] === 'Pending' && (int) $trade['User_ID'] === $userId): ?>
                        <form method="POST" action="trades.php" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="cancel_trade">
                            <input type="hidden" name="trade_id" value="<?= $trade['Trade_ID'] ?>">
                            <button type="submit" style="background: #e74c3c; width: 100%; padding: 10px;">✕ Cancel Trade</button>
                        </form>
                    <?php elseif ($trade['Status'] !== 'Pending'): ?>
                        <div style="margin-top: 12px; padding: 10px; background: #e0e7ff; border-radius: 6px; text-align: center; color: #4338ca; font-weight: bold;">
                            ✓ Trade <?= htmlspecialchars((string) $trade['Status']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
