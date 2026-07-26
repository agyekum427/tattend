<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();
$sessionId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code
    FROM attendance_sessions s JOIN courses c ON s.course_id = c.id
    WHERE s.id = ? AND s.lecturer_id = ?");
$stmt->execute([$sessionId, $lecturerId]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: sessions.php');
    exit;
}

// Auto-close if expired
if ($session['status'] === 'open' && strtotime($session['closes_at']) < time()) {
    $pdo->prepare("UPDATE attendance_sessions SET status='closed' WHERE id = ?")->execute([$sessionId]);
    $session['status'] = 'closed';
}

// Manual close
if (isset($_GET['close']) && $session['status'] === 'open') {
    $pdo->prepare("UPDATE attendance_sessions SET status='closed' WHERE id = ?")->execute([$sessionId]);
    header('Location: session_view.php?id=' . $sessionId);
    exit;
}

// CSV export
if (isset($_GET['export'])) {
    $records = $pdo->prepare("
        SELECT st.index_number, st.full_name, r.submitted_at
        FROM attendance_records r JOIN students st ON r.student_id = st.id
        WHERE r.session_id = ? ORDER BY r.submitted_at");
    $records->execute([$sessionId]);
    $records = $records->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $session['title']) . '_attendance.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Index Number', 'Full Name', 'Submitted At']);
    foreach ($records as $r) {
        fputcsv($out, [$r['index_number'], $r['full_name'], $r['submitted_at']]);
    }
    fclose($out);
    exit;
}

$records = $pdo->prepare("
    SELECT st.index_number, st.full_name, r.submitted_at
    FROM attendance_records r JOIN students st ON r.student_id = st.id
    WHERE r.session_id = ? ORDER BY r.submitted_at DESC");
$records->execute([$sessionId]);
$records = $records->fetchAll();

$totalStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_id = ?");
$totalStudents->execute([$session['course_id']]);
$totalStudents = $totalStudents->fetchColumn();

$url = checkin_url($session['session_code']);

$pageTitle = $session['title'];
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="flex-between">
        <div>
            <h2 class="mb-0"><?= e($session['title']) ?></h2>
            <p class="muted mt-0"><?= e($session['course_code']) ?> — <?= e($session['course_name']) ?></p>
        </div>
        <div class="flex gap-8">
            <span class="pill <?= $session['status'] === 'open' ? 'pill-open' : 'pill-closed' ?>" style="font-size:14px;"><?= ucfirst($session['status']) ?></span>
            <?php if ($session['status'] === 'open'): ?>
                <a href="?id=<?= $sessionId ?>&close=1" class="btn btn-danger btn-sm" onclick="return confirm('Close this session now?');">Close Session</a>
            <?php endif; ?>
            <a href="?id=<?= $sessionId ?>&export=1" class="btn btn-outline btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="grid grid-2" style="margin-top:20px;">
        <div class="card qr-box">
            <h3 class="card-title mt-0">Scan to Check In</h3>
            <div id="qrcode" style="display:flex; justify-content:center; margin-bottom:16px;"></div>
            <p class="muted" style="font-size:13px;">Session code: <strong><?= e($session['session_code']) ?></strong></p>
            <div class="link-box"><?= e($url) ?></div>
            <p class="muted" style="font-size:12px; margin-top:10px;">Opens: <?= date('M j, g:i A', strtotime($session['opens_at'])) ?> · Closes: <?= date('M j, g:i A', strtotime($session['closes_at'])) ?></p>
        </div>

        <div class="card">
            <h3 class="card-title mt-0">Summary</h3>
            <div class="grid grid-2" style="gap:14px;">
                <div class="stat-card"><div class="stat-number"><?= count($records) ?></div><div class="stat-label">Checked In</div></div>
                <div class="stat-card"><div class="stat-number"><?= (int)$totalStudents ?></div><div class="stat-label">Enrolled</div></div>
            </div>
            <p class="muted" style="margin-top:16px; font-size:13px;">
                Attendance rate: <strong><?= $totalStudents > 0 ? round((count($records) / $totalStudents) * 100) : 0 ?>%</strong>
            </p>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title mt-0">Check-ins (<?= count($records) ?>)</h3>
        <?php if (empty($records)): ?>
            <p class="muted">No check-ins yet. This list updates automatically.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Index Number</th><th>Full Name</th><th>Submitted At</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= e($r['index_number']) ?></td>
                        <td><?= e($r['full_name']) ?></td>
                        <td><?= date('g:i:s A', strtotime($r['submitted_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: <?= json_encode($url) ?>,
    width: 200,
    height: 200,
    colorDark: "#16A34A",
    colorLight: "#ffffff"
});

<?php if ($session['status'] === 'open'): ?>
// Auto-refresh the page every 10 seconds to show new check-ins live
setTimeout(function () { window.location.reload(); }, 10000);
<?php endif; ?>
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
