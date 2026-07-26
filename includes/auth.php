<?php
/**
 * T Attend — Auth helpers
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_lecturer_logged_in(): bool {
    return isset($_SESSION['lecturer_id']);
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function require_lecturer(): void {
    if (!is_lecturer_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_lecturer_id() {
    return $_SESSION['lecturer_id'] ?? null;
}

function current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Generate a short, unique-ish session code, e.g. "8F3K2Q" */
function generate_session_code(int $length = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/** Build the full public check-in URL for a session code */
function checkin_url(string $sessionCode): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/tattend/lecturer/x.php'));
    // Normalize base to project root (strip /lecturer, /student, /admin if present)
    $base = preg_replace('#/(lecturer|student|admin)$#', '', $base);
    return "{$protocol}://{$host}{$base}/student/checkin.php?code=" . urlencode($sessionCode);
}
