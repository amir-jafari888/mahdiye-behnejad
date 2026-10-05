<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once '../partials/db.php';

$error = '';
$maxAttempts = 5;
$lockoutMinutes = 15;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$username = trim($_POST['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = new DateTimeImmutable();
    $stmt = $pdo->prepare("
        SELECT id, attempts, first_attempt_at
        FROM admin_login_attempts
        WHERE ip_address = ? AND username = ?
        LIMIT 1
    ");
    $stmt->execute([$ipAddress, $username]);
    $attemptData = $stmt->fetch(PDO::FETCH_ASSOC);

    $locked = false;

    if ($attemptData) {
        $firstAttempt = new DateTimeImmutable($attemptData['first_attempt_at']);
        $lockUntil = $firstAttempt->modify("+{$lockoutMinutes} minutes");

        if ((int)$attemptData['attempts'] >= $maxAttempts && $now < $lockUntil) {
            $locked = true;
        } elseif ($now >= $lockUntil) {
            $pdo->prepare("DELETE FROM admin_login_attempts WHERE id = ?")
                ->execute([(int)$attemptData['id']]);
            $attemptData = false;
        }
    }

    if ($locked) {
        $error = "تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً چند دقیقه بعد دوباره تلاش کنید.";
    } else {
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $pdo->prepare("
                DELETE FROM admin_login_attempts
                WHERE ip_address = ? AND username = ?
            ")->execute([$ipAddress, $username]);

            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $admin['username'];

            header("Location: index.php");
            exit();
        }

        if ($attemptData) {
            $pdo->prepare("
                UPDATE admin_login_attempts
                SET attempts = attempts + 1, last_attempt_at = ?
                WHERE id = ?
            ")->execute([
                $now->format('Y-m-d H:i:s'),
                (int)$attemptData['id']
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO admin_login_attempts
                (ip_address, username, attempts, first_attempt_at, last_attempt_at)
                VALUES (?, ?, 1, ?, ?)
            ")->execute([
                $ipAddress,
                $username,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s')
            ]);
        }

        $error = "نام کاربری یا رمز عبور اشتباه است.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahdiye Beauty | پنل مدیریت</title>
    <style>
        /* --- 1. متغیرهای پروژه --- */
        :root {
            --primary-color: #77dd77;
            --secondary-color: #eadbc8;
            --contrast-color: #5d4037;
            --accent-color: #ffffff;
            --transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            --primary-hover: #388e3c;
            --bg-soft-pink: rgba(255, 170, 183, 0.32);
            --text-main: #444;
            --text-muted: #777;
            --error-color: #ff5252;
            --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- 2. تنظیمات پایه صفحه لاگین --- */
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: inherit;
            /* در صورت نیاز می‌توانید پس‌زمینه تصویر یا گرادینت دلخواه اضافه کنید */
            background: radial-gradient(circle at center, rgba(234, 219, 200, 0.4) 0%, rgba(255, 255, 255, 0.8) 100%);
        }

        /* --- 3. کانتینر اصلی لاگین --- */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: fadeInUp 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        /* --- 4. عنوان برند و زیرعنوان --- */
        .brand-title {
            color: var(--contrast-color);
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .sub-title {
            color: var(--text-muted);
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 25px;
        }

        /* --- 5. کارت اصلی Glassmorphism --- */
        .login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            padding: 35px 30px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6),
                0 15px 35px -10px rgba(0, 0, 0, 0.07);
            transition: var(--transition-smooth);
            box-sizing: border-box;
        }

        .login-card:hover {
            box-shadow: inset 0 0 0 2px var(--secondary-color),
                0 22px 40px -10px rgba(0, 0, 0, 0.1);
        }

        /* --- 6. پیغام خطا (Error Message) --- */
        .error-msg {
            padding: 14px 18px;
            background: var(--bg-soft-pink);
            color: var(--error-color);
            border: 1px solid rgba(255, 82, 82, 0.3);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.4s ease;
        }

        /* --- 7. فرم و گروه ورودی‌ها --- */
        .login-card form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: right;
        }

        .form-group label {
            color: var(--contrast-color);
            font-size: 0.9rem;
            font-weight: 600;
            padding-right: 4px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition-smooth);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            box-sizing: border-box;
        }

        /* افکت فوکوس اینپوت‌ها */
        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(119, 221, 119, 0.2);
        }

        /* --- 8. دکمه ورود --- */
        .login-card button[type="submit"] {
            width: 100%;
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 6px 18px rgba(119, 221, 119, 0.4);
            margin-top: 10px;
        }

        .login-card button[type="submit"]:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(56, 142, 60, 0.4);
        }

        .login-card button[type="submit"]:active {
            transform: translateY(0);
        }

        .password-field {
            position: relative;
            width: 100%;
        }

        .password-field input {
            width: 100%;
            padding-left: 48px !important;
        }

        .password-field>.password-toggle {
            position: absolute !important;
            top: 50% !important;
            left: 10px !important;
            right: auto !important;
            transform: translateY(-50%) !important;
            width: 36px !important;
            min-width: 36px !important;
            max-width: 36px !important;
            height: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            outline: 0 !important;
            background: transparent !important;
            color: #777 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            box-shadow: none !important;
            z-index: 5 !important;
        }

        .password-field>.password-toggle:hover {
            background: transparent !important;
            color: #5d4037 !important;
            transform: translateY(-50%) !important;
        }

        .password-field>.password-toggle:focus,
        .password-field>.password-toggle:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .password-field>.password-toggle svg {
            display: block !important;
            width: 20px !important;
            height: 20px !important;
            flex: none !important;
            pointer-events: none;
        }

        /* --- 9. انیمیشن ورودی --- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <div class="brand-title">Mahdiye Beauty</div>
        <div class="sub-title">پنل مدیریت</div>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>نام کاربری</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور</label>
                    <div class="password-field">
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="button" class="password-toggle" aria-label="نمایش رمز عبور">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit">ورود به سیستم</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.password-field').forEach(field => {
            const input = field.querySelector('input');
            const button = field.querySelector('.password-toggle');
            if (!input || !button) return;

            const eyeOpen = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.8"/></svg>';

            const eyeClosed = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3.2 3.5M6.2 6.8C3.9 8.2 2.5 12 2.5 12s3.5 6 9.5 6c1 0 1.9-.2 2.7-.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            button.innerHTML = eyeOpen;

            button.addEventListener('click', () => {
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.innerHTML = visible ? eyeOpen : eyeClosed;
                button.setAttribute('aria-label', visible ? 'نمایش رمز عبور' : 'مخفی کردن رمز عبور');
            });
        });
    </script>

</body>

</html>