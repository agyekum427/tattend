<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // admins table stores the login identifier in `username`, not `email`
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Every admin created here is its own independent tenant: they will
            // only ever see the lecturers (and everything under them) that they
            // personally create — never another admin's data.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, username, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $username, $hash]);
            $success = 'Account created successfully. You can now log in.';
        }
    }
}

$pageTitle = 'Admin Registration';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Create Admin Account</h2>
        <p class="muted center" style="font-size:13px; margin-top:-8px;">You'll get your own workspace — lecturers and data you add won't be visible to other admins.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?> <a href="login.php">Log in &rarr;</a></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email/Username</label>
                <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-block">Register</button>
        </form>
        <p class="center mt-0" style="margin-top:16px; font-size:14px;">Already have an account? <a href="login.php">Log in</a></p>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
