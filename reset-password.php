<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (
    empty($_SESSION['password_reset_id']) ||
    empty($_SESSION['password_reset_verified']) ||
    $_SESSION['password_reset_verified'] !== true
) {
    header("Location: user-login.php");
    exit;
}

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/partials/db.php';

$error = '';
$resetId = (int) $_SESSION['password_reset_id'];

$stmt = $pdo->prepare("
  SELECT id, user_id, otp_expires_at, verified_at, used_at
  FROM password_resets
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$resetId]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$reset ||
    $reset['used_at'] !== null ||
    $reset['verified_at'] === null ||
    strtotime($reset['otp_expires_at']) < time()
) {
    unset(
        $_SESSION['password_reset_id'],
        $_SESSION['password_reset_phone'],
        $_SESSION['password_reset_verified'],
        $_SESSION['password_reset_test_otp']
    );

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

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!is_string($password) || !is_string($passwordConfirm)) {
        $error = 'اطلاعات وارد شده معتبر نیست.';
    } elseif ($password === '' || $passwordConfirm === '') {
        $error = 'لطفاً هر دو فیلد رمز عبور را پر کنید.';
    } elseif (mb_strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'رمز عبور و تکرار آن یکسان نیستند.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
        SELECT id, user_id, verified_at, used_at, otp_expires_at
        FROM password_resets
        WHERE id = ?
        FOR UPDATE
      ");
            $stmt->execute([$resetId]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$reset ||
                $reset['used_at'] !== null ||
                $reset['verified_at'] === null ||
                strtotime($reset['otp_expires_at']) < time()
            ) {
                throw new RuntimeException('reset_invalid');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
      ");
            $stmt->execute([
                $hashedPassword,
                $reset['user_id']
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('user_update_failed');
            }

            $stmt = $pdo->prepare("
        UPDATE password_resets
        SET used_at = NOW()
        WHERE id = ?
        AND used_at IS NULL
      ");
            $stmt->execute([$resetId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('reset_already_used');
            }

            $pdo->commit();

            unset(
                $_SESSION['password_reset_id'],
                $_SESSION['password_reset_phone'],
                $_SESSION['password_reset_verified'],
                $_SESSION['password_reset_test_otp']
            );

            session_regenerate_id(true);

            header("Location: user-login.php?reset=success");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getMessage() === 'reset_invalid') {
                unset(
                    $_SESSION['password_reset_id'],
                    $_SESSION['password_reset_phone'],
                    $_SESSION['password_reset_verified'],
                    $_SESSION['password_reset_test_otp']
                );

                header("Location: user-login.php");
                exit;
            }

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
</head>

<body class="body">

    <div class="auth-wrapper">
        <h1 class="brand-title">مهدیه بیوتی</h1>
        <p class="sub-title">تعیین رمز عبور جدید</p>

        <div class="auth-card">

            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="reset-password.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

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
                        placeholder="تکرار رمز عبور"
                        dir="ltr"
                        autocomplete="new-password"
                        required>
                </div>

                <button type="submit">تغییر رمز عبور</button>
            </form>

        </div>
    </div>

</body>

</html>