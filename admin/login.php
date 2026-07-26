<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Admin Login';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Admin Login</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Email/Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-block">Log In</button>
        </form>
        <p class="center" style="margin-top:16px; font-size:14px;">No account yet? <a href="register.php">Register</a></p>
        <p class="center muted" style="font-size:12px;">Demo: admin / admin123</p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
