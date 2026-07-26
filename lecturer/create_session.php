<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();
$error = '';

$courses = $pdo->prepare("SELECT * FROM courses WHERE lecturer_id = ? ORDER BY course_name");
$courses->execute([$lecturerId]);
$courses = $courses->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = (int)($_POST['duration'] ?? 15);

    $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $check->execute([$courseId, $lecturerId]);

    if (!$check->fetch()) {
        $error = 'Please select a valid course.';
    } elseif ($title === '') {
        $error = 'Please give the session a title (e.g. "Week 5 Lecture").';
    } elseif ($duration < 1 || $duration > 240) {
        $error = 'Duration must be between 1 and 240 minutes.';
    } else {
        // Ensure unique session code
        do {
            $code = generate_session_code();
            $exists = $pdo->prepare("SELECT id FROM attendance_sessions WHERE session_code = ?");
            $exists->execute([$code]);
        } while ($exists->fetch());

        $opensAt = date('Y-m-d H:i:s');
        $closesAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

        $stmt = $pdo->prepare("
            INSERT INTO attendance_sessions (course_id, lecturer_id, session_code, title, opens_at, closes_at, status)
            VALUES (?, ?, ?, ?, ?, ?, 'open')");
        $stmt->execute([$courseId, $lecturerId, $code, $title, $opensAt, $closesAt]);

        header('Location: session_view.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

$pageTitle = 'New Session';
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <h2 class="center">Start Attendance Session</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if (empty($courses)): ?>
            <p class="muted center">You need to add a course first. <a href="students.php">Go to Students</a></p>
        <?php else: ?>
        <form method="post">
            <div class="form-group">
                <label>Course</label>
                <select name="course_id" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['course_code']) ?> — <?= e($c['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Session Title</label>
                <input type="text" name="title" placeholder="e.g. Week 5 Lecture" required>
            </div>
            <div class="form-group">
                <label>Open For (minutes)</label>
                <input type="number" name="duration" value="15" min="1" max="240" required>
            </div>
            <button type="submit" class="btn btn-block">Generate Session Link &amp; QR Code</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
