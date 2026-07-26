<?php
/**
 * T Attend — Database connection
 * Default XAMPP MySQL settings: host=localhost, user=root, no password.
 * Change these if your local setup differs.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tattend');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Make sure XAMPP's MySQL service is running and the 'tattend' "
        . "database has been imported (see database/tattend.sql). Error: " . $e->getMessage());
}
