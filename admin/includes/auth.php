<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function admin_csrf_token(): string
{
    return $_SESSION['admin_csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('درخواست نامعتبر است.');
    }
}
