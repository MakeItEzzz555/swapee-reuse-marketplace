<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$user = current_user();
$db = get_db();
$flash = '';

if (isset($_GET['delete_item'])) {
    $itemId = (int) $_GET['delete_item'];
    $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $stmt->close();
    $flash = 'Listing removed.';
}

$items = [];
$res = $db->query('SELECT items.*, users.name AS owner FROM items JOIN users ON users.id = items.user_id ORDER BY items.created_at DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

$users = [];
$res = $db->query('SELECT id, name, email, role, impact_co2, impact_waste, created_at FROM users ORDER BY created_at DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}

render_header('Admin', $user, 'admin');
?>
<section class="hero">
    <div>
        <div class="pill">Admin</div>
        <h1 class="headline">Moderate listings &amp; watch platform health.</h1>
        <p class="lede">Remove problematic posts, view user impact, and keep the prototype clean.</p>
        <?php if ($flash): ?>
            <div class="banner" style="margin-top:12px;"><?php echo sanitize($flash); ?></div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-title">Listings</div>
    <div class="panel">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo sanitize($item['title']); ?></td>
                        <td><?php echo sanitize($item['owner']); ?></td>
                        <td><?php echo sanitize($item['status']); ?></td>
                        <td><?php echo sanitize($item['listing_type']); ?></td>
                        <td><a class="btn ghost" href="admin.php?delete_item=<?php echo (int) $item['id']; ?>">Remove</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                    <tr><td colspan="5">No listings yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section>
    <div class="section-title">Users</div>
    <div class="panel">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Impact</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?php echo sanitize($row['name']); ?></td>
                        <td><?php echo sanitize($row['email']); ?></td>
                        <td><?php echo sanitize($row['role']); ?></td>
                        <td><?php echo number_format($row['impact_co2'], 1); ?> kg CO₂ • <?php echo number_format($row['impact_waste'], 2); ?> kg</td>
                        <td class="muted"><?php echo sanitize($row['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr><td colspan="5">No users yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
