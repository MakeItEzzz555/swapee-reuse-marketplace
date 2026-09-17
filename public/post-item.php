<?php
require_once __DIR__ . '/../includes/header.php';
require_login();

$user = current_user();
$db = get_db();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $condition = trim($_POST['item_condition'] ?? 'Good');
    $listingType = $_POST['listing_type'] ?? 'swap';
    $price = (float) ($_POST['price'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (!$title) {
        $flash = 'Title is required.';
    } else {
        if ($listingType === 'sell' && $price <= 0) {
            $flash = 'Please add a price for selling.';
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO items (user_id, title, description, category, item_condition, listing_type, price, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssssds', $user['id'], $title, $description, $category, $condition, $listingType, $price, $imageUrl);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = 'Listing published. Share it with classmates!';
                header('Location: dashboard.php');
                exit;
            } catch (Throwable $e) {
                $flash = 'Could not save listing: ' . sanitize($e->getMessage());
            }
        }
    }
}

render_header('Post item', $user, 'post');
?>
<section class="hero">
    <div>
        <div class="pill">List an item</div>
        <h1 class="headline">Share gear with the campus community.</h1>
        <p class="lede">Swap, donate, or sell — every exchange is tracked with impact metrics.</p>
        <?php if ($flash): ?>
            <div class="banner" style="margin-top:12px;"><?php echo sanitize($flash); ?></div>
        <?php endif; ?>
    </div>
    <div class="panel">
        <form method="POST">
            <label>Title</label>
            <input type="text" name="title" required placeholder="e.g., Data Structures Textbook">
            <label>Description</label>
            <textarea name="description" placeholder="Add details about condition, accessories, pickup location."></textarea>
            <label>Category</label>
            <select name="category">
                <option>Books</option>
                <option>Electronics</option>
                <option>Lab</option>
                <option>Furniture</option>
                <option>General</option>
            </select>
            <label>Condition</label>
            <select name="item_condition">
                <option>Like new</option>
                <option>Good</option>
                <option>Fair</option>
            </select>
            <label>Listing type</label>
            <select name="listing_type">
                <option value="swap">Swap</option>
                <option value="donate">Donate</option>
                <option value="sell">Sell</option>
            </select>
            <label>Price (if selling)</label>
            <input type="number" name="price" step="0.01" min="0" placeholder="0.00">
            <label>Image URL (optional)</label>
            <input type="text" name="image_url" placeholder="https://example.com/item.jpg">
            <button class="btn primary" type="submit">Publish listing</button>
        </form>
    </div>
</section>
<?php render_footer(); ?>
