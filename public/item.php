<?php
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$user = current_user();

function estimate_impact_item(string $category, string $type, string $condition): array
{
    $category = strtolower($category);
    $bases = [
        'books' => ['co2' => 5.0, 'waste' => 0.5],
        'electronics' => ['co2' => 25.0, 'waste' => 3.0],
        'lab' => ['co2' => 18.0, 'waste' => 2.2],
    ];
    $base = isset($bases[$category]) ? $bases[$category] : ['co2' => 10.0, 'waste' => 1.0];

    $cond = strtolower($condition);
    $conditionFactor = $cond === 'like new' ? 1.2 : ($cond === 'fair' ? 0.8 : 1.0);
    $typeFactor = $type === 'donate' ? 1.15 : ($type === 'sell' ? 0.95 : 1.0);

    return [
        'co2' => round($base['co2'] * $conditionFactor * $typeFactor, 1),
        'waste' => round($base['waste'] * $conditionFactor * $typeFactor, 2),
    ];
}

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $db->prepare('SELECT i.*, u.name AS owner_name, u.email AS owner_email FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ? LIMIT 1');
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    render_header('Item not found', $user, 'item');
    echo '<section class="hero"><div class="card">Item not found.</div></section>';
    render_footer();
    exit;
}

$impact = estimate_impact_item($item['category'] ?? '', $item['listing_type'], $item['item_condition'] ?? '');
$isOwner = $user && $user['id'] === (int) $item['user_id'];
$canClaim = !$isOwner && $item['status'] === 'available';
$ctaHref = $user ? 'dashboard.php?take_item=' . (int) $item['id'] : 'login.php';

render_header($item['title'], $user, 'item');
?>
<section class="hero">
    <div class="card" style="background: rgba(255,255,255,0.05);">
        <div class="meta" style="margin-bottom:12px;">
            <span class="badge"><?php echo sanitize($item['listing_type']); ?></span>
            <span class="status <?php echo sanitize($item['status']); ?>"><?php echo sanitize($item['status']); ?></span>
        </div>
        <h1 class="headline" style="margin:0;"><?php echo sanitize($item['title']); ?></h1>
        <p class="lede"><?php echo sanitize($item['description']); ?></p>
        <div class="grid" style="margin-top:12px;">
            <div class="card stat">
                <div class="value"><?php echo sanitize($item['category']); ?></div>
                <div class="label">Category</div>
            </div>
            <div class="card stat">
                <div class="value"><?php echo sanitize($item['item_condition']); ?></div>
                <div class="label">Condition</div>
            </div>
            <div class="card stat">
                <div class="value">
                    <?php echo $item['listing_type'] === 'sell'
                        ? '$' . number_format((float) $item['price'], 2)
                        : ucfirst($item['listing_type']); ?>
                </div>
                <div class="label">Type</div>
            </div>
            <div class="card stat">
                <div class="value"><?php echo number_format($impact['co2'], 1); ?> kg</div>
                <div class="label">Est. CO₂ saved</div>
            </div>
        </div>
        <div class="flex" style="margin-top:14px; gap:10px;">
            <?php if ($isOwner): ?>
                <span class="badge">Your listing</span>
            <?php elseif ($item['status'] === 'available'): ?>
                <a class="btn primary" href="<?php echo $ctaHref; ?>">Request exchange</a>
            <?php elseif ($item['status'] === 'reserved'): ?>
                <span class="badge">Reserved</span>
            <?php else: ?>
                <span class="badge">Completed</span>
            <?php endif; ?>
            <span class="muted">Owner: <?php echo sanitize($item['owner_name']); ?> • <?php echo sanitize($item['owner_email']); ?></span>
        </div>
    </div>
    <div class="card" style="padding:0; overflow:hidden;">
        <?php if (!empty($item['image_url'])): ?>
            <img src="<?php echo sanitize($item['image_url']); ?>" alt="Item image" style="width:100%; height:100%; max-height:420px; object-fit:cover; display:block;">
        <?php else: ?>
            <div style="width:100%; height:420px; display:grid; place-items:center; background:linear-gradient(135deg, rgba(168,85,247,0.2), rgba(34,211,238,0.2)); color:#fff;">
                No image provided
            </div>
        <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
