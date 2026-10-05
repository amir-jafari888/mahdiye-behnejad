<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/partials/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: user-reservation.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['user_csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('درخواست نامعتبر است.');
    }

    $phone = trim($_POST['phone'] ?? '');

    if (!preg_match('/^09[0-9]{9}$/', $phone)) {
        $error = 'شماره موبایل وارد شده معتبر نیست.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userId = (int) $user['id'];

            $stmt = $pdo->prepare("
        SELECT id, otp_attempts, otp_expires_at, verified_at, used_at, created_at
        FROM password_resets
        WHERE user_id = ?
        AND used_at IS NULL
        ORDER BY id DESC
        LIMIT 1
      ");
            $stmt->execute([$userId]);
            $activeReset = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($activeReset) {
                $attempts = (int) $activeReset['otp_attempts'];
                $createdAt = strtotime($activeReset['created_at']);

                if ($attempts >= 5) {
                    $cooldownSeconds = 15 * 60;
                    $cooldownActive = $createdAt !== false && (time() - $createdAt) < $cooldownSeconds;

                    if ($cooldownActive) {
                        $success = 'اگر این شماره در سامانه ثبت شده باشد، درخواست بازیابی شما در حال بررسی است.';
                    } else {
                        $stmt = $pdo->prepare("
              UPDATE password_resets
              SET used_at = NOW()
              WHERE id = ?
              AND used_at IS NULL
            ");
                        $stmt->execute([$activeReset['id']]);

                        $otp = (string) random_int(100000, 999999);
                        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                        $expireAt = date('Y-m-d H:i:s', time() + 300);

                        $stmt = $pdo->prepare("
              INSERT INTO password_resets
              (user_id, otp_hash, otp_expires_at, otp_attempts, request_ip)
              VALUES (?, ?, ?, 0, ?)
            ");

                        $stmt->execute([
                            $userId,
                            $otpHash,
                            $expireAt,
                            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                        ]);

                        $resetId = $pdo->lastInsertId();

                        session_regenerate_id(true);

                        $_SESSION['password_reset_id'] = $resetId;
                        $_SESSION['password_reset_phone'] = $phone;
                        $_SESSION['password_reset_test_otp'] = $otp;

                        header("Location: verify-reset-otp.php");
                        exit;
                    }
                } else {
                    session_regenerate_id(true);

                    $_SESSION['password_reset_id'] = $activeReset['id'];
                    $_SESSION['password_reset_phone'] = $phone;

                    if (!empty($_SESSION['password_reset_test_otp'])) {
                        header("Location: verify-reset-otp.php");
                        exit;
                    }

                    $success = 'اگر این شماره در سامانه ثبت شده باشد، کد بازیابی برای آن ارسال خواهد شد.';
                }
            } else {
                $otp = (string) random_int(100000, 999999);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expireAt = date('Y-m-d H:i:s', time() + 300);

                $stmt = $pdo->prepare("
          INSERT INTO password_resets
          (user_id, otp_hash, otp_expires_at, otp_attempts, request_ip)
          VALUES (?, ?, ?, 0, ?)
        ");

                $stmt->execute([
                    $userId,
                    $otpHash,
                    $expireAt,
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                $resetId = $pdo->lastInsertId();

                session_regenerate_id(true);

                $_SESSION['password_reset_id'] = $resetId;
                $_SESSION['password_reset_phone'] = $phone;
                $_SESSION['password_reset_test_otp'] = $otp;

                header("Location: verify-reset-otp.php");
                exit;
            }
        } else {
            $success = 'اگر این شماره در سامانه ثبت شده باشد، کد بازیابی برای آن ارسال خواهد شد.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازیابی رمز عبور | مهدیه بیوتی</title>
    <link rel="stylesheet" href="./assets/css/form.css">
    <style>
        .reset-alert {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 90%;
            margin-bottom: 18px;
            padding: 13px 14px;
            border: 1px solid rgba(119, 221, 119, .3);
            border-radius: 14px;
            background: rgba(119, 221, 119, .12);
            color: #388e3c;
            font-size: 13px;
            line-height: 1.8;
            box-shadow: 0 8px 20px rgba(119, 221, 119, .08);
            animation: resetAlertIn .4s cubic-bezier(.22, 1, .36, 1);
        }

        .reset-alert-icon {
            flex: 0 0 26px;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #77dd77;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        .reset-alert span:nth-child(2) {
            flex: 1;
        }

        .reset-alert-close {
            flex: 0 0 26px;
            width: 26px;
            height: 26px;
            padding: 0;
            border: 0;
            background: transparent;
            color: #388e3c;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            opacity: .65;
            transition: opacity .2s ease, transform .2s ease;
        }

        .reset-alert-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        @keyframes resetAlertIn {
            from {
                opacity: 0;
                transform: translateY(-10px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }
    </style>
</head>

<body class="body">

    <div class="auth-wrapper">
        <h1 class="brand-title">مهدیه بیوتی</h1>
        <p class="sub-title">بازیابی رمز عبور</p>

        <div class="auth-card">

            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="reset-alert" role="alert">
                    <span class="reset-alert-icon">✓</span>
                    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" class="reset-alert-close" aria-label="بستن پیام">×</button>
                </div>
            <?php endif; ?>

            <form action="forgot-password.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="phone">شماره موبایل</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                        dir="ltr"
                        inputmode="numeric"
                        autocomplete="tel"
                        required>
                </div>

                <button type="submit">ادامه بازیابی رمز عبور</button>
            </form>

            <div style="margin-top:16px;text-align:center;">
                <a href="user-login.php?action=login">بازگشت به صفحه ورود</a>
            </div>

        </div>
    </div>

    <script>
        document.querySelector('.reset-alert-close')?.addEventListener('click', function() {
            this.closest('.reset-alert')?.remove();
        });
    </script>

</body>

</html>