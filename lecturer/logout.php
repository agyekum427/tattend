<?php
require_once __DIR__ . '/../includes/auth.php';
unset($_SESSION['lecturer_id'], $_SESSION['lecturer_name']);
session_destroy();
header('Location: login.php');
exit;
