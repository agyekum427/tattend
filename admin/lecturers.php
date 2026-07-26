<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminId = current_admin_id();
$error = '';
$success = '';

// Add lecturer — always owned by the currently logged-in admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lecturer') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $pdo->prepare("SELECT id FROM lecturers WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'A lecturer with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO lecturers (admin_id, name, email, password) VALUES (?, ?, ?, ?)")
                ->execute([$adminId, $name, $email, $hash]);
            $success = 'Lecturer account created.';
        }
    }
}

// Toggle status — scoped so an admin can only ever touch their own lecturers
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM lecturers WHERE id = ? AND admin_id = ?");
    $stmt->execute([$id, $adminId]);
    $current = $stmt->fetchColumn();
    if ($current) {
        $new = $current === 'active' ? 'disabled' : 'active';
        $pdo->prepare("UPDATE lecturers SET status = ? WHERE id = ? AND admin_id = ?")->execute([$new, $id, $adminId]);
    }
    header('Location: lecturers.php');
    exit;
}

// Delete lecturer — scoped the same way
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM lecturers WHERE id = ? AND admin_id = ?")->execute([$id, $adminId]);
    header('Location: lecturers.php');
    exit;
}

$lecturers = $pdo->prepare("
    SELECT l.*,
        (SELECT COUNT(*) FROM courses c WHERE c.lecturer_id = l.id) AS course_count
    FROM lecturers l WHERE l.admin_id = ? ORDER BY l.created_at DESC");
$lecturers->execute([$adminId]);
$lecturers = $lecturers->fetchAll();

$pageTitle = 'Manage Lecturers';
$navContext = 'admin';
$assetPath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <h2>Manage Lecturer Accounts</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="card">
        <h3 class="card-title mt-0">Add Lecturer Account</h3>
        <form method="post" class="flex gap-12" style="flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add_lecturer">
            <div style="flex:1; min-width:160px;">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div style="flex:1; min-width:200px;">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div style="width:180px;">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Add Lecturer</button>
        </form>
    </div>

    <div class="card">
        <h3 class="card-title mt-0">All Lecturers (<?= count($lecturers) ?>)</h3>
        <?php if (empty($lecturers)): ?>
            <p class="muted">No lecturers yet.</p>
        <?php else: ?>
            <div class="table-responsive"><table>
                <thead><tr><th>Name</th><th>Email</th><th>Courses</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($lecturers as $l): ?>
                    <tr>
                        <td><?= e($l['name']) ?></td>
                        <td><?= e($l['email']) ?></td>
                        <td><?= (int)$l['course_count'] ?></td>
                        <td><span class="pill <?= $l['status'] === 'active' ? 'pill-open' : 'pill-closed' ?>"><?= ucfirst($l['status']) ?></span></td>
                        <td class="flex gap-8">
                            <a href="?toggle=<?= (int)$l['id'] ?>" class="btn btn-sm btn-outline">
                                <?= $l['status'] === 'active' ? 'Disable' : 'Enable' ?>
                            </a>
                            <a href="?delete=<?= (int)$l['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this lecturer and all their data? This cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
