<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (
    empty($_SESSION['password_reset_id']) ||
    empty($_SESSION['password_reset_phone'])
) {
    header("Location: forgot-password.php");
    exit;
}

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/partials/db.php';

$error = '';
$resetId = (int) $_SESSION['password_reset_id'];

$stmt = $pdo->prepare("
  SELECT id, user_id, otp_hash, otp_expires_at, otp_attempts, verified_at, used_at
  FROM password_resets
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$resetId]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset || $reset['used_at'] !== null) {
    unset(
        $_SESSION['password_reset_id'],
        $_SESSION['password_reset_phone'],
        $_SESSION['password_reset_test_otp']
    );

    header("Location: forgot-password.php");
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

    $otp = trim($_POST['otp_code'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = 'کد تایید باید ۶ رقمی باشد.';
    } elseif ($reset['verified_at'] !== null) {
        header("Location: reset-password.php");
        exit;
    } elseif (strtotime($reset['otp_expires_at']) < time()) {
        $error = 'کد تایید منقضی شده است.';
    } elseif ((int) $reset['otp_attempts'] >= 5) {
        $error = 'تعداد تلاش‌های مجاز به پایان رسیده است.';
    } elseif (!password_verify($otp, $reset['otp_hash'])) {
        $stmt = $pdo->prepare("
      UPDATE password_resets
      SET otp_attempts = otp_attempts + 1
      WHERE id = ?
      AND otp_attempts < 5
    ");
        $stmt->execute([$resetId]);

        $error = 'کد تایید اشتباه است.';
    } else {
        $stmt = $pdo->prepare("
      UPDATE password_resets
      SET verified_at = NOW()
      WHERE id = ?
      AND verified_at IS NULL
      AND used_at IS NULL
    ");
        $stmt->execute([$resetId]);

        if ($stmt->rowCount() !== 1) {
            $error = 'درخواست بازیابی دیگر معتبر نیست.';
        } else {
            session_regenerate_id(true);

            $_SESSION['password_reset_verified'] = true;

            unset($_SESSION['password_reset_test_otp']);

            header("Location: reset-password.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("
    SELECT id, user_id, otp_hash, otp_expires_at, otp_attempts, verified_at, used_at
    FROM password_resets
    WHERE id = ?
    LIMIT 1
  ");
    $stmt->execute([$resetId]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
}

$testOtp = $_SESSION['password_reset_test_otp'] ?? '';
$phone = $_SESSION['password_reset_phone'];
$displayPhone = substr($phone, 0, 4) . '***' . substr($phone, 7);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید بازیابی رمز عبور | مهدیه بیوتی</title>
    <link rel="stylesheet" href="./assets/css/form.css">
</head>

<body class="body">

    <div class="auth-wrapper">
        <h1 class="brand-title">مهدیه بیوتی</h1>
        <p class="sub-title">تایید بازیابی رمز عبور</p>

        <div class="auth-card">

            <p style="text-align:center;margin-bottom:20px;">
                کد تایید برای شماره
                <strong dir="ltr"><?= htmlspecialchars($displayPhone, ENT_QUOTES, 'UTF-8') ?></strong>
                ارسال شده است.
            </p>

            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($testOtp !== ''): ?>
                <div style="margin-bottom:20px;padding:14px;border-radius:12px;background:#fff3cd;color:#856404;text-align:center;">
                    <div style="font-size:13px;margin-bottom:6px;">کد تستی بازیابی</div>
                    <strong dir="ltr" style="font-size:24px;letter-spacing:4px;">
                        <?= htmlspecialchars($testOtp, ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            <?php endif; ?>

            <form action="verify-reset-otp.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="otp_code">کد تایید</label>
                    <input
                        type="text"
                        id="otp_code"
                        name="otp_code"
                        placeholder="••••••"
                        dir="ltr"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        required>
                </div>

                <button type="submit">تایید کد</button>
            </form>

            <div style="margin-top:16px;text-align:center;">
                <a href="forgot-password.php">ارسال درخواست جدید</a>
            </div>

        </div>
    </div>

</body>

</html>