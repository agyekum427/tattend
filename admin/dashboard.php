<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminId = current_admin_id();

$lecturerCount = $pdo->prepare("SELECT COUNT(*) FROM lecturers WHERE admin_id = ?");
$lecturerCount->execute([$adminId]);
$lecturerCount = $lecturerCount->fetchColumn();

$studentCount = $pdo->prepare("
    SELECT COUNT(*) FROM students s
    JOIN courses c ON s.course_id = c.id
    JOIN lecturers l ON c.lecturer_id = l.id
    WHERE l.admin_id = ?");
$studentCount->execute([$adminId]);
$studentCount = $studentCount->fetchColumn();

$sessionCount = $pdo->prepare("
    SELECT COUNT(*) FROM attendance_sessions s
    JOIN lecturers l ON s.lecturer_id = l.id
    WHERE l.admin_id = ?");
$sessionCount->execute([$adminId]);
$sessionCount = $sessionCount->fetchColumn();

$recordCount = $pdo->prepare("
    SELECT COUNT(*) FROM attendance_records r
    JOIN attendance_sessions s ON r.session_id = s.id
    JOIN lecturers l ON s.lecturer_id = l.id
    WHERE l.admin_id = ?");
$recordCount->execute([$adminId]);
$recordCount = $recordCount->fetchColumn();

$recentLecturers = $pdo->prepare("SELECT * FROM lecturers WHERE admin_id = ? ORDER BY created_at DESC LIMIT 5");
$recentLecturers->execute([$adminId]);
$recentLecturers = $recentLecturers->fetchAll();

$pageTitle = 'Admin Dashboard';
$navContext = 'admin';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h2>My Workspace Overview</h2>
    <div class="grid grid-3" style="margin-bottom:10px;">
        <div class="stat-card"><div class="stat-number"><?= (int)$lecturerCount ?></div><div class="stat-label">Lecturers</div></div>
        <div class="stat-card"><div class="stat-number"><?= (int)$studentCount ?></div><div class="stat-label">Students</div></div>
        <div class="stat-card"><div class="stat-number"><?= (int)$sessionCount ?></div><div class="stat-label">Sessions Created</div></div>
    </div>
    <div class="grid grid-3" style="margin-bottom:28px;">
        <div class="stat-card"><div class="stat-number"><?= (int)$recordCount ?></div><div class="stat-label">Total Check-ins</div></div>
    </div>

    <div class="card">
        <div class="flex-between">
            <h3 class="card-title mt-0">Recently Registered Lecturers</h3>
            <a href="lecturers.php" style="font-size:13px;">Manage all &rarr;</a>
        </div>
        <?php if (empty($recentLecturers)): ?>
            <p class="muted">No lecturers registered yet.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($recentLecturers as $l): ?>
                    <tr>
                        <td><?= e($l['name']) ?></td>
                        <td><?= e($l['email']) ?></td>
                        <td><span class="pill <?= $l['status'] === 'active' ? 'pill-open' : 'pill-closed' ?>"><?= ucfirst($l['status']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
