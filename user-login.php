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
// اتصال به دیتابیس

// اگر کاربر از قبل وارد شده باشد، مستقیم به صفحه رزرو هدایت می‌شود
if (isset($_SESSION['user_id'])) {
  header("Location: user-reservation.php");
  exit;
}

$error = '';
$success = '';
$mode = isset($_GET['action']) && $_GET['action'] === 'register' ? 'register' : 'login';

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfToken = $_POST['csrf_token'] ?? '';

  if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['user_csrf_token'], $csrfToken)
  ) {
    http_response_code(403);
    exit('درخواست نامعتبر است.');
  }

  $action = $_POST['form_action'] ?? '';

  // --- بخش ثبت‌نام ---
  if ($action === 'register') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($phone) || empty($password)) {
      $error = 'لطفاً تمامی فیلدها را پر کنید.';
    } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
      $error = 'شماره موبایل وارد شده معتبر نیست (نمونه: ۰۹۱۲۳۴۵۶۷۸۹).';
    } elseif (mb_strlen($password) < 6) {
      $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } else {
      // بررسی تکراری نبودن شماره موبایل
      $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
      $stmt->execute([$phone]);
      if ($stmt->fetch()) {
        $error = 'این شماره موبایل قبلاً ثبت‌نام شده است.';
      } else {
        // هش کردن password و ذخیره
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (fullname, phone, password) VALUES (?, ?, ?)");

        if ($insertStmt->execute([$fullname, $phone, $hashedPassword])) {
          session_regenerate_id(true);

          $_SESSION['pending_user_id'] = $pdo->lastInsertId();
          $_SESSION['pending_user_name'] = $fullname;
          $_SESSION['pending_phone'] = $phone;
          $_SESSION['pending_auth_action'] = 'register';

          $_SESSION['otp_code'] = random_int(100000, 999999);
          $_SESSION['otp_expire'] = time() + 300;
          $_SESSION['otp_attempts'] = 0;

          header("Location: verify-otp.php");
          exit;
        } else {
          $error = 'خطایی در ثبت‌نام رخ داد. دوباره تلاش کنید.';
        }
      }
    }
    $mode = 'register';
  }

  // --- بخش ورود ---
  elseif ($action === 'login') {
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
      $error = 'لطفاً شماره موبایل و رمز عبور را وارد کنید.';
    } else {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
      $stmt->execute([$phone]);
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['pending_user_id'] = $user['id'];
        $_SESSION['pending_user_name'] = $user['fullname'];
        $_SESSION['pending_phone'] = $user['phone'];
        $_SESSION['pending_auth_action'] = 'login';

        $_SESSION['otp_code'] = random_int(100000, 999999);
        $_SESSION['otp_expire'] = time() + 300;
        $_SESSION['otp_attempts'] = 0;

        header("Location: verify-otp.php");
        exit;
      } else {
        $error = 'شماره موبایل یا رمز عبور اشتباه است.';
      }
    }
    $mode = 'login';
  }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود / ثبت‌نام زیباجو | مهدیه بیوتی</title>
  <link rel="stylesheet" href="./assets/css/form.css">
</head>

<body class="body">

  <div class="auth-wrapper">
    <h1 class="brand-title">مهدیه بیوتی</h1>
    <p class="sub-title">جهت رزرو نوبت خدمات زیبایی وارد شوید</p>

    <div class="auth-card">

      <!-- سوییچ بین ورود و ثبت‌نام -->
      <div class="auth-tabs">
        <a href="?action=login" class="tab-btn <?= $mode === 'login' ? 'active' : '' ?>">ورود</a>
        <a href="?action=register" class="tab-btn <?= $mode === 'register' ? 'active' : '' ?>">ثبت‌نام</a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($mode === 'login'): ?>
        <!-- فرم ورود -->
        <form action="user-login.php?action=login" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="login">

          <div class="form-group">
            <label for="phone">شماره موبایل</label>
            <input type="tel" id="phone" name="phone" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" required>
          </div>

          <div class="form-group">
            <label for="password">رمز عبور</label>
            <div class="password-field">
              <input type="password" id="password" name="password" placeholder="••••••••" dir="ltr" required>

              <button type="button" class="password-toggle" aria-label="نمایش رمز عبور">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  <circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.8" />
                </svg>
              </button>
            </div>
          </div>

          <button type="submit">ورود به بخش رزرو</button>
          <div style="margin-top:14px;text-align:center;">
            <a href="forgot-password.php">رمز عبور خود را فراموش کرده‌اید؟</a>
          </div>
        </form>
      <?php else: ?>
        <!-- فرم ثبت‌نام -->
        <form action="user-login.php?action=register" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['user_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="form_action" value="register">

          <div class="form-group">
            <label for="fullname">نام و نام خانوادگی</label>
            <input type="text" id="fullname" name="fullname" placeholder="مثال: سارا محمدی" required>
          </div>

          <div class="form-group">
            <label for="phone">شماره موبایل</label>
            <input type="tel" id="phone" name="phone" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" required>
          </div>

          <div class="form-group">
            <label for="password">تعیین رمز عبور</label>
            <div class="password-field">
              <input type="password" id="password" name="password" placeholder="حداقل ۶ کاراکتر" dir="ltr" required>

              <button type="button" class="password-toggle" aria-label="نمایش رمز عبور">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  <circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.8" />
                </svg>
              </button>
            </div>
          </div>

          <button type="submit">ثبت‌نام و ورود</button>
        </form>
      <?php endif; ?>

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