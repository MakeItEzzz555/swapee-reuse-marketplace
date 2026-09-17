<?php
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$counts = ['items' => 0, 'transactions' => 0, 'co2' => 0, 'waste' => 0];

$result = $db->query('SELECT COUNT(*) AS total FROM items');
if ($result) {
    $counts['items'] = (int) $result->fetch_assoc()['total'];
}

$result = $db->query('SELECT COUNT(*) AS total, COALESCE(SUM(impact_co2),0) AS co2, COALESCE(SUM(impact_waste),0) AS waste FROM transactions');
if ($result) {
    $row = $result->fetch_assoc();
    $counts['transactions'] = (int) $row['total'];
    $counts['co2'] = (float) $row['co2'];
    $counts['waste'] = (float) $row['waste'];
}

$items = [];
$stmt = $db->prepare('SELECT items.*, users.name AS owner FROM items JOIN users ON users.id = items.user_id WHERE status IN ("available","reserved") ORDER BY (status="available") DESC, items.created_at DESC LIMIT 6');
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$user = current_user();
render_header('Swap & Save', $user, 'landing');
?>
<section class="hero">
    <div>
        <div class="pill">TRL4 Prototype • Campus Circularity</div>
        <h1 class="headline">Swap &amp; Save – a smoother, greener marketplace for students.</h1>
        <p class="lede">List textbooks, gadgets, and lab gear. Close swaps, donations, or sales with live impact scoring showing CO₂ and waste avoided per exchange.</p>
        <div class="flex" style="margin-top:18px; gap:12px;">
            <a class="btn primary" href="<?php echo $user ? 'dashboard.php' : 'register.php'; ?>">Try the prototype</a>
            <a class="btn ghost" href="<?php echo $user ? 'post-item.php' : 'login.php'; ?>">List an item</a>
        </div>
        <div class="banner" style="margin-top:14px;">
            <strong>Live snapshot:</strong>
            <span class="impact-pill">Items live: <span data-count-to="<?php echo $counts['items']; ?>"><?php echo $counts['items']; ?></span></span>
            <span class="impact-pill">Exchanges: <span data-count-to="<?php echo $counts['transactions']; ?>"><?php echo $counts['transactions']; ?></span></span>
            <span class="impact-pill">CO₂ saved: <?php echo number_format($counts['co2'], 1); ?> kg</span>
        </div>
    </div>
    <div class="grid">
        <div class="card headered">
            <div class="sparkle">♻</div>
            <div class="stat">
                <div class="value" data-count-to="<?php echo max(50, $counts['items'] * 3); ?>"><?php echo max(50, $counts['items'] * 3); ?></div>
                <div class="label">Estimated kg CO₂ avoided by circular swaps.</div>
            </div>
        </div>
        <div class="card headered">
            <div class="sparkle">⚡</div>
            <div class="stat">
                <div class="value">3 steps</div>
                <div class="label">List. Claim. Close the exchange with impact scoring.</div>
            </div>
        </div>
        <div class="card headered">
            <div class="sparkle">🛠</div>
            <div class="stat">
                <div class="value">TRL4 Ready</div>
                <div class="label">Clickable prototype + working PHP/MySQL baseline.</div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="section-title">How it works <span>student-friendly flow</span></div>
    <div class="stepper panel">
        <div class="step">
            <div class="dot">1</div>
            <div>
                <strong>List quickly.</strong>
                <div class="muted">Upload a title, condition, type (swap/donate/sell) and an optional image URL.</div>
            </div>
        </div>
        <div class="step">
            <div class="dot">2</div>
            <div>
                <strong>Claim &amp; chat in class.</strong>
                <div class="muted">Students browse a mobile-first grid, claim an item, and we update item status to prevent double-booking.</div>
            </div>
        </div>
        <div class="step">
            <div class="dot">3</div>
            <div>
                <strong>Close with impact.</strong>
                <div class="muted">On completion we generate CO₂ + waste avoided estimates and add them to both users’ profiles.</div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="section-title">Fresh on the marketplace <span>campus-ready</span></div>
    <div class="grid">
        <?php foreach ($items as $item): ?>
            <div class="card item-card">
                <div class="meta">
                    <span class="badge"><?php echo sanitize($item['category'] ?: 'General'); ?></span>
                    <span class="status <?php echo sanitize($item['status']); ?>"><?php echo sanitize($item['status']); ?></span>
                </div>
                <h3><?php echo sanitize($item['title']); ?></h3>
                <p><?php echo sanitize($item['description']); ?></p>
                <div class="item-footer">
                    <div>
                        <div class="tagline">By <?php echo sanitize($item['owner']); ?></div>
                        <div class="price"><?php echo $item['listing_type'] === 'sell' ? '$' . number_format($item['price'], 2) : ucfirst($item['listing_type']); ?></div>
                    </div>
                    <div class="flex" style="gap:8px;">
                        <a class="btn ghost" href="item.php?id=<?php echo (int) $item['id']; ?>">View</a>
                        <?php if ($item['status'] === 'available'): ?>
                            <a class="btn primary" href="<?php echo $user ? 'dashboard.php?take_item=' . (int) $item['id'] : 'login.php'; ?>">Claim</a>
                        <?php else: ?>
                            <span class="badge">Reserved</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <div class="card">No listings yet. Be the first to post an item.</div>
        <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
