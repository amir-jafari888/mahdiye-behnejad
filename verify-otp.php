<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

if (
    !isset($_SESSION['pending_user_id']) ||
    !isset($_SESSION['otp_code'])
) {
    header("Location: user-login.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['user_csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('درخواست نامعتبر است.');
    }
    $otp = trim($_POST['otp_code'] ?? "");
    if (time() > $_SESSION['otp_expire']) {
        $error = "کد تایید منقضی شده است.";
    } elseif ($_SESSION['otp_attempts'] >= 5) {
        $error = "تعداد تلاش بیش از حد مجاز است.";
    } elseif ($otp != $_SESSION['otp_code']) {
        $_SESSION['otp_attempts']++;
        $error = "کد تایید اشتباه است.";
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] =
            $_SESSION['pending_user_id'];

        $_SESSION['user_name'] =
            $_SESSION['pending_user_name'];
        unset(
            $_SESSION['pending_user_id'],
            $_SESSION['pending_user_name'],
            $_SESSION['pending_phone'],
            $_SESSION['pending_auth_action'],
            $_SESSION['otp_code'],
            $_SESSION['otp_expire'],
            $_SESSION['otp_attempts']
        );
        header("Location:user-reservation.php");
        exit;
    }
}

$phone = $_SESSION['pending_phone'];
$displayPhone = substr($phone, 0, 4) . '***' . substr($phone, 7);
// $maskedPhone = substr($phone, 0, 4) . '*****' . substr($phone, -2);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأیید شماره موبایل | Mahdiye Beauty</title>

    <link rel="stylesheet" href="assets/css/form.css">
</head>

<body class="body">

    <div class="auth-wrapper">
        <h1 class="brand-title">Mahdiye Beauty</h1>

        <p class="sub-title">
            تأیید شماره موبایل
        </p>

        <div class="auth-card">

            <div class="otp-header">
                <h2>کد تأیید</h2>
                <p>
                    کد تأیید به شماره
                    <strong><?= htmlspecialchars($displayPhone, ENT_QUOTES, 'UTF-8') ?></strong>
                    ارسال خواهد شد.
                </p>
            </div>

            <div style="text-align:center;margin:20px 0;padding:15px;border-radius:15px;background:rgba(255,255,255,.5);">
                <div style="font-size:.85rem;color:#666;margin-bottom:6px;">
                    کد تستی شما
                </div>
                <strong style="font-size:1.6rem;letter-spacing:5px;color:#333;">
                    <?= htmlspecialchars((string)$_SESSION['otp_code'], ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

            <form id="otp-form" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="otp-inputs" dir="ltr">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input" autocomplete="one-time-code">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                </div>

                <input type="hidden" name="otp_code" id="otp_code">

                <button type="submit">
                    تأیید شماره موبایل
                </button>

            </form>
            <?php if (!empty($error)): ?>
                <div class="form-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <p class="otp-note">
                کد تأیید را وارد کنید.
            </p>

        </div>
    </div>

    <script>
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpCode = document.getElementById('otp_code');
        const otpForm = document.getElementById('otp-form');

        otpInputs[0].focus();

        otpInputs.forEach((input, index) => {

            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 1);

                if (input.value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                updateOtpCode();
            });

            input.addEventListener('keydown', (event) => {
                if (
                    event.key === 'Backspace' &&
                    !input.value &&
                    index > 0
                ) {
                    otpInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (event) => {
                event.preventDefault();

                const pastedCode = event.clipboardData
                    .getData('text')
                    .replace(/\D/g, '')
                    .slice(0, 6);

                pastedCode.split('').forEach((digit, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = digit;
                    }
                });

                const nextIndex = Math.min(
                    pastedCode.length,
                    otpInputs.length - 1
                );

                otpInputs[nextIndex].focus();

                updateOtpCode();
            });

        });

        function updateOtpCode() {
            otpCode.value = Array.from(otpInputs)
                .map(input => input.value)
                .join('');
        }

        otpForm.addEventListener('submit', (event) => {
            updateOtpCode();

            if (otpCode.value.length !== 6) {
                event.preventDefault();
                alert('لطفاً کد ۶ رقمی را کامل وارد کنید.');
            }
        });
    </script>

</body>

</html>