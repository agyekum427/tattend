<?php
/**
 * Expects (optionally) before include:
 *   $pageTitle   string
 *   $navContext  'lecturer' | 'admin' | null
 *   $assetPath   relative path prefix to project root, e.g. '' or '../'
 */
$pageTitle  = $pageTitle ?? 'T Attend';
$navContext = $navContext ?? null;
$assetPath  = $assetPath ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · T Attend</title>
<link rel="stylesheet" href="<?= $assetPath ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="brand"><a href="<?= $assetPath ?>index.php" style="color:inherit; display:flex; align-items:center; gap:8px;">🟢 T Attend</a></div>
    <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
    <div class="nav-links" id="navLinks">
        <?php if ($navContext === 'lecturer'): ?>
            <span class="badge-role">Lecturer</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="students.php">Students</a>
            <a href="sessions.php">Sessions</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Log out</a>
        <?php elseif ($navContext === 'admin'): ?>
            <span class="badge-role">Admin</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="lecturers.php">Lecturers</a>
            <a href="logout.php">Log out</a>
        <?php else: ?>
            <a href="<?= $assetPath ?>index.php">Home</a>
        <?php endif; ?>
    </div>
</nav>
<script>
(function () {
    var toggle = document.getElementById('navToggle');
    var links = document.getElementById('navLinks');
    if (!toggle || !links) return;
    toggle.addEventListener('click', function () {
        var open = links.classList.toggle('open');
        toggle.classList.toggle('active', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            links.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
})();
</script>

