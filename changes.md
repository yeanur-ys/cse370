# Scentology — Complete Implementation Guide

## Overview of Issues Found

| # | Feature | Status | Problem |
|---|---------|--------|---------|
| 1 | Search | ❌ Missing | No search.php, no search bar, no filter by price/year |
| 2 | My Collection | ❌ Missing | No DB table, no PHP functions, profile tab is a stub |
| 3 | Shops Map | ❌ Missing | Coordinates stored but rendered as text only |
| 4 | Review History | ❌ Missing | Profile has no tab for user's own reviews |
| 5 | Note filter bug | 🐛 Bug | `$noteFilter` declared in perfumes.php but never used in SQL |
| 6 | Profile collection bug | 🐛 Bug | `$collection` passed to `update_profile()` but silently dropped |
| 7 | Listing management | ⚠️ Partial | Sellers cannot cancel/delete their own listings |
| 8 | Trade management | ⚠️ Partial | Users cannot cancel their own pending trades |
| 9 | Wishlist display | ⚠️ Partial | Price and image missing from wishlist.php |
| 10 | Add to Collection | ❌ Missing | No button on perfume-detail.php |
| 11 | Header nav | ⚠️ Missing | No Search link in navigation |
| 12 | Perfume text search | ❌ Missing | Only brand filter exists, no name/note text search |

---

## Step 0 — Database Migration

Run this SQL once. It adds the `Collection` table (the only missing table for all planned features).

**File: `database/add_collection_table.sql`** (create new file)

```sql
CREATE TABLE IF NOT EXISTS Collection (
    User_ID    INT NOT NULL,
    Perfume_ID INT NOT NULL,
    Purchase_Date DATE NULL,
    Notes      VARCHAR(255) NULL,
    Added_At   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (User_ID, Perfume_ID),
    FOREIGN KEY (User_ID)    REFERENCES User(User_ID)    ON DELETE CASCADE,
    FOREIGN KEY (Perfume_ID) REFERENCES Perfume(Perfume_ID) ON DELETE CASCADE
);
```

Also add the execution of this file at the bottom of `public/init-db.php` (inside the existing try block, after the insert_perfumes.sql block):

```php
// After existing insert block, before the verify block:
$collectionFile = __DIR__ . '/../database/add_collection_table.sql';
if (file_exists($collectionFile)) {
    $collectionSql = file_get_contents($collectionFile);
    try {
        $pdo->exec($collectionSql);
        echo "<p>✓ Collection table ready</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "<p style='color:orange'>Collection table warning: " . $e->getMessage() . "</p>";
        }
    }
}
```

---

## File 1 — `app/auth.php`

**Add these five functions** at the bottom of the file (before the closing `?>` if one exists, or just append):

```php
// ── Collection helpers ────────────────────────────────────────────────────

function get_user_collection(int $userId): array
{
    $stmt = db()->prepare("
        SELECT c.Perfume_ID, c.Purchase_Date, c.Notes, c.Added_At,
               p.Name, p.Price, p.Image_URL, b.Brand_Name
        FROM Collection c
        JOIN Perfume p ON c.Perfume_ID = p.Perfume_ID
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        WHERE c.User_ID = ?
        ORDER BY c.Added_At DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function is_in_collection(int $userId, int $perfumeId): bool
{
    $stmt = db()->prepare("SELECT 1 FROM Collection WHERE User_ID = ? AND Perfume_ID = ?");
    $stmt->execute([$userId, $perfumeId]);
    return (bool) $stmt->fetch();
}

function add_to_collection(int $userId, int $perfumeId, ?string $purchaseDate, string $notes = ''): void
{
    $stmt = db()->prepare("
        INSERT IGNORE INTO Collection (User_ID, Perfume_ID, Purchase_Date, Notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $perfumeId, $purchaseDate ?: null, $notes]);
}

function remove_from_collection(int $userId, int $perfumeId): void
{
    $stmt = db()->prepare("DELETE FROM Collection WHERE User_ID = ? AND Perfume_ID = ?");
    $stmt->execute([$userId, $perfumeId]);
}

// ── Review history helper ─────────────────────────────────────────────────

function get_user_reviews(int $userId): array
{
    $stmt = db()->prepare("
        SELECT r.Review_ID, r.Rating, r.Comment, r.Created_at,
               p.Perfume_ID, p.Name as Perfume_Name, b.Brand_Name
        FROM Review r
        JOIN Perfume p ON r.Perfume_ID = p.Perfume_ID
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        WHERE r.User_ID = ?
        ORDER BY r.Created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
```

---

## File 2 — `public/search.php` (NEW FILE — create it)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

$query    = trim((string) ($_GET['q']         ?? ''));
$priceMin = ($_GET['price_min'] ?? '') !== '' ? (float) $_GET['price_min'] : null;
$priceMax = ($_GET['price_max'] ?? '') !== '' ? (float) $_GET['price_max'] : null;
$yearMin  = ($_GET['year_min']  ?? '') !== '' ? (int)   $_GET['year_min']  : null;
$yearMax  = ($_GET['year_max']  ?? '') !== '' ? (int)   $_GET['year_max']  : null;
$type     = trim((string) ($_GET['type'] ?? 'all'));

$perfumeResults = $brandResults = $shopResults = [];
$searched = $query !== '' || $priceMin !== null || $priceMax !== null
                           || $yearMin !== null  || $yearMax !== null;

if ($searched) {
    try {
        $pdo = db();

        // ── Perfume search ────────────────────────────────────────────
        if ($type === 'all' || $type === 'perfume') {
            $sql    = "
                SELECT p.Perfume_ID, p.Name, p.Price, p.Release_Year, p.Image_URL,
                       b.Brand_Name,
                       GROUP_CONCAT(n.Note_Name SEPARATOR ', ') as Notes
                FROM Perfume p
                JOIN Brand b ON p.Brand_ID = b.Brand_ID
                LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
                LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
                WHERE 1=1
            ";
            $params = [];

            if ($query !== '') {
                $sql .= " AND (p.Name LIKE ? OR b.Brand_Name LIKE ? OR n.Note_Name LIKE ?)";
                $like = "%$query%";
                $params[] = $like; $params[] = $like; $params[] = $like;
            }
            if ($priceMin !== null) { $sql .= " AND p.Price >= ?"; $params[] = $priceMin; }
            if ($priceMax !== null) { $sql .= " AND p.Price <= ?"; $params[] = $priceMax; }
            if ($yearMin  !== null) { $sql .= " AND p.Release_Year >= ?"; $params[] = $yearMin; }
            if ($yearMax  !== null) { $sql .= " AND p.Release_Year <= ?"; $params[] = $yearMax; }

            $sql .= " GROUP BY p.Perfume_ID ORDER BY p.Name LIMIT 40";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $perfumeResults = $stmt->fetchAll();
        }

        // ── Brand search (text only) ──────────────────────────────────
        if (($type === 'all' || $type === 'brand') && $query !== '') {
            $stmt = $pdo->prepare("
                SELECT b.Brand_ID, b.Brand_Name, COUNT(p.Perfume_ID) as Perfume_Count
                FROM Brand b LEFT JOIN Perfume p ON b.Brand_ID = p.Brand_ID
                WHERE b.Brand_Name LIKE ?
                GROUP BY b.Brand_ID ORDER BY b.Brand_Name LIMIT 10
            ");
            $stmt->execute(["%$query%"]);
            $brandResults = $stmt->fetchAll();
        }

        // ── Shop search (text only) ───────────────────────────────────
        if (($type === 'all' || $type === 'shop') && $query !== '') {
            $stmt = $pdo->prepare("
                SELECT Shop_ID, Shop_Name, Address, Stock
                FROM Shop
                WHERE Shop_Name LIKE ? OR Address LIKE ? OR Stock LIKE ?
                ORDER BY Shop_Name LIMIT 10
            ");
            $like = "%$query%";
            $stmt->execute([$like, $like, $like]);
            $shopResults = $stmt->fetchAll();
        }

    } catch (Exception $e) {
        $searchError = 'Search error: ' . htmlspecialchars($e->getMessage());
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>🔍 Search</h2>
    <form method="GET" action="search.php">
        <label for="q">Keyword</label>
        <input type="text" id="q" name="q"
               value="<?= htmlspecialchars($query) ?>"
               placeholder="Search perfume name, brand, note, shop...">

        <label for="type">Search In</label>
        <select id="type" name="type">
            <option value="all"    <?= $type === 'all'    ? 'selected' : '' ?>>All</option>
            <option value="perfume"<?= $type === 'perfume'? 'selected' : '' ?>>Perfumes</option>
            <option value="brand"  <?= $type === 'brand'  ? 'selected' : '' ?>>Brands</option>
            <option value="shop"   <?= $type === 'shop'   ? 'selected' : '' ?>>Shops</option>
        </select>

        <label>Price Range (৳)</label>
        <div style="display:flex;gap:10px;">
            <input type="number" name="price_min" min="0" style="flex:1"
                   value="<?= $priceMin !== null ? htmlspecialchars((string)$priceMin) : '' ?>"
                   placeholder="Min">
            <input type="number" name="price_max" min="0" style="flex:1"
                   value="<?= $priceMax !== null ? htmlspecialchars((string)$priceMax) : '' ?>"
                   placeholder="Max">
        </div>

        <label>Release Year Range</label>
        <div style="display:flex;gap:10px;">
            <input type="number" name="year_min" min="1900" max="2030" style="flex:1"
                   value="<?= $yearMin !== null ? htmlspecialchars((string)$yearMin) : '' ?>"
                   placeholder="From year">
            <input type="number" name="year_max" min="1900" max="2030" style="flex:1"
                   value="<?= $yearMax !== null ? htmlspecialchars((string)$yearMax) : '' ?>"
                   placeholder="To year">
        </div>

        <button type="submit">Search</button>
    </form>
</div>

<?php if (isset($searchError)): ?>
    <div class="alert error"><?= $searchError ?></div>
<?php endif; ?>

<?php if ($searched): ?>

    <?php if (count($perfumeResults) > 0): ?>
    <div class="card">
        <h3>Perfumes (<?= count($perfumeResults) ?>)</h3>
        <div class="grid">
            <?php foreach ($perfumeResults as $p): ?>
                <div class="shop-item">
                    <?php if ($p['Image_URL']): ?>
                        <div style="width:100%;height:160px;overflow:hidden;border-radius:8px;margin-bottom:10px;">
                            <img src="<?= htmlspecialchars(asset_image_url((string)$p['Image_URL'])) ?>"
                                 alt="<?= htmlspecialchars((string)$p['Name']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    <?php endif; ?>
                    <strong>
                        <a href="perfume-detail.php?id=<?= $p['Perfume_ID'] ?>"
                           style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars((string)$p['Name']) ?>
                        </a>
                    </strong><br>
                    <small><strong>Brand:</strong> <?= htmlspecialchars((string)$p['Brand_Name']) ?></small><br>
                    <?php if ($p['Price']): ?>
                        <small style="color:#e74c3c;font-weight:bold;">৳ <?= number_format((float)$p['Price']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($p['Release_Year']): ?>
                        <small><strong>Year:</strong> <?= htmlspecialchars((string)$p['Release_Year']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($p['Notes']): ?>
                        <small><strong>Notes:</strong> <?= htmlspecialchars(substr((string)$p['Notes'], 0, 60)) ?>...</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($brandResults) > 0): ?>
    <div class="card">
        <h3>Brands (<?= count($brandResults) ?>)</h3>
        <div class="grid">
            <?php foreach ($brandResults as $b): ?>
                <div class="shop-item">
                    <strong>
                        <a href="perfumes.php?brand=<?= $b['Brand_ID'] ?>"
                           style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars((string)$b['Brand_Name']) ?>
                        </a>
                    </strong><br>
                    <small><?= (int)$b['Perfume_Count'] ?> perfumes</small><br>
                    <a href="perfumes.php?brand=<?= $b['Brand_ID'] ?>" style="color:#007bff;">View Perfumes →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($shopResults) > 0): ?>
    <div class="card">
        <h3>Shops (<?= count($shopResults) ?>)</h3>
        <div class="grid">
            <?php foreach ($shopResults as $s): ?>
                <div class="shop-item">
                    <strong><?= htmlspecialchars((string)$s['Shop_Name']) ?></strong><br>
                    <small>📍 <?= htmlspecialchars((string)$s['Address']) ?></small><br>
                    <?php if ($s['Stock']): ?>
                        <small><strong>Stock:</strong> <?= htmlspecialchars((string)$s['Stock']) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($perfumeResults) === 0 && count($brandResults) === 0 && count($shopResults) === 0): ?>
        <div class="card"><p>No results found. Try different keywords or broaden your filters.</p></div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 3 — `public/perfumes.php` (REPLACE ENTIRELY)

Key changes:
- Added `$search`, `$priceMin`, `$priceMax`, `$yearMin`, `$yearMax` GET params
- Fixed the unused `$noteFilter` bug (removed dead variable)
- Extended the SQL WHERE clause to handle all new filters
- Extended the filter form with new inputs

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

$userId = current_user_id();
$user   = $userId !== null ? get_user_with_profile($userId) : null;

$brandFilter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$search      = trim((string)($_GET['search']    ?? ''));
$priceMin    = ($_GET['price_min'] ?? '') !== '' ? (float)$_GET['price_min'] : null;
$priceMax    = ($_GET['price_max'] ?? '') !== '' ? (float)$_GET['price_max'] : null;
$yearMin     = ($_GET['year_min']  ?? '') !== '' ? (int)$_GET['year_min']    : null;
$yearMax     = ($_GET['year_max']  ?? '') !== '' ? (int)$_GET['year_max']    : null;

try {
    $pdo = db();

    $where  = ['1=1'];
    $params = [];

    if ($brandFilter > 0) {
        $where[]  = 'b.Brand_ID = ?';
        $params[] = $brandFilter;
    }
    if ($search !== '') {
        $where[]  = '(p.Name LIKE ? OR b.Brand_Name LIKE ? OR n.Note_Name LIKE ?)';
        $like     = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($priceMin !== null) { $where[] = 'p.Price >= ?'; $params[] = $priceMin; }
    if ($priceMax !== null) { $where[] = 'p.Price <= ?'; $params[] = $priceMax; }
    if ($yearMin  !== null) { $where[] = 'p.Release_Year >= ?'; $params[] = $yearMin; }
    if ($yearMax  !== null) { $where[] = 'p.Release_Year <= ?'; $params[] = $yearMax; }

    $whereClause = implode(' AND ', $where);

    $query = "
        SELECT p.Perfume_ID, p.Name, p.Release_Year, p.Price, p.Image_URL,
               b.Brand_Name, b.Brand_ID,
               GROUP_CONCAT(DISTINCT n.Note_Name SEPARATOR ', ') as Notes
        FROM Perfume p
        JOIN Brand b ON p.Brand_ID = b.Brand_ID
        LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
        LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
        WHERE $whereClause
        GROUP BY p.Perfume_ID
        ORDER BY b.Brand_Name, p.Name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $perfumes = $stmt->fetchAll();

    $brandStmt = $pdo->prepare('SELECT Brand_ID, Brand_Name FROM Brand ORDER BY Brand_Name');
    $brandStmt->execute();
    $brands = $brandStmt->fetchAll();

} catch (Exception $e) {
    $perfumes = [];
    $brands   = [];
    $dbError  = "Database error: " . $e->getMessage() . ". <a href='init-db.php'>Initialize database</a>";
}

// Wishlist IDs for the logged-in user
$userWishlist = [];
if ($user && isset($pdo)) {
    $wStmt = $pdo->prepare('SELECT Perfume_ID FROM Wishlist WHERE User_ID = ?');
    $wStmt->execute([(int)$user['id']]);
    $userWishlist = array_map('intval', array_column($wStmt->fetchAll(), 'Perfume_ID'));
}

// Handle wishlist toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && $user && isset($pdo)) {
    $perfumeId = (int)($_POST['perfume_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    if ($action === 'add_wishlist' && $perfumeId > 0) {
        $pdo->prepare('INSERT IGNORE INTO Wishlist (Perfume_ID, User_ID) VALUES (?, ?)')
            ->execute([$perfumeId, (int)$user['id']]);
    } elseif ($action === 'remove_wishlist' && $perfumeId > 0) {
        $pdo->prepare('DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?')
            ->execute([$perfumeId, (int)$user['id']]);
    }
    header('Location: perfumes.php?' . http_build_query(array_filter([
        'brand'     => $brandFilter ?: null,
        'search'    => $search    ?: null,
        'price_min' => $priceMin,
        'price_max' => $priceMax,
        'year_min'  => $yearMin,
        'year_max'  => $yearMax,
    ])));
    exit;
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Perfume Catalog</h2>
    <p>Browse our collection of premium perfumes.</p>
</div>

<?php if (isset($dbError)): ?>
    <div class="alert error"><strong>⚠️ Database Error:</strong> <?= $dbError ?></div>
<?php endif; ?>

<div class="card">
    <h3>Filter & Search</h3>
    <form method="GET" action="perfumes.php">

        <label for="search">Keyword (name, brand, note)</label>
        <input type="text" id="search" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="e.g. Creed, woody, rose...">

        <label for="brand">Brand</label>
        <select id="brand" name="brand">
            <option value="">All Brands</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['Brand_ID'] ?>"
                    <?= $brandFilter == $brand['Brand_ID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$brand['Brand_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Price Range (৳)</label>
        <div style="display:flex;gap:10px;">
            <input type="number" name="price_min" min="0" style="flex:1"
                   value="<?= $priceMin !== null ? htmlspecialchars((string)$priceMin) : '' ?>"
                   placeholder="Min price">
            <input type="number" name="price_max" min="0" style="flex:1"
                   value="<?= $priceMax !== null ? htmlspecialchars((string)$priceMax) : '' ?>"
                   placeholder="Max price">
        </div>

        <label>Release Year Range</label>
        <div style="display:flex;gap:10px;">
            <input type="number" name="year_min" min="1900" max="2030" style="flex:1"
                   value="<?= $yearMin !== null ? htmlspecialchars((string)$yearMin) : '' ?>"
                   placeholder="From year">
            <input type="number" name="year_max" min="1900" max="2030" style="flex:1"
                   value="<?= $yearMax !== null ? htmlspecialchars((string)$yearMax) : '' ?>"
                   placeholder="To year">
        </div>

        <div style="display:flex;gap:10px;margin-top:10px;">
            <button type="submit">Apply Filters</button>
            <a href="perfumes.php" style="padding:8px 16px;background:#6b7280;color:white;border-radius:4px;text-decoration:none;">Clear</a>
        </div>
    </form>
</div>

<div class="card">
    <h3>Perfumes (<?= count($perfumes) ?>)</h3>

    <?php if (count($perfumes) === 0): ?>
        <p>No perfumes match your filters. <a href="perfumes.php">Clear filters</a></p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($perfumes as $perfume): ?>
                <div class="shop-item">
                    <div style="width:100%;height:180px;overflow:hidden;border-radius:8px;margin-bottom:10px;background:#e0e0e0;">
                        <?php if ($perfume['Image_URL']): ?>
                            <img src="<?= htmlspecialchars(asset_image_url((string)$perfume['Image_URL'])) ?>"
                                 alt="<?= htmlspecialchars((string)$perfume['Name']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;">🧴 No image</div>
                        <?php endif; ?>
                    </div>

                    <a href="perfume-detail.php?id=<?= $perfume['Perfume_ID'] ?>"
                       style="color:inherit;text-decoration:none;font-weight:bold;display:block;margin:8px 0;line-height:1.3;">
                        <?= htmlspecialchars((string)$perfume['Name']) ?>
                    </a>

                    <small style="display:block;margin:4px 0;">
                        <strong>Brand:</strong> <?= htmlspecialchars((string)$perfume['Brand_Name']) ?>
                    </small>
                    <?php if ($perfume['Price']): ?>
                        <small style="color:#e74c3c;font-weight:bold;display:block;margin:4px 0;">
                            💰 ৳ <?= number_format((float)$perfume['Price']) ?>
                        </small>
                    <?php endif; ?>
                    <?php if ($perfume['Release_Year']): ?>
                        <small style="display:block;margin:4px 0;">
                            <strong>Year:</strong> <?= htmlspecialchars((string)$perfume['Release_Year']) ?>
                        </small>
                    <?php endif; ?>
                    <?php if ($perfume['Notes']): ?>
                        <small style="display:block;margin:4px 0;">
                            <strong>Notes:</strong> <?= htmlspecialchars((string)$perfume['Notes']) ?>
                        </small>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <form method="POST" action="perfumes.php" style="display:inline;">
                            <?php
                            // Carry current filters so redirect preserves them
                            $filterInputs = http_build_query(array_filter([
                                'brand'     => $brandFilter ?: null,
                                'search'    => $search    ?: null,
                                'price_min' => $priceMin,
                                'price_max' => $priceMax,
                                'year_min'  => $yearMin,
                                'year_max'  => $yearMax,
                            ]));
                            foreach (explode('&', $filterInputs) as $pair) {
                                [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
                                if ($k !== '') echo "<input type='hidden' name='".htmlspecialchars(urldecode($k))."' value='".htmlspecialchars(urldecode($v))."'>";
                            }
                            ?>
                            <input type="hidden" name="perfume_id" value="<?= $perfume['Perfume_ID'] ?>">
                            <?php if (in_array($perfume['Perfume_ID'], $userWishlist)): ?>
                                <button type="submit" name="action" value="remove_wishlist"
                                        class="btn-small" style="background:#ff6b6b;">
                                    ❤️ Remove from Wishlist
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="add_wishlist" class="btn-small">
                                    🤍 Add to Wishlist
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <small><a href="login.php">Login to wishlist</a></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 4 — `public/shops.php` (REPLACE ENTIRELY)

Key changes:
- Added Leaflet.js map showing all shops with pins and popups
- Map only renders when shops have coordinates
- Inventory/stock shown in popups too

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error   = '';
$userId  = current_user_id();
$user    = $userId !== null ? get_user_with_profile($userId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        $error = 'Login required to register a shop.';
    } elseif (!$user || !$user['is_seller']) {
        $error = 'Only sellers can register a shop.';
    } else {
        $shopName  = trim((string)($_POST['shop_name']  ?? ''));
        $address   = trim((string)($_POST['address']    ?? ''));
        $latitude  = trim((string)($_POST['latitude']   ?? ''));
        $longitude = trim((string)($_POST['longitude']  ?? ''));
        $stock     = trim((string)($_POST['stock']      ?? ''));

        if ($shopName && $address) {
            $pdo  = db();
            $stmt = $pdo->prepare("INSERT INTO Shop (User_ID, Shop_Name, Address, Latitude, Longitude, Stock) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $shopName, $address, $latitude ?: null, $longitude ?: null, $stock])) {
                $success = 'Shop registered successfully.';
            } else {
                $error = 'Failed to register the shop.';
            }
        } else {
            $error = 'Shop name and address are required.';
        }
    }
}

$stmt = db()->prepare('SELECT Shop_ID, Shop_Name, Address, Latitude, Longitude, Stock FROM Shop ORDER BY Shop_ID DESC');
$stmt->execute();
$shops = $stmt->fetchAll();

// Shops that have valid coordinates for the map
$mappableShops = array_filter($shops, fn($s) => $s['Latitude'] !== null && $s['Longitude'] !== null
                                             && $s['Latitude'] !== '' && $s['Longitude'] !== '');

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Shops</h2>
    <p>Browse partner shops and their live stock. Locate them on the map below.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card">
    <h3>Register Your Shop</h3>
    <form method="POST" action="shops.php">
        <label for="shop_name">Shop Name</label>
        <input type="text" id="shop_name" name="shop_name" required>

        <label for="address">Address</label>
        <input type="text" id="address" name="address" required>

        <label for="latitude">Latitude</label>
        <input type="text" id="latitude" name="latitude" placeholder="e.g. 23.8103">

        <label for="longitude">Longitude</label>
        <input type="text" id="longitude" name="longitude" placeholder="e.g. 90.4125">

        <label for="stock">Live Stock (describe available perfumes)</label>
        <textarea id="stock" name="stock" rows="3" placeholder="What perfumes are currently available?"></textarea>

        <button type="submit">Register Shop</button>
    </form>
    <small style="color:#6b7280;">
        💡 Tip: Get your coordinates from
        <a href="https://www.google.com/maps" target="_blank" rel="noopener">Google Maps</a>
        by right-clicking your shop location.
    </small>
</div>
<?php endif; ?>

<?php if (count($mappableShops) > 0): ?>
<div class="card">
    <h3>📍 Shop Locations</h3>
    <div id="shop-map" style="width:100%;height:420px;border-radius:8px;border:1px solid #e5e7eb;"></div>
</div>

<!-- Leaflet CSS & JS from CDN (no API key required) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
(function () {
    const shops = <?= json_encode(array_values(array_map(fn($s) => [
        'name'    => $s['Shop_Name'],
        'address' => $s['Address'],
        'stock'   => $s['Stock'] ?? '',
        'lat'     => (float)$s['Latitude'],
        'lng'     => (float)$s['Longitude'],
    ], $mappableShops))) ?>;

    // Centre map on the average of all shop coordinates
    const avgLat = shops.reduce((sum, s) => sum + s.lat, 0) / shops.length;
    const avgLng = shops.reduce((sum, s) => sum + s.lng, 0) / shops.length;

    const map = L.map('shop-map').setView([avgLat, avgLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    shops.forEach(function (s) {
        const popup = `<strong>${s.name}</strong><br>📍 ${s.address}`
            + (s.stock ? `<br><small>Stock: ${s.stock}</small>` : '');
        L.marker([s.lat, s.lng]).addTo(map).bindPopup(popup);
    });

    // Fit bounds to show all markers
    if (shops.length > 1) {
        const bounds = shops.map(s => [s.lat, s.lng]);
        map.fitBounds(bounds, { padding: [30, 30] });
    }
})();
</script>
<?php else: ?>
<div class="card">
    <p style="color:#6b7280;">🗺️ No shops have provided coordinates yet. Map will appear once shops add their location details.</p>
</div>
<?php endif; ?>

<div class="card">
    <h3>All Shops (<?= count($shops) ?>)</h3>

    <?php if (count($shops) === 0): ?>
        <p>No shops registered yet.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($shops as $shop): ?>
                <div class="shop-item">
                    <strong>🏪 <?= htmlspecialchars((string)$shop['Shop_Name']) ?></strong><br>
                    <small>📍 <?= htmlspecialchars((string)$shop['Address']) ?></small><br>
                    <small>📦 <strong>Stock:</strong> <?= htmlspecialchars((string)($shop['Stock'] ?: 'No stock notes yet')) ?></small><br>
                    <?php if ($shop['Latitude'] && $shop['Longitude']): ?>
                        <small style="color:#6b7280;">
                            🌐 <?= htmlspecialchars((string)$shop['Latitude']) ?>,
                               <?= htmlspecialchars((string)$shop['Longitude']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 5 — `public/perfume-detail.php` (REPLACE ENTIRELY)

Key changes:
- Added `require_once assets.php` (already present — keep it)
- Added Collection check (`is_in_collection`)
- Added `add_collection` / `remove_collection` POST handlers
- Added "Add to My Collection" form with purchase date & notes inputs below the wishlist button

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

$userId = current_user_id();
$user   = $userId !== null ? get_user_with_profile($userId) : null;
$perfumeId = (int)($_GET['id'] ?? 0);

if ($perfumeId <= 0) {
    header('Location: perfumes.php');
    exit;
}

$pdo = db();

$perfumeStmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, p.Release_Year,
           b.Brand_Name, b.Brand_ID,
           GROUP_CONCAT(n.Note_Name SEPARATOR ', ') as Notes
    FROM Perfume p
    JOIN Brand b ON p.Brand_ID = b.Brand_ID
    LEFT JOIN Has_Notes hn ON p.Perfume_ID = hn.Perfume_ID
    LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
    WHERE p.Perfume_ID = ?
    GROUP BY p.Perfume_ID
");
$perfumeStmt->execute([$perfumeId]);
$perfume = $perfumeStmt->fetch();

if (!$perfume) {
    header('Location: perfumes.php');
    exit;
}

$perfume['Notes'] = $perfume['Notes'] ?? '';
$perfume['Price'] = (float)($perfume['Price'] ?? 0);
$perfume['Name']  = (string)($perfume['Name']  ?? 'Unknown Perfume');

$reviewStmt = $pdo->prepare("
    SELECT r.Review_ID, r.Rating, r.Comment, r.Created_at, u.User_Name
    FROM Review r JOIN User u ON r.User_ID = u.User_ID
    WHERE r.Perfume_ID = ?
    ORDER BY r.Created_at DESC
");
$reviewStmt->execute([$perfumeId]);
$reviews = $reviewStmt->fetchAll();

// Wishlist & Collection state
$inWishlist    = false;
$inCollection  = false;
if ($user) {
    $wStmt = $pdo->prepare("SELECT 1 FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?");
    $wStmt->execute([$perfumeId, $user['id']]);
    $inWishlist = (bool)$wStmt->fetch();

    $inCollection = is_in_collection((int)$user['id'], $perfumeId);
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_wishlist') {
        $pdo->prepare("INSERT IGNORE INTO Wishlist (Perfume_ID, User_ID) VALUES (?, ?)")
            ->execute([$perfumeId, $user['id']]);
        $inWishlist = true;

    } elseif ($action === 'remove_wishlist') {
        $pdo->prepare("DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?")
            ->execute([$perfumeId, $user['id']]);
        $inWishlist = false;

    } elseif ($action === 'add_collection') {
        $purchaseDate = trim((string)($_POST['purchase_date'] ?? ''));
        $collNotes    = trim((string)($_POST['collection_notes'] ?? ''));
        add_to_collection((int)$user['id'], $perfumeId,
                          $purchaseDate !== '' ? $purchaseDate : null,
                          $collNotes);
        $inCollection = true;

    } elseif ($action === 'remove_collection') {
        remove_from_collection((int)$user['id'], $perfumeId);
        $inCollection = false;

    } elseif ($action === 'add_review') {
        $rating  = (int)($_POST['rating']  ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($rating >= 1 && $rating <= 5) {
            $pdo->prepare("
                INSERT INTO Review (Perfume_ID, User_ID, Rating, Comment)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE Rating = ?, Comment = ?, Created_at = NOW()
            ")->execute([$perfumeId, $user['id'], $rating, $comment, $rating, $comment]);
            header("Location: perfume-detail.php?id=$perfumeId");
            exit;
        }
    }
}

// Average rating
$avgRating = 0;
if (count($reviews) > 0) {
    $avgRating = round(array_sum(array_column($reviews, 'Rating')) / count($reviews), 1);
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <div style="display:flex;gap:30px;flex-wrap:wrap;">

        <!-- Image -->
        <div style="flex:1;min-width:280px;">
            <div style="background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;min-height:380px;">
                <?php if ($perfume['Image_URL']): ?>
                    <img src="<?= htmlspecialchars(asset_image_url((string)$perfume['Image_URL'])) ?>"
                         alt="<?= htmlspecialchars((string)$perfume['Name']) ?>"
                         style="width:100%;max-width:400px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);"
                         onerror="this.style.display='none';this.parentElement.innerHTML='<div style=&quot;text-align:center;padding:40px;&quot;><div style=&quot;font-size:48px;&quot;>🧴</div><p>Image unavailable</p></div>'">
                <?php else: ?>
                    <div style="text-align:center;padding:40px;">
                        <div style="font-size:48px;">🧴</div>
                        <p>No image available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details -->
        <div style="flex:1;min-width:280px;">
            <h1><?= htmlspecialchars((string)$perfume['Name']) ?></h1>

            <p><strong>Brand:</strong>
                <a href="perfumes.php?brand=<?= $perfume['Brand_ID'] ?>">
                    <?= htmlspecialchars((string)$perfume['Brand_Name']) ?>
                </a>
            </p>

            <?php if ($perfume['Release_Year']): ?>
                <p><strong>Release Year:</strong> <?= htmlspecialchars((string)$perfume['Release_Year']) ?></p>
            <?php endif; ?>

            <?php if ($perfume['Price']): ?>
                <p style="font-size:22px;color:#e74c3c;">
                    <strong>💰 ৳<?= number_format((float)$perfume['Price']) ?></strong>
                </p>
            <?php endif; ?>

            <?php if ($perfume['Notes']): ?>
                <p><strong>Fragrance Notes:</strong><br><?= htmlspecialchars((string)$perfume['Notes']) ?></p>
            <?php endif; ?>

            <?php if (count($reviews) > 0): ?>
                <div style="padding:10px;background:#f0f0f0;border-radius:5px;margin:15px 0;">
                    <strong>Average Rating:</strong> ⭐ <?= $avgRating ?>/5 (<?= count($reviews) ?> reviews)
                </div>
            <?php endif; ?>

            <?php if (is_logged_in()): ?>

                <!-- Wishlist button -->
                <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin:12px 0 6px;">
                    <?php if ($inWishlist): ?>
                        <button type="submit" name="action" value="remove_wishlist"
                                style="background:#ff6b6b;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;color:white;">
                            ❤️ Remove from Wishlist
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="add_wishlist"
                                style="background:#3498db;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;color:white;">
                            🤍 Add to Wishlist
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Collection button / form -->
                <?php if ($inCollection): ?>
                    <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>" style="margin:6px 0;">
                        <button type="submit" name="action" value="remove_collection"
                                style="background:#6b7280;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;color:white;">
                            📦 Remove from Collection
                        </button>
                    </form>
                <?php else: ?>
                    <details style="margin:6px 0;">
                        <summary style="cursor:pointer;background:#10b981;color:white;padding:10px 20px;border-radius:5px;list-style:none;display:inline-block;">
                            📦 Add to My Collection
                        </summary>
                        <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>"
                              style="margin-top:10px;padding:12px;background:#f0fdf4;border-radius:6px;border:1px solid #bbf7d0;">
                            <label for="purchase_date" style="display:block;margin-bottom:4px;font-weight:bold;">Purchase Date (optional)</label>
                            <input type="date" id="purchase_date" name="purchase_date"
                                   style="width:100%;margin-bottom:10px;padding:6px;border:1px solid #d1d5db;border-radius:4px;box-sizing:border-box;">
                            <label for="collection_notes" style="display:block;margin-bottom:4px;font-weight:bold;">Notes (optional)</label>
                            <input type="text" id="collection_notes" name="collection_notes"
                                   placeholder="e.g. Gift from sister, 100ml bottle"
                                   style="width:100%;margin-bottom:10px;padding:6px;border:1px solid #d1d5db;border-radius:4px;box-sizing:border-box;">
                            <button type="submit" name="action" value="add_collection"
                                    style="background:#10b981;padding:8px 16px;border:none;border-radius:5px;cursor:pointer;color:white;">
                                ✓ Add to Collection
                            </button>
                        </form>
                    </details>
                <?php endif; ?>

            <?php else: ?>
                <p>
                    <a href="login.php" style="color:#3498db;font-weight:bold;">Login</a>
                    to add to wishlist or collection.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="card">
    <h2>Reviews (<?= count($reviews) ?>)</h2>

    <?php if (is_logged_in()): ?>
        <div style="margin-bottom:24px;padding:16px;background:#f9f9f9;border-radius:8px;">
            <h3>Write a Review</h3>
            <form method="POST" action="perfume-detail.php?id=<?= $perfumeId ?>">
                <label for="rating"><strong>Rating</strong></label>
                <select name="rating" id="rating" required style="padding:8px;border:1px solid #ddd;border-radius:5px;margin-top:5px;display:block;width:100%;margin-bottom:12px;">
                    <option value="">Select rating...</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Good</option>
                    <option value="3">⭐⭐⭐ Average</option>
                    <option value="2">⭐⭐ Poor</option>
                    <option value="1">⭐ Very Poor</option>
                </select>
                <label for="comment"><strong>Comment</strong></label>
                <textarea name="comment" id="comment" rows="4"
                          style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;margin-top:5px;box-sizing:border-box;"></textarea>
                <button type="submit" name="action" value="add_review"
                        style="margin-top:10px;background:#27ae60;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;color:white;">
                    Submit Review
                </button>
            </form>
        </div>
    <?php else: ?>
        <p><a href="login.php" style="color:#3498db;font-weight:bold;">Login</a> to leave a review.</p>
    <?php endif; ?>

    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
            <div style="padding:15px;border-bottom:1px solid #eee;margin-bottom:15px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong><?= htmlspecialchars((string)$review['User_Name']) ?></strong><br>
                        <small><?= str_repeat('⭐', (int)$review['Rating']) ?> (<?= $review['Rating'] ?>/5)</small>
                    </div>
                    <small style="color:#999;"><?= date('M d, Y', strtotime((string)$review['Created_at'])) ?></small>
                </div>
                <?php if ($review['Comment']): ?>
                    <p style="margin-top:10px;color:#333;"><?= htmlspecialchars((string)$review['Comment']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#999;text-align:center;padding:20px;">No reviews yet. Be the first!</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 6 — `public/profile.php` (REPLACE ENTIRELY)

Key changes:
- Collection tab now queries the `Collection` table and shows real owned perfumes
- Added Review History tab (5th tab) showing user's own reviews
- Removed dead `$collection` field from form and `update_profile()` call
- Tab button count updated accordingly

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

require_login();

$userId  = (int)current_user_id();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $phone    = trim((string)($_POST['phone']     ?? ''));
    $city     = trim((string)($_POST['city']      ?? ''));
    $bio      = trim((string)($_POST['bio']       ?? ''));

    if ($fullName === '') {
        $error = 'Full name is required.';
    } else {
        update_profile($userId, $fullName, $phone, $city, $bio);
        $success = 'Profile updated successfully.';
    }
}

$user       = get_user_with_profile($userId);
$collection = get_user_collection($userId);
$myReviews  = get_user_reviews($userId);

require_once __DIR__ . '/partials/header.php';
?>
<div class="card">
    <h2>My Profile</h2>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Tab buttons -->
    <div class="tabs">
        <button class="tab-button active" onclick="showTab('profile',  event)">📝 Profile</button>
        <button class="tab-button"        onclick="showTab('collection', event)">💎 My Collection</button>
        <button class="tab-button"        onclick="showTab('wishlist', event)">🤍 Wishlist</button>
        <button class="tab-button"        onclick="showTab('reviews',  event)">⭐ My Reviews</button>
    </div>

    <!-- ── Profile tab ───────────────────────────────────────────────────── -->
    <div id="profile" class="tab-content active">
        <div style="background:#eef2ff;padding:12px;border-radius:8px;margin-bottom:16px;">
            <strong>Account Type:</strong>
            <?php if (!empty($user['is_seller'])): ?>
                Seller (Total Sales: <?= htmlspecialchars((string)($user['total_sell'] ?? '0')) ?>)
            <?php else: ?>
                General User
            <?php endif; ?>
        </div>

        <form method="POST" action="profile.php">
            <label for="full_name">User Name</label>
            <input type="text" id="full_name" name="full_name"
                   value="<?= htmlspecialchars((string)($user['full_name'] ?? '')) ?>" required>

            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>" disabled>

            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars((string)($user['phone'] ?? '')) ?>">

            <label for="city">City</label>
            <input type="text" id="city" name="city"
                   value="<?= htmlspecialchars((string)($user['city'] ?? '')) ?>">

            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars((string)($user['bio'] ?? '')) ?></textarea>

            <button type="submit">Update Profile</button>
        </form>
        <small style="color:#6b7280;">Member since: <?= htmlspecialchars((string)($user['created_at'] ?? 'N/A')) ?></small>
    </div>

    <!-- ── Collection tab ───────────────────────────────────────────────── -->
    <div id="collection" class="tab-content">
        <p style="margin-bottom:12px;">
            Your personal fragrance vault — perfumes you own, logged with purchase date.
            Add perfumes via their <a href="perfumes.php" style="color:#2563eb;">catalog page</a>.
        </p>

        <?php if (count($collection) === 0): ?>
            <p><em>Your collection is empty. Visit a
                <a href="perfumes.php" style="color:#2563eb;">perfume page</a>
                and click "Add to My Collection".</em>
            </p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($collection as $item): ?>
                    <div class="shop-item">
                        <?php if ($item['Image_URL']): ?>
                            <div style="width:100%;height:140px;overflow:hidden;border-radius:8px;margin-bottom:8px;">
                                <img src="<?= htmlspecialchars(asset_image_url((string)$item['Image_URL'])) ?>"
                                     alt="<?= htmlspecialchars((string)$item['Name']) ?>"
                                     style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        <?php endif; ?>
                        <strong>
                            <a href="perfume-detail.php?id=<?= $item['Perfume_ID'] ?>"
                               style="color:inherit;text-decoration:none;">
                                <?= htmlspecialchars((string)$item['Name']) ?>
                            </a>
                        </strong><br>
                        <small><?= htmlspecialchars((string)$item['Brand_Name']) ?></small><br>
                        <?php if ($item['Price']): ?>
                            <small style="color:#e74c3c;font-weight:bold;">৳ <?= number_format((float)$item['Price']) ?></small><br>
                        <?php endif; ?>
                        <?php if ($item['Purchase_Date']): ?>
                            <small>📅 Purchased: <?= htmlspecialchars((string)$item['Purchase_Date']) ?></small><br>
                        <?php endif; ?>
                        <?php if ($item['Notes']): ?>
                            <small>📝 <?= htmlspecialchars((string)$item['Notes']) ?></small><br>
                        <?php endif; ?>
                        <small style="color:#9ca3af;">Added: <?= date('M d, Y', strtotime((string)$item['Added_At'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Wishlist tab ──────────────────────────────────────────────────── -->
    <div id="wishlist" class="tab-content">
        <p><strong>❤️ Perfumes on your wishlist:</strong></p>
        <?php
        try {
            $pdo = db();
            $wStmt = $pdo->prepare("
                SELECT p.Perfume_ID, p.Name, p.Price, p.Image_URL, b.Brand_Name
                FROM Wishlist w
                JOIN Perfume p ON w.Perfume_ID = p.Perfume_ID
                JOIN Brand b ON p.Brand_ID = b.Brand_ID
                WHERE w.User_ID = ?
                ORDER BY b.Brand_Name, p.Name
            ");
            $wStmt->execute([$userId]);
            $wishlistItems = $wStmt->fetchAll();

            if (count($wishlistItems) > 0): ?>
                <div class="grid">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="shop-item">
                            <?php if ($item['Image_URL']): ?>
                                <div style="width:100%;height:140px;overflow:hidden;border-radius:8px;margin-bottom:8px;">
                                    <img src="<?= htmlspecialchars(asset_image_url((string)$item['Image_URL'])) ?>"
                                         alt="<?= htmlspecialchars((string)$item['Name']) ?>"
                                         style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <strong>
                                <a href="perfume-detail.php?id=<?= $item['Perfume_ID'] ?>"
                                   style="color:inherit;text-decoration:none;">
                                    <?= htmlspecialchars((string)$item['Name']) ?>
                                </a>
                            </strong><br>
                            <small><?= htmlspecialchars((string)$item['Brand_Name']) ?></small><br>
                            <?php if ($item['Price']): ?>
                                <small style="color:#e74c3c;font-weight:bold;">৳ <?= number_format((float)$item['Price']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><em>Your wishlist is empty. <a href="perfumes.php">Browse perfumes</a>.</em></p>
            <?php endif;
        } catch (Exception $e) { ?>
            <p style="color:#991b1b;">Error: <?= htmlspecialchars($e->getMessage()) ?></p>
        <?php } ?>
    </div>

    <!-- ── Reviews tab ──────────────────────────────────────────────────── -->
    <div id="reviews" class="tab-content">
        <p><strong>⭐ Reviews you have written:</strong></p>
        <?php if (count($myReviews) === 0): ?>
            <p><em>You have not reviewed any perfumes yet.
                <a href="perfumes.php">Browse the catalog</a> and leave your first review!</em>
            </p>
        <?php else: ?>
            <?php foreach ($myReviews as $rev): ?>
                <div style="border-left:4px solid #667eea;padding:12px;margin-bottom:12px;background:#f8f9fa;border-radius:4px;">
                    <strong>
                        <a href="perfume-detail.php?id=<?= $rev['Perfume_ID'] ?>"
                           style="color:#2563eb;text-decoration:none;">
                            <?= htmlspecialchars((string)$rev['Brand_Name']) ?> — <?= htmlspecialchars((string)$rev['Perfume_Name']) ?>
                        </a>
                    </strong><br>
                    <small><?= str_repeat('⭐', (int)$rev['Rating']) ?> (<?= $rev['Rating'] ?>/5) ·
                        <?= date('M d, Y', strtotime((string)$rev['Created_at'])) ?>
                    </small>
                    <?php if ($rev['Comment']): ?>
                        <p style="margin:8px 0 0;font-style:italic;color:#374151;">
                            "<?= htmlspecialchars((string)$rev['Comment']) ?>"
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
function showTab(tabName, event) {
    event.preventDefault();
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>
```

---

## File 7 — `public/listings.php` (REPLACE ENTIRELY)

Key changes:
- Sellers now see their own listings with a **Cancel** button
- Added status filter tabs (All / Available / Sold / Cancelled)
- POST action `cancel_listing` marks it as `Cancelled` and verifies ownership

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error   = '';
$userId  = current_user_id();
$user    = $userId !== null ? get_user_with_profile($userId) : null;

$statusFilter = trim((string)($_GET['status'] ?? 'Available'));
$allowedStatuses = ['Available', 'Sold', 'Cancelled', 'All'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'Available';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_listing') {
        if (!is_logged_in()) {
            $error = 'Login required to post a listing.';
        } elseif (!$user || !$user['is_seller']) {
            $error = 'Only sellers can post listings.';
        } else {
            $itemName  = trim((string)($_POST['item_name']       ?? ''));
            $quantity  = (int)($_POST['quantity']                ?? 1);
            $price     = (float)($_POST['price']                 ?? 0.0);
            $condition = trim((string)($_POST['item_condition']  ?? 'New'));

            if ($itemName && $price >= 0 && $quantity > 0) {
                try {
                    $pdo  = db();
                    $stmt = $pdo->prepare("INSERT INTO Listing (User_ID, Item_Name, Quantity, Price, Item_Condition) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([(int)$user['id'], $itemName, $quantity, $price, $condition]);
                    $success = 'Listing created successfully.';
                } catch (Exception $e) {
                    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = 'Invalid listing details.';
            }
        }

    } elseif ($_POST['action'] === 'cancel_listing') {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        if ($listingId > 0 && is_logged_in() && $user) {
            try {
                $pdo  = db();
                // Only the listing owner can cancel
                $stmt = $pdo->prepare("UPDATE Listing SET Status = 'Cancelled' WHERE Listing_ID = ? AND User_ID = ? AND Status = 'Available'");
                if ($stmt->execute([$listingId, (int)$user['id']]) && $stmt->rowCount() > 0) {
                    $success = 'Listing cancelled.';
                } else {
                    $error = 'Could not cancel listing (already sold or not yours).';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }

    } elseif ($_POST['action'] === 'purchase_listing') {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        if ($listingId > 0 && is_logged_in() && $user) {
            try {
                $pdo  = db();
                $stmt = $pdo->prepare("UPDATE Listing SET Status = 'Sold', Purchased_By_User_ID = ?, Purchased_At = NOW() WHERE Listing_ID = ? AND Status = 'Available'");
                if ($stmt->execute([(int)$user['id'], $listingId]) && $stmt->rowCount() > 0) {
                    $success = 'Purchase successful!';
                } else {
                    $error = 'Listing is no longer available.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Login required to purchase.';
        }
    }
}

// Fetch listings
try {
    $pdo = db();
    $sql = "
        SELECT l.*, s.Shop_Name, u.User_Name, u.Email, p.City, p.Number
        FROM Listing l
        JOIN User u ON l.User_ID = u.User_ID
        LEFT JOIN Shop s ON s.User_ID = l.User_ID
        LEFT JOIN Profile p ON u.User_ID = p.User_ID
    ";
    $filterParams = [];
    if ($statusFilter !== 'All') {
        $sql .= " WHERE l.Status = ?";
        $filterParams[] = $statusFilter;
    }
    $sql .= " ORDER BY l.Listing_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filterParams);
    $listings = $stmt->fetchAll();
} catch (Exception $e) {
    $listings = [];
    if (!$error) $error = 'Failed to load listings.';
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h2>Market Listings</h2>
    <p>Browse perfumes listed by sellers. Filter by status below.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
        <?php foreach ($allowedStatuses as $st): ?>
            <a href="listings.php?status=<?= urlencode($st) ?>"
               style="padding:6px 14px;border-radius:4px;text-decoration:none;font-size:0.9rem;
                      <?= $statusFilter === $st ? 'background:#2563eb;color:white;' : 'background:#e5e7eb;color:#374151;' ?>">
                <?= htmlspecialchars($st) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($user && $user['is_seller']): ?>
<div class="card">
    <h3>Post New Listing</h3>
    <form method="POST" action="listings.php">
        <input type="hidden" name="action" value="add_listing">

        <label for="item_name">Item Name</label>
        <input type="text" id="item_name" name="item_name" required>

        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" value="1" required>

        <label for="price">Price (৳)</label>
        <input type="number" step="0.01" id="price" name="price" min="0" required>

        <label for="item_condition">Condition</label>
        <select id="item_condition" name="item_condition">
            <option value="New">New</option>
            <option value="Used - Good">Used - Good</option>
            <option value="Used - Fair">Used - Fair</option>
        </select>

        <button type="submit">Publish Listing</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Listings (<?= count($listings) ?>)</h3>

    <?php if (count($listings) === 0): ?>
        <p>No listings found for the selected status.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($listings as $listing): ?>
                <?php
                $listingStatus = $listing['Status'] ?? 'Available';
                $isOwner = $userId !== null && (int)$listing['User_ID'] === $userId;
                ?>
                <div class="shop-item">
                    <strong style="color:#2563eb;">🏷️ <?= htmlspecialchars((string)$listing['Item_Name']) ?></strong>
                    <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:4px;font-size:0.85rem;margin:8px 0;">
                        <?= htmlspecialchars((string)$listingStatus) ?>
                    </span>

                    <div style="margin:10px 0;padding:10px;background:#f3f4f6;border-radius:6px;">
                        <small><strong>💰 Price:</strong> ৳<?= number_format((float)$listing['Price']) ?></small><br>
                        <small><strong>📦 Qty:</strong> <?= (int)$listing['Quantity'] ?></small><br>
                        <small><strong>✨ Condition:</strong> <?= htmlspecialchars((string)$listing['Item_Condition']) ?></small>
                    </div>

                    <div style="padding:10px;background:#fef3c7;border-left:3px solid #f59e0b;border-radius:2px;margin:8px 0;">
                        <small><strong>👤 Seller:</strong> <?= htmlspecialchars((string)$listing['User_Name']) ?></small><br>
                        <?php if ($listing['Shop_Name']): ?>
                            <small><strong>🏪 Shop:</strong> <?= htmlspecialchars((string)$listing['Shop_Name']) ?></small><br>
                        <?php endif; ?>
                    </div>

                    <?php if ($listing['City'] || $listing['Number'] || $listing['Email']): ?>
                        <div style="padding:8px;background:#f0fdf4;border-left:3px solid #10b981;border-radius:2px;margin:6px 0;">
                            <?php if ($listing['City']): ?>
                                <small>📍 <?= htmlspecialchars((string)$listing['City']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($listing['Number']): ?>
                                <small>📱 <?= htmlspecialchars((string)$listing['Number']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($listing['Email']): ?>
                                <small>📧 <a href="mailto:<?= htmlspecialchars((string)$listing['Email']) ?>"><?= htmlspecialchars((string)$listing['Email']) ?></a></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($listingStatus === 'Available'): ?>
                        <?php if (is_logged_in() && !$isOwner): ?>
                            <form method="POST" action="listings.php?status=<?= urlencode($statusFilter) ?>" style="margin-top:10px;">
                                <input type="hidden" name="action"     value="purchase_listing">
                                <input type="hidden" name="listing_id" value="<?= $listing['Listing_ID'] ?>">
                                <button type="submit" style="background:#2563eb;width:100%;padding:10px;color:white;border:none;border-radius:4px;cursor:pointer;">
                                    🛒 Purchase
                                </button>
                            </form>
                        <?php elseif ($isOwner): ?>
                            <form method="POST" action="listings.php?status=<?= urlencode($statusFilter) ?>" style="margin-top:10px;"
                                  onsubmit="return confirm('Cancel this listing?');">
                                <input type="hidden" name="action"     value="cancel_listing">
                                <input type="hidden" name="listing_id" value="<?= $listing['Listing_ID'] ?>">
                                <button type="submit" style="background:#ef4444;width:100%;padding:10px;color:white;border:none;border-radius:4px;cursor:pointer;">
                                    ✕ Cancel My Listing
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="margin-top:10px;padding:10px;background:#e0e7ff;border-radius:6px;text-align:center;color:#4338ca;font-weight:bold;">
                            ✓ <?= htmlspecialchars((string)$listingStatus) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 8 — `public/trades.php` (REPLACE ENTIRELY)

Key changes:
- Users can now cancel their own **Pending** trades (`cancel_trade` action)
- Verifies ownership before cancelling (WHERE Trade_ID = ? AND User_ID = ?)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$success = '';
$error   = '';
$userId  = current_user_id();
$user    = $userId !== null ? get_user_with_profile($userId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!is_logged_in()) {
        $error = 'Login required.';

    } elseif ($_POST['action'] === 'add_trade') {
        $offeringPerfumeId = (int)($_POST['offering_perfume_id'] ?? 0);
        $desiredNoteId     = (int)($_POST['desired_note_id']     ?? 0);

        if ($offeringPerfumeId > 0 && $desiredNoteId > 0) {
            try {
                $pdo  = db();
                $stmt = $pdo->prepare("INSERT INTO Trade (User_ID, Offering_Perfume_ID, Desired_Note_ID, Status) VALUES (?, ?, ?, 'Pending')");
                if ($stmt->execute([(int)$user['id'], $offeringPerfumeId, $desiredNoteId])) {
                    $success = 'Trade request posted!';
                } else {
                    $error = 'Failed to post trade request.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $error = 'Please select both a perfume to offer and a desired note.';
        }

    } elseif ($_POST['action'] === 'accept_trade') {
        $tradeId = (int)($_POST['trade_id'] ?? 0);
        if ($tradeId > 0 && $user) {
            try {
                $pdo  = db();
                $stmt = $pdo->prepare("UPDATE Trade SET Status = 'Accepted', Accepted_By_User_ID = ?, Accepted_At = NOW() WHERE Trade_ID = ? AND Status = 'Pending'");
                if ($stmt->execute([(int)$user['id'], $tradeId]) && $stmt->rowCount() > 0) {
                    $success = 'Trade accepted! Contact the other party to arrange exchange.';
                } else {
                    $error = 'Trade is no longer pending.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }

    } elseif ($_POST['action'] === 'cancel_trade') {
        $tradeId = (int)($_POST['trade_id'] ?? 0);
        if ($tradeId > 0 && $user) {
            try {
                $pdo  = db();
                // Only the trade creator can cancel, and only if still Pending
                $stmt = $pdo->prepare("UPDATE Trade SET Status = 'Cancelled' WHERE Trade_ID = ? AND User_ID = ? AND Status = 'Pending'");
                if ($stmt->execute([$tradeId, (int)$user['id']]) && $stmt->rowCount() > 0) {
                    $success = 'Trade request cancelled.';
                } else {
                    $error = 'Could not cancel trade (already accepted or not yours).';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Fetch trades
try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT t.Trade_ID, t.User_ID, t.Status, t.Created_at,
               u.User_Name, u.Email, pr.City, pr.Number,
               perfume.Name as Offering_Perfume_Name,
               GROUP_CONCAT(DISTINCT n.Note_Name SEPARATOR ', ') as Offering_Notes,
               note.Note_Name as Desired_Note_Name
        FROM Trade t
        JOIN User u ON t.User_ID = u.User_ID
        LEFT JOIN Profile pr ON u.User_ID = pr.User_ID
        LEFT JOIN Perfume perfume ON t.Offering_Perfume_ID = perfume.Perfume_ID
        LEFT JOIN Has_Notes hn ON perfume.Perfume_ID = hn.Perfume_ID
        LEFT JOIN Notes n ON hn.Note_ID = n.Note_ID
        LEFT JOIN Notes note ON t.Desired_Note_ID = note.Note_ID
        GROUP BY t.Trade_ID
        ORDER BY t.Created_at DESC
    ");
    $stmt->execute();
    $trades = $stmt->fetchAll();
} catch (Exception $e) {
    $trades = [];
    if (!$error) $error = 'Failed to load trades.';
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
    <h3>Post Trade Request</h3>
    <p>Select what you have and what notes/scent you want in return.</p>
    <form method="POST" action="trades.php">
        <input type="hidden" name="action" value="add_trade">

        <label for="offering_perfume_id" style="color:#059669;font-weight:bold;">📤 Perfume I'm Offering</label>
        <select id="offering_perfume_id" name="offering_perfume_id" required>
            <option value="">-- Select a Perfume --</option>
            <?php
            $perfumeList = $pdo->query("
                SELECT p.Perfume_ID, p.Name, b.Brand_Name
                FROM Perfume p JOIN Brand b ON p.Brand_ID = b.Brand_ID
                ORDER BY b.Brand_Name, p.Name
            ")->fetchAll();
            foreach ($perfumeList as $perf): ?>
                <option value="<?= $perf['Perfume_ID'] ?>">
                    <?= htmlspecialchars((string)$perf['Brand_Name']) ?> — <?= htmlspecialchars((string)$perf['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="desired_note_id" style="color:#d97706;font-weight:bold;margin-top:12px;display:block;">📥 Scent Notes I Want</label>
        <select id="desired_note_id" name="desired_note_id" required>
            <option value="">-- Select Desired Note --</option>
            <?php
            $noteList = $pdo->query("SELECT Note_ID, Note_Name FROM Notes ORDER BY Note_Name")->fetchAll();
            foreach ($noteList as $note): ?>
                <option value="<?= $note['Note_ID'] ?>">
                    <?= htmlspecialchars((string)$note['Note_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small style="color:#6b7280;">💡 Others will offer a perfume containing this note.</small>

        <button type="submit" style="margin-top:14px;">📮 Post Trade Request</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Active Trade Requests</h3>
    <?php if (count($trades) === 0): ?>
        <p>No trade requests yet.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($trades as $trade): ?>
                <?php
                $tradeStatus = $trade['Status'] ?? 'Pending';
                $isOwner     = $userId !== null && (int)$trade['User_ID'] === $userId;
                ?>
                <div class="shop-item">
                    <strong style="color:#2563eb;">👤 <?= htmlspecialchars((string)$trade['User_Name']) ?></strong>
                    <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:4px;font-size:0.85rem;margin:8px 0;">
                        <?= htmlspecialchars((string)$tradeStatus) ?>
                    </span>

                    <div style="padding:10px;background:#f3f4f6;border-radius:6px;margin:10px 0;">
                        <small><strong>📤 Offering:</strong>
                            <?= htmlspecialchars((string)($trade['Offering_Perfume_Name'] ?? 'Unknown')) ?>
                        </small>
                        <?php if ($trade['Offering_Notes']): ?>
                            <br><small style="color:#6b7280;">Notes: <?= htmlspecialchars((string)$trade['Offering_Notes']) ?></small>
                        <?php endif; ?>
                        <br><br>
                        <small><strong>📥 Wants note:</strong>
                            <?= htmlspecialchars((string)($trade['Desired_Note_Name'] ?? 'Unknown')) ?>
                        </small>
                    </div>

                    <?php if ($trade['City'] || $trade['Number'] || $trade['Email']): ?>
                        <div style="padding:8px;background:#f0fdf4;border-left:3px solid #10b981;border-radius:2px;margin:6px 0;">
                            <?php if ($trade['City']): ?>
                                <small>📍 <?= htmlspecialchars((string)$trade['City']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($trade['Number']): ?>
                                <small>📱 <?= htmlspecialchars((string)$trade['Number']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($trade['Email']): ?>
                                <small>📧 <a href="mailto:<?= htmlspecialchars((string)$trade['Email']) ?>"><?= htmlspecialchars((string)$trade['Email']) ?></a></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tradeStatus === 'Pending'): ?>
                        <?php if (is_logged_in() && !$isOwner): ?>
                            <form method="POST" action="trades.php" style="margin-top:10px;">
                                <input type="hidden" name="action"   value="accept_trade">
                                <input type="hidden" name="trade_id" value="<?= $trade['Trade_ID'] ?>">
                                <button type="submit" style="background:#10b981;width:100%;padding:10px;color:white;border:none;border-radius:4px;cursor:pointer;">
                                    ✓ Accept This Trade
                                </button>
                            </form>
                        <?php elseif ($isOwner): ?>
                            <form method="POST" action="trades.php" style="margin-top:10px;"
                                  onsubmit="return confirm('Cancel this trade request?');">
                                <input type="hidden" name="action"   value="cancel_trade">
                                <input type="hidden" name="trade_id" value="<?= $trade['Trade_ID'] ?>">
                                <button type="submit" style="background:#ef4444;width:100%;padding:10px;color:white;border:none;border-radius:4px;cursor:pointer;">
                                    ✕ Cancel My Trade
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="margin-top:10px;padding:10px;background:#e0e7ff;border-radius:6px;text-align:center;color:#4338ca;font-weight:bold;">
                            ✓ Trade <?= htmlspecialchars((string)$tradeStatus) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 9 — `public/wishlist.php` (REPLACE ENTIRELY)

Key changes:
- Added `Price` and `Image_URL` to the SELECT query (they were simply missing)
- Now shows perfume image and price in the wishlist cards

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/assets.php';

require_login();

$userId = (int)current_user_id();
$user   = get_user_with_profile($userId);
$pdo    = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove' && isset($_POST['perfume_id'])) {
        $perfumeId = (int)$_POST['perfume_id'];
        $pdo->prepare("DELETE FROM Wishlist WHERE Perfume_ID = ? AND User_ID = ?")
            ->execute([$perfumeId, $userId]);
        header('Location: wishlist.php');
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT p.Perfume_ID, p.Name, p.Release_Year, p.Price, p.Image_URL, b.Brand_Name
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
    <p><?= htmlspecialchars((string)$user['full_name']) ?>'s collection of desired perfumes.</p>
</div>

<?php if (count($wishlist) === 0): ?>
    <div class="card">
        <p>Your wishlist is empty. <a href="perfumes.php">Browse perfumes and add to your wishlist.</a></p>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Perfumes in Wishlist (<?= count($wishlist) ?>)</h3>
        <div class="grid">
            <?php foreach ($wishlist as $item): ?>
                <div class="shop-item">
                    <?php if ($item['Image_URL']): ?>
                        <div style="width:100%;height:160px;overflow:hidden;border-radius:8px;margin-bottom:10px;">
                            <img src="<?= htmlspecialchars(asset_image_url((string)$item['Image_URL'])) ?>"
                                 alt="<?= htmlspecialchars((string)$item['Name']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    <?php endif; ?>

                    <strong>
                        <a href="perfume-detail.php?id=<?= $item['Perfume_ID'] ?>"
                           style="color:inherit;text-decoration:none;">
                            <?= htmlspecialchars((string)$item['Name']) ?>
                        </a>
                    </strong><br>
                    <small><strong>Brand:</strong> <?= htmlspecialchars((string)$item['Brand_Name']) ?></small><br>
                    <?php if ($item['Price']): ?>
                        <small style="color:#e74c3c;font-weight:bold;">৳ <?= number_format((float)$item['Price']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($item['Release_Year']): ?>
                        <small><strong>Year:</strong> <?= htmlspecialchars((string)$item['Release_Year']) ?></small><br>
                    <?php endif; ?>

                    <form method="POST" action="wishlist.php" style="margin-top:10px;">
                        <input type="hidden" name="perfume_id" value="<?= $item['Perfume_ID'] ?>">
                        <button type="submit" name="action" value="remove"
                                class="btn-small" style="background:#ff6b6b;">
                            ✕ Remove
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
```

---

## File 10 — `public/partials/header.php` (PARTIAL change)

You need to add a **Search** link to your navigation. Since you have not shared `header.php`, find the `<nav>` block and add this link wherever your other nav links are:

```html
<a href="search.php">🔍 Search</a>
```

Place it as the first or second nav item, e.g. before the "Perfumes" link. Example structure if your nav looks like:

```html
<nav>
  <a href="index.php">Home</a>
  <a href="perfumes.php">Perfumes</a>
  <a href="brands.php">Brands</a>
  ...
</nav>
```

Change it to:

```html
<nav>
  <a href="index.php">Home</a>
  <a href="search.php">🔍 Search</a>
  <a href="perfumes.php">Perfumes</a>
  <a href="brands.php">Brands</a>
  ...
</nav>
```

---

## Implementation Checklist

Follow this order to avoid breaking anything mid-way:

1. `database/add_collection_table.sql` — create file, run via `init-db.php` or directly in phpMyAdmin
2. `app/auth.php` — append the 5 new functions at the bottom
3. `public/search.php` — create new file
4. `public/perfumes.php` — replace entirely
5. `public/shops.php` — replace entirely
6. `public/perfume-detail.php` — replace entirely
7. `public/profile.php` — replace entirely
8. `public/listings.php` — replace entirely
9. `public/trades.php` — replace entirely
10. `public/wishlist.php` — replace entirely
11. `public/partials/header.php` — add Search nav link

After deploying, visit `/public/init-db.php` once to ensure the Collection table is created.
