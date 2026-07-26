<?php
/**
 * T Attend — Demo account seeder
 * Run this ONCE in your browser after importing tattend.sql:
 *   http://localhost/tattend/database/seed_users.php
 * It creates a demo admin, a demo lecturer, a demo course, and
 * sample students — using properly hashed passwords. The script
 * deletes itself after a successful run.
 */

require_once __DIR__ . '/../config/db.php';

$adminPass    = 'admin123';
$lecturerPass = 'lecturer123';

try {
    // Admin
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (name, username, password) VALUES (?, ?, ?)")
            ->execute(['System Administrator', 'admin', $hash]);
        echo "<p>Created admin account.</p>";
    } else {
        echo "<p>Admin account already exists — skipped.</p>";
    }

    // Lecturer
    $stmt = $pdo->prepare("SELECT id FROM lecturers WHERE email = 'lecturer@tattend.com'");
    $stmt->execute();
    $lecturer = $stmt->fetch();
    if (!$lecturer) {
        $hash = password_hash($lecturerPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = 'admin'");
        $stmt->execute();
        $adminId = $stmt->fetchColumn();
        $pdo->prepare("INSERT INTO lecturers (admin_id, name, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$adminId, 'Dr. Ama Owusu', 'lecturer@tattend.com', $hash]);
        $lecturerId = $pdo->lastInsertId();
        echo "<p>Created demo lecturer account.</p>";
    } else {
        $lecturerId = $lecturer['id'];
        echo "<p>Demo lecturer already exists — skipped.</p>";
    }

    // Demo course
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE lecturer_id = ? AND course_code = 'IT101'");
    $stmt->execute([$lecturerId]);
    $course = $stmt->fetch();
    if (!$course) {
        $pdo->prepare("INSERT INTO courses (lecturer_id, course_name, course_code) VALUES (?, ?, ?)")
            ->execute([$lecturerId, 'Introduction to Information Technology', 'IT101']);
        $courseId = $pdo->lastInsertId();
        echo "<p>Created demo course (IT101).</p>";

        $students = [
            ['IT/2023/001', 'Kwame Mensah'],
            ['IT/2023/002', 'Efua Boateng'],
            ['IT/2023/003', 'Yaw Darko'],
            ['IT/2023/004', 'Abena Kufuor'],
            ['IT/2023/005', 'Kofi Asante'],
        ];
        $ins = $pdo->prepare("INSERT INTO students (course_id, index_number, full_name) VALUES (?, ?, ?)");
        foreach ($students as $s) {
            $ins->execute([$courseId, $s[0], $s[1]]);
        }
        echo "<p>Added 5 demo students.</p>";
    } else {
        echo "<p>Demo course already exists — skipped.</p>";
    }

    echo "<hr><p><strong>Done.</strong></p>";
    echo "<p>Admin login &mdash; username: <code>admin</code> / password: <code>{$adminPass}</code></p>";
    echo "<p>Lecturer login &mdash; email: <code>lecturer@tattend.com</code> / password: <code>{$lecturerPass}</code></p>";

    // Self-delete for security
    @unlink(__FILE__);
    echo "<p><em>This seed script has now deleted itself.</em></p>";
    echo '<p><a href="../index.php">Go to T Attend &rarr;</a></p>';

} catch (PDOException $e) {
    echo "<p>Error seeding data: " . htmlspecialchars($e->getMessage()) . "</p>";
}
