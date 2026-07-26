<?php
$host     = $_ENV['DB_HOST']     ?? 'tattend-agyekumyaw427-3201.i.aivencloud.com';
$user     = $_ENV['DB_USER']     ?? 'avnadmin';
$pass     = $_ENV['DB_PASS']     ?? 'AVNS_1K8wwe_hAo_50D4KXlT';
$dbname   = $_ENV['DB_NAME']     ?? 'defaultdb';
$port     = $_ENV['DB_PORT']     ?? '19672';

$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
