<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $workspaceCode = trim($_POST['workspace_code'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $workspaceCode === '') {
        $error = 'Please fill in all fields, including your workspace code.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // The workspace code is the admin's username: it determines which
        // admin's tenant this lecturer belongs to. Every lecturer must be
        // placed under exactly one admin so that admin can manage them,
        // and so that other admins never see them.
        $adminStmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $adminStmt->execute([$workspaceCode]);
        $admin = $adminStmt->fetch();

        if (!$admin) {
            $error = 'That workspace code was not recognized. Please check with your administrator.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM lecturers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO lecturers (admin_id, name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$admin['id'], $name, $email, $hash]);
                $success = 'Account created successfully. You can now log in.';
            }
        }
    }
}

$pageTitle = 'Lecturer Registration';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Create Lecturer Account</h2>
        <p class="muted center" style="font-size:13px; margin-top:-8px;">Ask your administrator for your workspace code — it links your account to their institution.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?> <a href="login.php">Log in &rarr;</a></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Workspace Code</label>
                <input type="text" name="workspace_code" value="<?= e($_POST['workspace_code'] ?? '') ?>" placeholder="Provided by your administrator" required>
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

