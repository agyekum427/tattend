<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();

$courses = $pdo->prepare("SELECT * FROM courses WHERE lecturer_id = ? ORDER BY course_name");
$courses->execute([$lecturerId]);
$courses = $courses->fetchAll();

$selectedCourseId = (int)($_GET['course_id'] ?? ($courses[0]['id'] ?? 0));

// Make sure the selected course actually belongs to this lecturer
if ($selectedCourseId) {
    $ownCheck = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $ownCheck->execute([$selectedCourseId, $lecturerId]);
    if (!$ownCheck->fetch()) {
        $selectedCourseId = 0;
    }
}

$chartLabels = [];
$chartData = [];
$sessions = [];
$totalSessions = 0;
$studentSummary = [];

if ($selectedCourseId) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.created_at,
            (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id = s.id) AS checkins
        FROM attendance_sessions s
        WHERE s.course_id = ? AND s.lecturer_id = ?
        ORDER BY s.created_at ASC");
    $stmt->execute([$selectedCourseId, $lecturerId]);
    $sessions = $stmt->fetchAll();
    $totalSessions = count($sessions);

    foreach ($sessions as $s) {
        $chartLabels[] = $s['title'];
        $chartData[] = (int)$s['checkins'];
    }

    // Per-student attendance summary: how many sessions each student was present for
    $stmt = $pdo->prepare("
        SELECT st.id, st.index_number, st.full_name,
            (SELECT COUNT(*) FROM attendance_records r
                JOIN attendance_sessions s2 ON r.session_id = s2.id
                WHERE r.student_id = st.id AND s2.course_id = st.course_id) AS present_count
        FROM students st
        WHERE st.course_id = ?
        ORDER BY st.full_name");
    $stmt->execute([$selectedCourseId]);
    $studentSummary = $stmt->fetchAll();
}

// CSV export of the per-student summary (must run before any HTML output)
if (isset($_GET['export_summary']) && $selectedCourseId) {
    $courseInfo = $pdo->prepare("SELECT course_code FROM courses WHERE id = ?");
    $courseInfo->execute([$selectedCourseId]);
    $courseCode = $courseInfo->fetchColumn();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $courseCode) . '_attendance_summary.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Index Number', 'Full Name', 'Sessions Present', 'Total Sessions', 'Attendance %']);
    foreach ($studentSummary as $s) {
        $pct = $totalSessions > 0 ? round(($s['present_count'] / $totalSessions) * 100) : 0;
        fputcsv($out, [$s['index_number'], $s['full_name'], (int)$s['present_count'], $totalSessions, $pct . '%']);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Reports';
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h2>Attendance Reports</h2>

    <div class="card">
        <div class="flex gap-8" style="flex-wrap:wrap;">
            <?php foreach ($courses as $c): ?>
                <a href="?course_id=<?= (int)$c['id'] ?>"
                   class="btn btn-sm <?= $c['id'] == $selectedCourseId ? '' : 'btn-outline' ?>">
                    <?= e($c['course_code']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="card"><p class="muted">No session data yet for this course.</p></div>
    <?php else: ?>
        <div class="card">
            <h3 class="card-title mt-0">Check-ins per Session</h3>
            <canvas id="attendanceChart" height="90"></canvas>
        </div>

        <div class="card">
            <div class="flex-between">
                <h3 class="card-title mt-0">Student Attendance Summary</h3>
                <div class="flex gap-8">
                    <a href="?course_id=<?= (int)$selectedCourseId ?>&export_summary=1" class="btn btn-sm btn-outline">Export CSV</a>
                    <a href="student_report_print.php?course_id=<?= (int)$selectedCourseId ?>" target="_blank" class="btn btn-sm">Print / Save as PDF</a>
                </div>
            </div>
            <p class="muted" style="font-size:13px; margin-top:-8px;">Out of <?= (int)$totalSessions ?> session<?= $totalSessions === 1 ? '' : 's' ?> held for this course.</p>
            <div class="table-responsive"><table>
                <thead><tr><th>Index Number</th><th>Full Name</th><th>Present</th><th>Attendance %</th></tr></thead>
                <tbody>
                <?php foreach ($studentSummary as $s):
                    $pct = $totalSessions > 0 ? round(($s['present_count'] / $totalSessions) * 100) : 0; ?>
                    <tr>
                        <td><?= e($s['index_number']) ?></td>
                        <td><?= e($s['full_name']) ?></td>
                        <td><?= (int)$s['present_count'] ?> / <?= (int)$totalSessions ?></td>
                        <td><?= $pct ?>%</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($studentSummary)): ?>
                    <tr><td colspan="4" class="muted">No students registered for this course yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <div class="card">
            <h3 class="card-title mt-0">Session Detail</h3>
            <div class="table-responsive"><table>
                <thead><tr><th>Session</th><th>Date</th><th>Check-ins</th></tr></thead>
                <tbody>
                <?php foreach (array_reverse($sessions) as $s): ?>
                    <tr>
                        <td><?= e($s['title']) ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($s['created_at'])) ?></td>
                        <td><?= (int)$s['checkins'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
        new Chart(document.getElementById('attendanceChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Check-ins',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: '#16A34A',
                    borderRadius: 6
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
        </script>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
