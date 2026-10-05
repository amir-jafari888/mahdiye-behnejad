<?php

require_once __DIR__ . '/includes/auth.php';
// ۱. اول سشن را شروع کنید
// if (session_status() === PHP_SESSION_NONE) session_start();

// ۲. پردازش‌های سمت سرور (حذف و افزودن) قبل از هرگونه خروجی HTML
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
// ۳. بررسی حذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = (int)($_POST['service_id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image_path FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

        if ($service) {
            $full_path = '../' . ltrim($service['image_path'], '/');

            if (is_file($full_path)) {
                unlink($full_path);
            }

            $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
        }
    }

    header("Location: admin-service.php?msg=deleted");
    exit;
}

// ۴. بررسی ثبت سرویس
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_service'])) {
    $upload = upload_secure_image(
        $_FILES['image'] ?? [],
        __DIR__ . '/../uploads/services/',
        'service_'
    );

    if ($upload['success']) {
        $db_path = 'uploads/services/' . $upload['file_name'];

        $stmt = $pdo->prepare(
            "INSERT INTO services (name, description, price, category, image_path)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['category'],
            $db_path
        ]);

        $_SESSION['msg'] = 'خدمت با موفقیت اضافه شد.';
    } else {
        $_SESSION['msg'] = $upload['error'];
    }

    header("Location: admin-service.php");
    exit;
}

// ۵. حالا که منطق‌های بالا تمام شد، هدر و بقیه موارد را لود کنید
require_once __DIR__ . '/includes/header-admin.php';
require_once __DIR__ . '/includes/auth.php';

// نمایش پیام‌ها (اگر لازم است)
// if (isset($_GET['msg'])) {
//     echo "<div class='alert'>عملیات با موفقیت انجام شد: " . htmlspecialchars($_GET['msg']) . "</div>";
// }
// اگر پیامی در سشن بود، آن را بردار، در غیر این صورت یک رشته خالی در نظر بگیر
$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت خدمات - Mahdiye Beauty</title>
    <style>
        /* --- 1. متغیرهای پروژه --- */
        :root {
            --primary-color: #77dd77;
            --secondary-color: #eadbc8;
            --contrast-color: #5d4037;
            --accent-color: #ffffff;
            --primary-hover: #388e3c;
            --bg-soft-pink: rgba(255, 170, 183, 0.32);
            --text-main: #444;
            --text-muted: #777;
            --error-color: #ff5252;
            --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- 2. کانتینر اصلی --- */
        .admin-container {
            padding: 120px 20px 40px 20px;
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
            animation: fadeInUp 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        .admin-container h2 {
            color: var(--contrast-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        /* --- 3. پیام‌های سیستم (Alert) --- */
        .alert {
            padding: 15px 20px !important;
            background: rgba(119, 221, 119, 0.25) !important;
            color: #2e7d32 !important;
            border: 1px solid rgba(119, 221, 119, 0.4);
            border-radius: 16px !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-weight: 600;
            margin-bottom: 0 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        /* --- 4. کارت‌های Glassmorphism --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 28px;
            padding: 30px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6),
                0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: var(--transition-smooth);
        }

        .glass-card:hover {
            box-shadow: inset 0 0 0 2px var(--secondary-color),
                0 20px 35px -10px rgba(0, 0, 0, 0.08);
        }

        .glass-card h3 {
            color: var(--contrast-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        /* --- 5. فرم‌ها و ورودی‌ها --- */
        .glass-card form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .glass-card input[type="text"],
        .glass-card textarea,
        .glass-card select {
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
        }

        /* کنترل تغییر ابعاد textarea (فقط عمودی) */
        .glass-card textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* استایل اختصاصی Dropdown (Select) */
        .glass-card select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%255D4037' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 18px center;
            padding-left: 45px;
        }

        .glass-card select option {
            background: #fff;
            color: var(--text-main);
            padding: 10px;
        }

        /* افکت فوکوس ورودی‌ها */
        .glass-card input[type="text"]:focus,
        .glass-card textarea:focus,
        .glass-card select:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(119, 221, 119, 0.2);
        }

        /* ورودی آپلود فایل */
        .glass-card input[type="file"] {
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.4);
            border: 1px dashed rgba(93, 64, 55, 0.3);
            border-radius: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
            color: var(--text-muted);
        }

        .glass-card input[type="file"]:hover {
            background: rgba(255, 255, 255, 0.75);
            border-color: var(--primary-color);
        }

        /* --- 6. دکمه‌ها --- */
        .btn-add {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 4px 15px rgba(119, 221, 119, 0.4);
            align-self: flex-start;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(56, 142, 60, 0.4);
        }

        .btn-delete {
            background: var(--bg-soft-pink);
            color: var(--error-color);
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 82, 82, 0.2);
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .btn-delete:hover {
            background: var(--error-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 82, 82, 0.3);
            transform: translateY(-2px);
        }

        /* --- 7. لیست خدمات (آیتم‌های اسلاید/سرویس) --- */
        .slide-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            transition: var(--transition-smooth);
        }

        .slide-item:last-child {
            margin-bottom: 0;
        }

        .slide-item:hover {
            background: rgba(255, 255, 255, 0.65);
            transform: scale(1.01);
        }

        .slide-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .slide-item strong {
            color: var(--contrast-color);
            font-size: 1rem;
        }

        .slide-item small {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .btn-edit {
            background: rgba(119, 221, 119, .16);
            color: var(--primary-hover);
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 15px;
            font-weight: 600;
            font-size: .85rem;
            border: 1px solid rgba(119, 221, 119, .3);
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .btn-edit:hover {
            background: var(--primary-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(119, 221, 119, .3);
            transform: translateY(-2px);
        }

        /* --- 8. انیمیشن --- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="admin-container">

    <h2>پنل مدیریت خدمات</h2>

    <!-- فرم افزودن -->
    <div class="glass-card">
        <h3>افزودن خدمت جدید</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="name" placeholder="نام خدمت" required>
            <textarea name="description" placeholder="توضیحات کوتاه"></textarea>
            <input type="text" name="price" placeholder="قیمت">
            <select name="category" placeholder="کتگوری">
                <option value="میکاپ">میکاپ</option>
                <option value="ناخن">ناخن</option>
                <option value="کوپ">کوپ</option>
                <option value="رنگ و لایت">رنگ و لایت</option>
                <option value="براشینگ">براشینگ</option>
                <option value="کراتین">کراتین</option>
                <option value="اصلاح ابرو">اصلاح ابرو</option>
                <option value="اصلاح صورت">اصلاح صورت</option>
                <option value="آرایش دایم">آرایش دایم</option>
                <option value="مژه">مژه</option>
                <option value="پوست">پوست</option>
                <option value="اکستنشن مو">اکستنشن مو</option>
                <option value="پدیکور">پدیکور</option>
                <option value="سرویس عروس">سرویس عروس</option>
            </select>
            <input type="file" name="image" accept="image/*" required>
            <button type="submit" name="submit_service" class="btn-add">ذخیره خدمت</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- لیست خدمات -->
    <div class="glass-card">
        <h3>لیست خدمات موجود</h3>
        <?php
        $services = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll();
        foreach ($services as $row):
        ?>
            <div class="slide-item">
                <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" class="slide-img">
                <div style="flex-grow:1;margin-right:15px;">
                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                    <small><?php echo htmlspecialchars($row['price']); ?></small>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <a href="edit-service.php?id=<?php echo (int)$row['id']; ?>" class="btn-edit">ویرایش</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('حذف شود؟')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="service_id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" name="delete_service" class="btn-delete">حذف</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>