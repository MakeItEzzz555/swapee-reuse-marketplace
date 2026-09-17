<?php
require_once __DIR__ . '/../includes/header.php';
require_login();

$user = current_user();
$db = get_db();

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $db->prepare('SELECT * FROM items WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $itemId, $user['id']);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    render_header('Edit listing', $user, 'edit-item');
    echo '<section class="hero"><div class="card">Listing not found or not yours.</div></section>';
    render_footer();
    exit;
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $condition = trim($_POST['item_condition'] ?? 'Good');
    $listingType = $_POST['listing_type'] ?? 'swap';
    $price = (float) ($_POST['price'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $status = $_POST['status'] ?? $item['status'];

    if (!$title) {
        $flash = 'Title is required.';
    } elseif ($listingType === 'sell' && $price <= 0) {
        $flash = 'Please add a price for selling.';
    } else {
        $stmt = $db->prepare('UPDATE items SET title = ?, description = ?, category = ?, item_condition = ?, listing_type = ?, price = ?, image_url = ?, status = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sssssdssii', $title, $description, $category, $condition, $listingType, $price, $imageUrl, $status, $itemId, $user['id']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Listing updated.';
        header('Location: profile.php');
        exit;
    }
}

render_header('Edit listing', $user, 'edit-item');
?>
<section class="hero">
    <div>
        <div class="pill">Edit your listing</div>
        <h1 class="headline">Update details for <?php echo sanitize($item['title']); ?></h1>
        <p class="lede">Adjust category, status, price, or switch to donate/sell as needed.</p>
        <?php if ($flash): ?>
            <div class="banner" style="margin-top:12px;"><?php echo sanitize($flash); ?></div>
        <?php endif; ?>
    </div>
    <div class="panel">
        <form method="POST">
            <label>Title</label>
            <input type="text" name="title" required value="<?php echo sanitize($item['title']); ?>">
            <label>Description</label>
            <textarea name="description" placeholder="Add details about condition, accessories, pickup location."><?php echo sanitize($item['description']); ?></textarea>
            <label>Category</label>
            <select name="category">
                <?php
                $categories = ['Books','Electronics','Lab','Furniture','General'];
                foreach ($categories as $cat) {
                    $sel = $item['category'] === $cat ? 'selected' : '';
                    echo "<option $sel>$cat</option>";
                }
                ?>
            </select>
            <label>Condition</label>
            <select name="item_condition">
                <?php
                $conds = ['Like new','Good','Fair'];
                foreach ($conds as $c) {
                    $sel = $item['item_condition'] === $c ? 'selected' : '';
                    echo "<option $sel>$c</option>";
                }
                ?>
            </select>
            <label>Listing type</label>
            <select name="listing_type">
                <?php
                $types = ['swap' => 'Swap','donate' => 'Donate','sell' => 'Sell'];
                foreach ($types as $val => $label) {
                    $sel = $item['listing_type'] === $val ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$label</option>";
                }
                ?>
            </select>
            <label>Price (if selling)</label>
            <input type="number" name="price" step="0.01" min="0" value="<?php echo sanitize((string)$item['price']); ?>">
            <label>Image URL (optional)</label>
            <input type="text" name="image_url" value="<?php echo sanitize($item['image_url']); ?>">
            <label>Status</label>
            <select name="status">
                <?php
                $statuses = ['available','reserved','completed'];
                foreach ($statuses as $s) {
                    $sel = $item['status'] === $s ? 'selected' : '';
                    echo "<option value=\"$s\" $sel>$s</option>";
                }
                ?>
            </select>
            <button class="btn primary" type="submit">Save changes</button>
            <a class="btn ghost" href="profile.php">Cancel</a>
        </form>
    </div>
</section>
<?php render_footer(); ?>
