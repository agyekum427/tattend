<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();
$courseId = (int)($_GET['course_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$courseId, $lecturerId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: reports.php');
    exit;
}

$totalSessions = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE course_id = ?");
$totalSessions->execute([$courseId]);
$totalSessions = (int)$totalSessions->fetchColumn();

$stmt = $pdo->prepare("
    SELECT st.index_number, st.full_name,
        (SELECT COUNT(*) FROM attendance_records r
            JOIN attendance_sessions s2 ON r.session_id = s2.id
            WHERE r.student_id = st.id AND s2.course_id = st.course_id) AS present_count
    FROM students st
    WHERE st.course_id = ?
    ORDER BY st.full_name");
$stmt->execute([$courseId]);
$studentSummary = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Summary — <?= e($course['course_code']) ?></title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #1F2937; margin: 40px; }
    h1 { font-size: 20px; margin-bottom: 2px; }
    .subtitle { color: #6B7280; font-size: 13px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
    th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #E5E7EB; }
    th { background: #E7F8ED; color: #128038; text-transform: uppercase; font-size: 11px; letter-spacing: 0.03em; }
    tr:nth-child(even) { background: #FAFAFA; }
    .meta { display: flex; gap: 30px; margin-bottom: 10px; font-size: 13px; }
    .meta strong { color: #16A34A; }
    .toolbar { margin-bottom: 24px; }
    .toolbar button {
        background: #16A34A; color: #fff; border: none; padding: 10px 18px;
        border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;
    }
    @media print {
        .toolbar { display: none; }
        body { margin: 15mm; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <h1>Attendance Summary</h1>
    <p class="subtitle"><?= e($course['course_code']) ?> — <?= e($course['course_name']) ?> &nbsp;·&nbsp; Generated <?= date('F j, Y g:i A') ?></p>

    <div class="meta">
        <div>Total Sessions Held: <strong><?= $totalSessions ?></strong></div>
        <div>Total Students: <strong><?= count($studentSummary) ?></strong></div>
    </div>

    <table>
        <thead>
            <tr><th>#</th><th>Index Number</th><th>Full Name</th><th>Present</th><th>Attendance %</th></tr>
        </thead>
        <tbody>
        <?php foreach ($studentSummary as $i => $s):
            $pct = $totalSessions > 0 ? round(($s['present_count'] / $totalSessions) * 100) : 0; ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($s['index_number']) ?></td>
                <td><?= e($s['full_name']) ?></td>
                <td><?= (int)$s['present_count'] ?> / <?= $totalSessions ?></td>
                <td><?= $pct ?>%</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($studentSummary)): ?>
            <tr><td colspan="5">No students registered for this course yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
