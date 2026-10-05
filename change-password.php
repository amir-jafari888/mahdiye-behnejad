<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit;
}

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/partials/db.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("
  SELECT id, password
  FROM users
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: user-login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['user_csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('درخواست نامعتبر است.');
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (
        !is_string($currentPassword) ||
        !is_string($newPassword) ||
        !is_string($passwordConfirm)
    ) {
        $error = 'اطلاعات وارد شده معتبر نیست.';
    } elseif (
        $currentPassword === '' ||
        $newPassword === '' ||
        $passwordConfirm === ''
    ) {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = 'رمز عبور فعلی اشتباه است.';
    } elseif (mb_strlen($newPassword) < 6) {
        $error = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
    } elseif ($newPassword !== $passwordConfirm) {
        $error = 'رمز عبور جدید و تکرار آن یکسان نیستند.';
    } elseif (password_verify($newPassword, $user['password'])) {
        $error = 'رمز عبور جدید باید با رمز فعلی متفاوت باشد.';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
      UPDATE users
      SET password = ?
      WHERE id = ?
    ");

        if ($stmt->execute([$hashedPassword, $userId])) {
            $success = 'رمز عبور شما با موفقیت تغییر کرد.';

            $stmt = $pdo->prepare("
        SELECT id, password
        FROM users
        WHERE id = ?
        LIMIT 1
      ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'تغییر رمز عبور انجام نشد. دوباره تلاش کنید.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر رمز عبور | مهدیه بیوتی</title>
    <link rel="stylesheet" href="./assets/css/form.css">
    <style>
        .success-msg {
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
            animation: successMsgIn .4s cubic-bezier(.22, 1, .36, 1);
        }

        .success-msg::before {
            content: "✓";
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

        @keyframes successMsgIn {
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
        <p class="sub-title">تغییر رمز عبور</p>

        <div class="auth-card">

            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-msg">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="change-password.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="current_password">رمز عبور فعلی</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        placeholder="رمز عبور فعلی"
                        dir="ltr"
                        autocomplete="current-password"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">رمز عبور جدید</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="حداقل ۶ کاراکتر"
                        dir="ltr"
                        autocomplete="new-password"
                        required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">تکرار رمز عبور جدید</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        placeholder="تکرار رمز عبور جدید"
                        dir="ltr"
                        autocomplete="new-password"
                        required>
                </div>

                <button type="submit">تغییر رمز عبور</button>
            </form>

            <div style="margin-top:16px;text-align:center;">
                <a href="user-reservation.php">بازگشت به رزروهای من</a>
            </div>

        </div>
    </div>

</body>

</html>