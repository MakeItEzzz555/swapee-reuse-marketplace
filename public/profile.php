<?php
require_once __DIR__ . '/../includes/header.php';
require_login();

$user = current_user();
$db = get_db();

$flash = '';
if (isset($_SESSION['flash_success'])) {
    $flash = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$stmt = $db->prepare('SELECT COUNT(*) AS total FROM items WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tx = [];
$stmt = $db->prepare('SELECT t.*, i.title FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.owner_id = ? OR t.actor_id = ? ORDER BY t.created_at DESC');
$stmt->bind_param('ii', $user['id'], $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tx[] = $row;
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

$pendingIncoming = [];
$stmt = $db->prepare('SELECT t.*, i.title FROM transactions t JOIN items i ON i.id = t.item_id WHERE t.status = "pending" AND t.owner_id = ? ORDER BY t.created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pendingIncoming[] = $row;
}
$stmt->close();

render_header('Profile', $user, 'profile');
?>
<section class="hero">
    <div>
        <div class="pill">Your impact</div>
        <h1 class="headline">Profile &amp; impact ledger</h1>
        <p class="lede">Summaries of your listings, exchanges, and environmental wins.</p>
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
            <div class="value"><?php echo (int) ($counts['total'] ?? 0); ?></div>
            <div class="label">Total listings</div>
        </div>
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
                <div class="item-footer" style="margin-top:10px;">
                    <span class="price"><?php echo $item['listing_type'] === 'sell' ? '$' . number_format($item['price'], 2) : ucfirst($item['listing_type']); ?></span>
                    <a class="btn ghost" href="edit-item.php?id=<?php echo (int) $item['id']; ?>">Edit</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$myItems): ?>
            <div class="card">You have no listings yet. Click “List an item” to start.</div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-title">Activity feed <span>exchanges you participated in</span></div>
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
                <?php foreach ($tx as $row): ?>
                    <tr>
                        <td><?php echo sanitize($row['title']); ?></td>
                        <td><?php echo sanitize($row['action']); ?></td>
                        <td><span class="impact-pill"><?php echo number_format($row['impact_co2'], 1); ?> kg CO₂ • <?php echo number_format($row['impact_waste'], 2); ?> kg</span></td>
                        <td class="muted"><?php echo sanitize($row['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tx): ?>
                    <tr><td colspan="4">No exchanges yet. Claim or list an item to see impact.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
