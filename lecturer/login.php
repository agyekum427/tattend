<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_lecturer_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE email = ?");
    $stmt->execute([$email]);
    $lecturer = $stmt->fetch();

    if ($lecturer && password_verify($password, $lecturer['password'])) {
        if ($lecturer['status'] === 'disabled') {
            $error = 'This account has been disabled by an administrator.';
        } else {
            $_SESSION['lecturer_id'] = $lecturer['id'];
            $_SESSION['lecturer_name'] = $lecturer['name'];
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Lecturer Login';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Lecturer Login</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-block">Log In</button>
        </form>
        <p class="center" style="margin-top:16px; font-size:14px;">No account yet? <a href="register.php">Register</a></p>
        <p class="center muted" style="font-size:12px;">Demo: lecturer@tattend.com / lecturer123</p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
