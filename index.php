<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Welcome';
$assetPath = '';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="hero">
        <h1>Attendance in seconds, not minutes.</h1>
        <p>T Attend lets a lecturer generate a time-limited attendance session and share it as a link or QR code, so students can check in instantly by entering their index number.</p>
    </div>

    <div class="role-cards">
        <div class="role-card">
            <div class="icon">🎓</div>
            <h3>Lecturer</h3>
            <p class="muted">Manage your class list, start attendance sessions, and view reports.</p>
            <a class="btn btn-block" href="lecturer/login.php">Lecturer Login</a>
            <p class="mt-0" style="margin-top:10px; font-size:13px;"><a href="lecturer/register.php">Create an account</a></p>
        </div>
        <div class="role-card">
            <div class="icon">🙋</div>
            <h3>Student</h3>
            <p class="muted">Enter a session code or scan the QR code shared by your lecturer.</p>
            <a class="btn btn-block" href="student/index.php">Check In</a>
        </div>
        <div class="role-card">
            <div class="icon">🛠️</div>
            <h3>Admin</h3>
            <p class="muted">Manage lecturer accounts on the platform.</p>
            <a class="btn btn-block" href="admin/login.php">Admin Login</a>
            <p class="mt-0" style="margin-top:10px; font-size:13px;"><a href="lecturer/register.php">Create an account</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
