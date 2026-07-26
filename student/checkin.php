<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
$error = '';
$success = null; // will hold student info on success

if ($code === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code
    FROM attendance_sessions s JOIN courses c ON s.course_id = c.id
    WHERE s.session_code = ?");
$stmt->execute([$code]);
$session = $stmt->fetch();

if (!$session) {
    $error = 'That session code was not found. Please check with your lecturer and try again.';
} else {
    // Auto-close if expired
    if ($session['status'] === 'open' && strtotime($session['closes_at']) < time()) {
        $pdo->prepare("UPDATE attendance_sessions SET status='closed' WHERE id = ?")->execute([$session['id']]);
        $session['status'] = 'closed';
    }

    if ($session['status'] !== 'open') {
        $error = 'This attendance session has closed. Please contact your lecturer if you believe this is a mistake.';
    }
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $indexNumber = trim($_POST['index_number'] ?? '');

    if ($indexNumber === '') {
        $error = 'Please enter your index number.';
    } else {
        // Validate against the registered class list for this session's course
        $stmt = $pdo->prepare("SELECT * FROM students WHERE course_id = ? AND index_number = ?");
        $stmt->execute([$session['course_id'], $indexNumber]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = 'That index number is not registered for this course. Please check and try again, or contact your lecturer.';
        } else {
            // Check for duplicate submission
            $dupe = $pdo->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
            $dupe->execute([$session['id'], $student['id']]);

            if ($dupe->fetch()) {
                $error = 'You have already submitted attendance for this session, ' . $student['full_name'] . '.';
            } else {
                $ins = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id) VALUES (?, ?)");
                $ins->execute([$session['id'], $student['id']]);
                $success = $student;
            }
        }
    }
}

$pageTitle = 'Check In';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container-narrow">
    <div class="card">
        <?php if ($error && !$session): ?>
            <h2 class="center">Session Not Found</h2>
            <div class="alert alert-error"><?= e($error) ?></div>
            <a href="index.php" class="btn btn-block">Try Another Code</a>

        <?php elseif ($error && $session['status'] !== 'open' && !$success): ?>
            <h2 class="center">Session Closed</h2>
            <div class="alert alert-error"><?= e($error) ?></div>

        <?php elseif ($success): ?>
            <h2 class="center">✅ You're Marked Present</h2>
            <div class="alert alert-success" style="text-align:center;">
                <strong><?= e($success['full_name']) ?></strong><br>
                Index Number: <?= e($success['index_number']) ?><br>
                Course: <?= e($session['course_code']) ?> — <?= e($session['course_name']) ?><br>
                Session: <?= e($session['title']) ?>
            </div>
            <p class="muted center" style="font-size:13px;">Submitted at <?= date('g:i:s A') ?>. You may now close this page.</p>

        <?php else: ?>
            <h2 class="center">Confirm Your Attendance</h2>
            <p class="muted center" style="font-size:14px;">
                <?= e($session['course_code']) ?> — <?= e($session['course_name']) ?><br>
                Session: <strong><?= e($session['title']) ?></strong>
            </p>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="code" value="<?= e($code) ?>">
                <div class="form-group">
                    <label>Your Index Number</label>
                    <input type="text" name="index_number" placeholder="e.g. IT/2023/001" required autofocus>
                </div>
                <button type="submit" class="btn btn-block">Submit Attendance</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
