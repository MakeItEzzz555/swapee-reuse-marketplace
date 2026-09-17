<?php
require_once __DIR__ . '/../includes/header.php';
require_login();

function estimate_impact(string $category, string $type, string $condition): array
{
    $category = strtolower($category);
    $bases = [
        'books' => ['co2' => 5.0, 'waste' => 0.5],
        'electronics' => ['co2' => 25.0, 'waste' => 3.0],
        'lab' => ['co2' => 18.0, 'waste' => 2.2],
    ];
    $base = isset($bases[$category]) ? $bases[$category] : ['co2' => 10.0, 'waste' => 1.0];

    $conditionFactor = 1.0;
    $cond = strtolower($condition);
    if ($cond === 'like new') {
        $conditionFactor = 1.2;
    } elseif ($cond === 'good') {
        $conditionFactor = 1.0;
    } elseif ($cond === 'fair') {
        $conditionFactor = 0.8;
    }

    $typeFactor = 1.0;
    if ($type === 'donate') {
        $typeFactor = 1.15;
    } elseif ($type === 'sell') {
        $typeFactor = 0.95;
    }

    return [
        'co2' => round($base['co2'] * $conditionFactor * $typeFactor, 1),
        'waste' => round($base['waste'] * $conditionFactor * $typeFactor, 2),
    ];
}

$user = current_user();
$db = get_db();
$flash = '';

if (isset($_SESSION['flash_success'])) {
    $flash = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
} elseif (isset($_GET['posted'])) {
    $flash = 'Listing published. Share it with classmates!';
}

// Approve/decline incoming swap/donate requests (owner only)
if (isset($_GET['approve_tx'])) {
    $txId = (int) $_GET['approve_tx'];
    $stmt = $db->prepare('SELECT t.*, i.user_id AS owner FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.id = ? LIMIT 1');
    $stmt->bind_param('i', $txId);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($tx && $tx['owner'] === $user['id'] && $tx['status'] === 'pending') {
        $impact = ['co2' => (float) $tx['impact_co2'], 'waste' => (float) $tx['impact_waste']];
        $db->query('UPDATE transactions SET status = "completed" WHERE id = ' . (int) $txId);
        $db->query('UPDATE items SET status = "completed" WHERE id = ' . (int) $tx['item_id']);
        update_impact((int) $tx['owner_id'], $impact['co2'], $impact['waste']);
        update_impact((int) $tx['actor_id'], $impact['co2'], $impact['waste']);
        $flash = 'Exchange approved and logged.';
    }
}

if (isset($_GET['decline_tx'])) {
    $txId = (int) $_GET['decline_tx'];
    $stmt = $db->prepare('SELECT t.*, i.user_id AS owner FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.id = ? LIMIT 1');
    $stmt->bind_param('i', $txId);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($tx && $tx['owner'] === $user['id'] && $tx['status'] === 'pending') {
        $db->query('UPDATE items SET status = "available" WHERE id = ' . (int) $tx['item_id']);
        $db->query('DELETE FROM transactions WHERE id = ' . (int) $txId);
        $flash = 'Exchange request declined.';
    }
}

if (isset($_GET['take_item'])) {
    $itemId = (int) $_GET['take_item'];
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($item) {
        if ($item['user_id'] === $user['id']) {
            $flash = 'You already own this listing.';
        } elseif ($item['status'] !== 'available') {
            $flash = 'This listing is no longer available.';
        } else {
            // Avoid multiple pending on same item
            $check = $db->prepare('SELECT id FROM transactions WHERE item_id = ? AND status = "pending" LIMIT 1');
            $check->bind_param('i', $itemId);
            $check->execute();
            $existingPending = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existingPending) {
                $flash = 'This listing already has a pending request.';
            } else {
                $impact = estimate_impact($item['category'] ?? '', $item['listing_type'], $item['item_condition'] ?? '');
                $status = $item['listing_type'] === 'sell' ? 'completed' : 'pending';

                $insert = $db->prepare('INSERT INTO transactions (item_id, owner_id, actor_id, action, impact_co2, impact_waste, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('iiisdds', $itemId, $item['user_id'], $user['id'], $item['listing_type'], $impact['co2'], $impact['waste'], $status);
                $insert->execute();
                $insert->close();

                if ($status === 'completed') {
                    $update = $db->prepare('UPDATE items SET status = "completed" WHERE id = ?');
                    $update->bind_param('i', $itemId);
                    $update->execute();
                    $update->close();
                    update_impact($item['user_id'], $impact['co2'], $impact['waste']);
                    update_impact($user['id'], $impact['co2'], $impact['waste']);
                    $flash = 'Sale completed. Impact logged!';
                } else {
                    $update = $db->prepare('UPDATE items SET status = "reserved" WHERE id = ?');
                    $update->bind_param('i', $itemId);
                    $update->execute();
                    $update->close();
                    $flash = 'Request sent. Waiting for owner to approve.';
                }
            }
        }
    } else {
        $flash = 'Listing not found.';
    }
}

$available = [];
$stmt = $db->prepare('SELECT items.*, users.name AS owner FROM items JOIN users ON users.id = items.user_id WHERE status IN ("available","reserved") ORDER BY (status="available") DESC, created_at DESC LIMIT 12');
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $available[] = $row;
}
$stmt->close();

$myItems = [];
$stmt = $db->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $myItems[] = $row;
}
$stmt->close();

$myTx = [];
$stmt = $db->prepare('SELECT t.*, i.title FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.owner_id = ? OR t.actor_id = ? ORDER BY t.created_at DESC LIMIT 6');
$stmt->bind_param('ii', $user['id'], $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $myTx[] = $row;
}
$stmt->close();

$pendingIncoming = [];
$stmt = $db->prepare('SELECT t.*, i.title FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.status = "pending" AND t.owner_id = ? ORDER BY t.created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pendingIncoming[] = $row;
}
$stmt->close();

render_header('Dashboard', $user, 'dashboard');
?>
<section class="hero">
    <div>
        <div class="pill">Marketplace</div>
        <h1 class="headline">Hi <?php echo sanitize($user['name']); ?>, your circularity cockpit.</h1>
        <p class="lede">Track personal CO₂ and waste avoided, list items, and close exchanges with a click.</p>
        <div class="flex" style="margin-top:14px;">
            <a class="btn primary" href="post-item.php">+ List an item</a>
            <a class="btn ghost" href="profile.php">View profile</a>
        </div>
        <?php if ($flash): ?>
            <div class="banner" style="margin-top:12px;"><?php echo sanitize($flash); ?></div>
        <?php endif; ?>
    </div>
    <div class="grid">
        <div class="card stat">
            <div class="value"><?php echo number_format((float) $user['impact_co2'], 1); ?> kg</div>
            <div class="label">CO₂ avoided</div>
        </div>
        <div class="card stat">
            <div class="value"><?php echo number_format((float) $user['impact_waste'], 2); ?> kg</div>
            <div class="label">Waste diverted</div>
        </div>
        <div class="card stat">
            <div class="value"><?php echo count($myItems); ?></div>
            <div class="label">Your listings</div>
        </div>
    </div>
</section>

<section>
    <div class="section-title">Available now <span>claim to complete an exchange</span></div>
    <div class="grid">
        <?php foreach ($available as $item): ?>
            <div class="card item-card">
                <div class="meta">
                    <span class="badge"><?php echo sanitize($item['category'] ?: 'General'); ?></span>
                    <span class="status <?php echo sanitize($item['status']); ?>"><?php echo sanitize($item['status']); ?></span>
                </div>
                <h3><?php echo sanitize($item['title']); ?></h3>
                <p><?php echo sanitize($item['description']); ?></p>
                <div class="item-footer">
                    <div>
                        <div class="tagline">Owner: <?php echo sanitize($item['owner']); ?></div>
                        <div class="price"><?php echo $item['listing_type'] === 'sell' ? '$' . number_format($item['price'], 2) : ucfirst($item['listing_type']); ?></div>
                    </div>
                    <div class="flex" style="gap:8px;">
                        <a class="btn ghost" href="item.php?id=<?php echo (int) $item['id']; ?>">View</a>
                        <?php if ($item['user_id'] !== $user['id'] && $item['status'] === 'available'): ?>
                            <a class="btn primary" href="dashboard.php?take_item=<?php echo (int) $item['id']; ?>">Complete exchange</a>
                        <?php elseif ($item['user_id'] === $user['id']): ?>
                            <span class="badge">Yours</span>
                        <?php else: ?>
                            <span class="badge">Reserved</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$available): ?>
            <div class="card">No listings yet. Post your first item.</div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-title">Your listings <span>status and type</span></div>
    <div class="grid">
        <?php foreach ($myItems as $item): ?>
            <div class="card item-card">
                <div class="meta">
                    <span class="badge"><?php echo sanitize($item['listing_type']); ?></span>
                    <span class="status <?php echo sanitize($item['status']); ?>"><?php echo sanitize($item['status']); ?></span>
                </div>
                <h3><?php echo sanitize($item['title']); ?></h3>
                <p><?php echo sanitize($item['description']); ?></p>
                <div class="tagline"><?php echo sanitize($item['item_condition']); ?> • <?php echo sanitize($item['category']); ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$myItems): ?>
            <div class="card">You have no listings yet. Click “List an item” to start.</div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-title">Incoming requests <span>approve or decline</span></div>
    <div class="grid">
        <?php foreach ($pendingIncoming as $req): ?>
            <div class="card item-card">
                <div class="meta">
                    <span class="badge"><?php echo sanitize($req['action']); ?></span>
                    <span class="status reserved">pending</span>
                </div>
                <h3><?php echo sanitize($req['title']); ?></h3>
                <p class="tagline">Requested by user #<?php echo (int) $req['actor_id']; ?></p>
                <div class="item-footer">
                    <span class="impact-pill"><?php echo number_format($req['impact_co2'], 1); ?> kg CO₂ • <?php echo number_format($req['impact_waste'], 2); ?> kg</span>
                    <div class="flex" style="gap:6px;">
                        <a class="btn primary" href="dashboard.php?approve_tx=<?php echo (int) $req['id']; ?>">Approve</a>
                        <a class="btn ghost" href="dashboard.php?decline_tx=<?php echo (int) $req['id']; ?>">Decline</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$pendingIncoming): ?>
            <div class="card">No pending requests.</div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-title">Recent exchanges <span>impact log</span></div>
    <div class="panel">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Impact</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($myTx as $tx): ?>
                    <tr>
                        <td><?php echo sanitize($tx['title']); ?></td>
                        <td><?php echo sanitize($tx['action']); ?></td>
                        <td><span class="impact-pill"><?php echo number_format($tx['impact_co2'], 1); ?> kg CO₂ • <?php echo number_format($tx['impact_waste'], 2); ?> kg waste</span></td>
                        <td class="muted"><?php echo sanitize($tx['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$myTx): ?>
                    <tr><td colspan="4">No exchanges yet. Claim an item to log impact.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
