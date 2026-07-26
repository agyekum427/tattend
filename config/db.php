<?php
// Fallbacks provided for production
$host     = getenv('DB_HOST')     ?: 'tattend-agyekumyaw427-3201.i.aivencloud.com';
$dbname   = getenv('DB_NAME')     ?: 'defaultdb';
$username = getenv('DB_USER')     ?: 'avnadmin';
$password = getenv('DB_PASSWORD') ?: 'AVNS_1K8wwe_hAo_50D4KXlT';
$port     = getenv('DB_PORT')     ?: '19672';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Kills execution so PHP doesn't keep running down to $pdo->prepare() with $pdo set to null
    die("Database Connection Error: " . $e->getMessage());
}
