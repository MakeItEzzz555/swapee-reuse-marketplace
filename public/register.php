<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($name && $email && $password) {
        try {
            $user = register_user($name, $email, $password, $bio);
            if ($user) {
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Email already registered or could not be saved.';
        } catch (Throwable $e) {
            $error = 'Could not register: ' . sanitize($e->getMessage());
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$user = current_user();
render_header('Register', $user, 'auth');
?>
<section class="hero">
    <div>
        <div class="pill">Create account</div>
        <h1 class="headline">Start swapping, donating, and tracking impact.</h1>
        <p class="lede">Students-first experience with responsive UI and instant impact scoring when exchanges close.</p>
        <div class="panel" style="margin-top:16px;">
            <?php if ($error): ?>
                <div class="banner" style="margin-bottom:10px;"><?php echo sanitize($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <label>Name</label>
                <input type="text" name="name" required placeholder="Amina Diallo">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@campus.edu">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
                <label>Bio (optional)</label>
                <textarea name="bio" placeholder="EE student, loves hardware."></textarea>
                <button class="btn primary" type="submit">Create account</button>
            </form>
            <p class="small" style="margin-top:10px;">Already registered? <a href="login.php">Login</a>.</p>
        </div>
    </div>
    <div class="card">
        <div class="stat">
            <div class="value">Prototype ready</div>
            <div class="label">Works on mobile/desktop, ready for live demo.</div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
