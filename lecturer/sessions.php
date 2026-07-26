<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();

// Auto-close expired sessions
$pdo->prepare("UPDATE attendance_sessions SET status='closed' WHERE lecturer_id = ? AND status='open' AND closes_at < NOW()")
    ->execute([$lecturerId]);

$sessions = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code,
        (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id = s.id) AS checkins
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.lecturer_id = ?
    ORDER BY s.created_at DESC");
$sessions->execute([$lecturerId]);
$sessions = $sessions->fetchAll();

$pageTitle = 'Sessions';
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="flex-between">
        <h2>All Sessions</h2>
        <a href="create_session.php" class="btn">+ New Session</a>
    </div>
    <div class="card">
        <?php if (empty($sessions)): ?>
            <p class="muted">No sessions created yet.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Title</th><th>Course</th><th>Opened</th><th>Closes</th><th>Status</th><th>Check-ins</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td><?= e($s['title']) ?></td>
                        <td><?= e($s['course_code']) ?></td>
                        <td><?= date('M j, g:i A', strtotime($s['opens_at'])) ?></td>
                        <td><?= date('M j, g:i A', strtotime($s['closes_at'])) ?></td>
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
