<?php
// ۱. شروع نشست برای دسترسی به آن
session_start();

// ۲. خالی کردن آرایه نشست
$_SESSION = array();

// ۳. حذف کوکی نشست (برای امنیت بیشتر)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ۴. نابودی کامل نشست
session_destroy();

// ۵. بازگشت به صفحه لاگین
header("Location: user-login.php");
exit;
