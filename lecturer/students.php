<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_lecturer();

$lecturerId = current_lecturer_id();
$error = '';
$success = '';

// Handle new course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $courseName = trim($_POST['course_name'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? '');
    if ($courseName === '' || $courseCode === '') {
        $error = 'Please provide both course name and code.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO courses (lecturer_id, course_name, course_code) VALUES (?, ?, ?)");
        $stmt->execute([$lecturerId, $courseName, $courseCode]);
        $success = 'Course added.';
    }
}

// Handle single student add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $indexNumber = trim($_POST['index_number'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $check->execute([$courseId, $lecturerId]);

    if (!$check->fetch()) {
        $error = 'Invalid course.';
    } elseif ($indexNumber === '' || $fullName === '') {
        $error = 'Please provide index number and full name.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (course_id, index_number, full_name) VALUES (?, ?, ?)");
            $stmt->execute([$courseId, $indexNumber, $fullName]);
            $success = 'Student added.';
        } catch (PDOException $e) {
            $error = 'That index number already exists in this course.';
        }
    }
}

// Handle bulk import (CSV: index_number,full_name)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_students') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $check->execute([$courseId, $lecturerId]);

    if (!$check->fetch()) {
        $error = 'Invalid course.';
    } elseif (empty($_FILES['csv_file']['tmp_name'])) {
        $error = 'Please choose a CSV file to import.';
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $added = 0; $skipped = 0;
        if ($handle) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO students (course_id, index_number, full_name) VALUES (?, ?, ?)");
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;
                $idx = trim($row[0]); $name = trim($row[1]);
                if ($idx === '' || $name === '' || strtolower($idx) === 'index_number') continue;
                $stmt->execute([$courseId, $idx, $name]);
                if ($stmt->rowCount() > 0) { $added++; } else { $skipped++; }
            }
            fclose($handle);
            $success = "Import complete: {$added} students added" . ($skipped ? ", {$skipped} skipped (duplicates)." : ".");
        }
    }
}

// Handle delete
if (isset($_GET['delete_student'])) {
    $sid = (int)$_GET['delete_student'];
    $stmt = $pdo->prepare("
        DELETE s FROM students s
        JOIN courses c ON s.course_id = c.id
        WHERE s.id = ? AND c.lecturer_id = ?");
    $stmt->execute([$sid, $lecturerId]);
    header('Location: students.php');
    exit;
}

$courses = $pdo->prepare("SELECT * FROM courses WHERE lecturer_id = ? ORDER BY created_at DESC");
$courses->execute([$lecturerId]);
$courses = $courses->fetchAll();

$selectedCourseId = (int)($_GET['course_id'] ?? ($courses[0]['id'] ?? 0));

$students = [];
if ($selectedCourseId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE course_id = ? ORDER BY full_name");
    $stmt->execute([$selectedCourseId]);
    $students = $stmt->fetchAll();
}

$pageTitle = 'Students';
$navContext = 'lecturer';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h2>Manage Students</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="card">
        <h3 class="card-title mt-0">Your Courses</h3>
        <div class="flex gap-8" style="flex-wrap:wrap; margin-bottom:16px;">
            <?php foreach ($courses as $c): ?>
                <a href="?course_id=<?= (int)$c['id'] ?>"
                   class="btn btn-sm <?= $c['id'] == $selectedCourseId ? '' : 'btn-outline' ?>">
                    <?= e($c['course_code']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form method="post" class="flex gap-12" style="flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add_course">
            <div style="flex:1; min-width:180px;">
                <label>Course Name</label>
                <input type="text" name="course_name" placeholder="e.g. Data Structures" required>
            </div>
            <div style="width:140px;">
                <label>Course Code</label>
                <input type="text" name="course_code" placeholder="e.g. CS201" required>
            </div>
            <button type="submit" class="btn">Add Course</button>
        </form>
    </div>

    <?php if ($selectedCourseId): ?>
    <div class="grid grid-2">
        <div class="card">
            <h3 class="card-title mt-0">Add Student</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_student">
                <input type="hidden" name="course_id" value="<?= (int)$selectedCourseId ?>">
                <div class="form-group">
                    <label>Index Number</label>
                    <input type="text" name="index_number" placeholder="e.g. IT/2023/006" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <button type="submit" class="btn btn-block">Add Student</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-title mt-0">Bulk Import (CSV)</h3>
            <p class="muted" style="font-size:13px;">CSV format: <code>index_number,full_name</code> — one student per line, no header needed.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_students">
                <input type="hidden" name="course_id" value="<?= (int)$selectedCourseId ?>">
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-block btn-outline">Import Students</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title mt-0">Class List (<?= count($students) ?>)</h3>
        <?php if (empty($students)): ?>
            <p class="muted">No students added yet for this course.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Index Number</th><th>Full Name</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['index_number']) ?></td>
                        <td><?= e($s['full_name']) ?></td>
                        <td><a href="?course_id=<?= $selectedCourseId ?>&delete_student=<?= (int)$s['id'] ?>"
                               onclick="return confirm('Remove this student?');"
                               class="btn btn-sm btn-outline">Remove</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <div class="card"><p class="muted">Add a course above to start managing students.</p></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
