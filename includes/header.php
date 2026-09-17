<?php
require_once __DIR__ . '/auth.php';

function render_header(string $title, ?array $user = null, string $pageKey = ''): void
{
    $assetPrefix = '../';
    $safeTitle = sanitize($title);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $safeTitle; ?> · Swapee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/style.css">
</head>
<body class="page-<?php echo sanitize($pageKey); ?>">
    <div class="bg-grid"></div>
    <div class="bg-gradient"></div>
    <header class="topbar">
        <div class="logo-mark">
            <span class="spark"></span>
            <span>Swapee</span>
        </div>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="dashboard.php">Marketplace</a>
            <a href="profile.php">Impact</a>
            <a href="post-item.php">List item</a>
            <div class="mobile-auth">
                <?php if ($user): ?>
                    <a class="btn ghost" href="profile.php">Profile</a>
                    <a class="btn ghost" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn ghost" href="login.php">Login</a>
                    <a class="btn primary" href="register.php">Get started</a>
                <?php endif; ?>
            </div>
        </nav>
        <?php if ($user): ?>
            <a class="user-chip nav-user-mobile" href="profile.php" title="View profile">
                <span class="badge"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                <span class="user-name"><?php echo sanitize($user['name']); ?></span>
            </a>
        <?php endif; ?>
        <div class="nav-actions">
            <?php if ($user): ?>
                <a class="user-chip" href="profile.php" title="View profile">
                    <span class="badge"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                    <span class="user-name"><?php echo sanitize($user['name']); ?></span>
                </a>
                <a class="btn ghost" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="btn ghost" href="login.php">Login</a>
                <a class="btn primary" href="register.php">Get started</a>
            <?php endif; ?>
        </div>
        <button class="btn ghost nav-toggle" aria-label="Toggle navigation">☰</button>
    </header>
    <main class="page">
    <?php
}

function render_footer(): void
{
    $assetPrefix = '../';
    ?>
    </main>
    <script src="<?php echo $assetPrefix; ?>assets/js/app.js"></script>
</body>
</html>
<?php
}
