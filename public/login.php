<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $user = login($email, $password);
        if ($user) {
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'Invalid credentials. Try again.';
}

$user = current_user();
render_header('Login', $user, 'auth');
?>
<section class="hero">
    <div>
        <div class="pill">Welcome back</div>
        <h1 class="headline">Sign in and start swapping.</h1>
        <p class="lede">Access your listings, impact dashboard, and admin controls.</p>
        <div class="panel" style="margin-top:16px;">
            <?php if ($error): ?>
                <div class="banner" style="margin-bottom:10px;"><?php echo sanitize($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@campus.edu">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
                <button class="btn primary" type="submit">Login</button>
            </form>
            <p class="small" style="margin-top:10px;">No account yet? <a href="register.php">Create one</a>.</p>
        </div>
    </div>
    <div class="card">
        <div class="stat">
            <div class="value">Secure by default</div>
            <div class="label">Hashed passwords, session-based auth, admin gating.</div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
