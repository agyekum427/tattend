<?php
require_once __DIR__ . '/../includes/auth.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['session_code'] ?? ''));
    if ($code === '') {
        $error = 'Please enter a session code.';
    } else {
        header('Location: checkin.php?code=' . urlencode($code));
        exit;
    }
}

$pageTitle = 'Student Check-In';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Enter Session Code</h2>
        <p class="muted center" style="font-size:14px;">Ask your lecturer for the code shown on screen, or scan the QR code they shared.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Session Code</label>
                <input type="text" name="session_code" placeholder="e.g. 8F3K2Q" style="text-transform:uppercase; text-align:center; font-size:20px; letter-spacing:3px;" required autofocus>
            </div>
            <button type="submit" class="btn btn-block">Continue</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
