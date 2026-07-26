<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();

// Stats
$courseCount = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE lecturer_id = ?");
$courseCount->execute([$lecturerId]);
$courseCount = $courseCount->fetchColumn();

$studentCount = $pdo->prepare("
    SELECT COUNT(*) FROM students s
    JOIN courses c ON s.course_id = c.id
    WHERE c.lecturer_id = ?");
$studentCount->execute([$lecturerId]);
$studentCount = $studentCount->fetchColumn();

$sessionCount = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE lecturer_id = ?");
$sessionCount->execute([$lecturerId]);
$sessionCount = $sessionCount->fetchColumn();

$activeSessions = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code,
        (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id = s.id) AS checkins
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.lecturer_id = ? AND s.status = 'open' AND s.closes_at >= NOW()
    ORDER BY s.created_at DESC");
$activeSessions->execute([$lecturerId]);
$activeSessions = $activeSessions->fetchAll();

$recentSessions = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code,
        (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id = s.id) AS checkins
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.lecturer_id = ?
    ORDER BY s.created_at DESC LIMIT 5");
$recentSessions->execute([$lecturerId]);
$recentSessions = $recentSessions->fetchAll();

$pageTitle = 'Dashboard';
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="flex-between" style="margin-bottom:24px;">
        <div>
            <h2 class="mt-0 mb-0">Welcome back, <?= e($_SESSION['lecturer_name']) ?> 👋</h2>
            <p class="muted mt-0">Here's what's happening with your classes.</p>
        </div>
        <a href="create_session.php" class="btn">+ New Session</a>
    </div>

    <div class="grid grid-3" style="margin-bottom:28px;">
        <div class="stat-card"><div class="stat-number"><?= (int)$courseCount ?></div><div class="stat-label">Courses</div></div>
        <div class="stat-card"><div class="stat-number"><?= (int)$studentCount ?></div><div class="stat-label">Students</div></div>
        <div class="stat-card"><div class="stat-number"><?= (int)$sessionCount ?></div><div class="stat-label">Sessions Created</div></div>
    </div>

    <div class="card">
        <div class="flex-between">
            <h3 class="card-title mt-0">Active Sessions</h3>
            <a href="sessions.php" style="font-size:13px;">View all &rarr;</a>
        </div>
        <?php if (empty($activeSessions)): ?>
            <p class="muted">No sessions are currently open. <a href="create_session.php">Start one now</a>.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Title</th><th>Course</th><th>Closes</th><th>Check-ins</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($activeSessions as $s): ?>
                    <tr>
                        <td><?= e($s['title']) ?></td>
                        <td><?= e($s['course_code']) ?></td>
                        <td><?= date('M j, g:i A', strtotime($s['closes_at'])) ?></td>
                        <td><?= (int)$s['checkins'] ?></td>
                        <td><a href="session_view.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 class="card-title mt-0">Recent Sessions</h3>
        <?php if (empty($recentSessions)): ?>
            <p class="muted">No sessions yet.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Title</th><th>Course</th><th>Status</th><th>Check-ins</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentSessions as $s): ?>
                    <tr>
                        <td><?= e($s['title']) ?></td>
                        <td><?= e($s['course_code']) ?></td>
                        <td><span class="pill <?= $s['status'] === 'open' ? 'pill-open' : 'pill-closed' ?>"><?= ucfirst($s['status']) ?></span></td>
                        <td><?= (int)$s['checkins'] ?></td>
                        <td><a href="session_view.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
