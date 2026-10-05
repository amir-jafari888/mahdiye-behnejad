<?php

require_once __DIR__ . '/includes/auth.php';
// ۱. ابتدا سشن و دیتابیس (بدون هیچ خروجی HTML)
// if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
// -------------------------------------------------------------------------
// ۲. بخش پردازش عملیات (POST) - باید قبل از هدر باشد
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- افزودن آیتم جدید ---
    if (isset($_POST['add_gallery_item'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tag = trim($_POST['tag'] ?? 'محبوب');
        $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
        if (!$serviceId) {
            $error_msg = 'لطفاً یک سرویس را انتخاب کنید.';
        }
        $image_path = '';
        if (!$serviceId) {
            $error_msg = 'لطفاً سرویس مربوط به این نمونه‌کار را انتخاب کنید.';
        }

        if (isset($_FILES['image_file'])) {
            $upload = upload_secure_image(
                $_FILES['image_file'],
                __DIR__ . '/../uploads/gallery/',
                'gallery_'
            );

            if ($upload['success']) {
                $image_path = 'uploads/gallery/' . $upload['file_name'];

                if (!empty($image_path) && !empty($title) && empty($error_msg)) {
                    try {
                        $sql = "INSERT INTO gallery (service_id, image_path, title, description, tag)
                        VALUES (:service_id, :image_path, :title, :description, :tag)";

                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            ':service_id'  => $serviceId,
                            ':image_path'  => $image_path,
                            ':title'       => $title,
                            ':description' => $description,
                            ':tag'         => $tag
                        ]);

                        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=added");
                        exit;
                    } catch (PDOException $e) {
                        error_log('Gallery insert failed: ' . $e->getMessage());
                        $error_msg = 'ثبت تصویر با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
                    }
                }
            } else {
                $error_msg = $upload['error'];
            }
        }

        // --- حذف آیتم ---
        if (isset($_POST['delete_gallery_item'])) {
            $gallery_id = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);
            if ($gallery_id) {
                $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = :id");
                $stmt->execute([':id' => $gallery_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item && !empty($item['image_path']) && file_exists('../' . $item['image_path'])) {
                    unlink('../' . $item['image_path']);
                }

                $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
                $stmt->execute([':id' => $gallery_id]);

                header("Location: " . $_SERVER['PHP_SELF'] . "?msg=deleted");
                exit;
            }
        }
    }
}

$services = $pdo->query("
    SELECT id, name, category
    FROM services
    ORDER BY category, name
")->fetchAll(PDO::FETCH_ASSOC);

// ۳. گرفتن داده‌ها برای نمایش
$galleryItems = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// ۴. حالا که همه پردازش‌ها تمام شد و اگر ریدایرکت نشدیم، هدر را لود می‌کنیم
require_once __DIR__ . '/includes/header-admin.php';
require_once __DIR__ . '/includes/auth.php';

// آماده‌سازی پیام‌ها برای نمایش در HTML
$message = $_GET['msg'] ?? '';
$error = $error_msg ?? '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت گالری - Mahdiye Beauty</title>
    <!-- مسیر فایل CSS استایل پنل ادمین خود را اینجا وارد کنید -->
    <!-- <link rel="stylesheet" href="admin_style.css"> -->
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

        .gallery-service-field {
            position: relative
        }

        .gallery-service-field label {
            display: block;
            margin-bottom: 8px;
            color: var(--contrast-color);
            font-size: .9rem;
            font-weight: 600
        }

        .gallery-service-select {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 54px;
            border: 1px solid rgba(93, 64, 55, .13);
            border-radius: 17px;
            background: linear-gradient(135deg, rgba(255, 255, 255, .72), rgba(234, 219, 200, .28));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .75), 0 6px 18px rgba(93, 64, 55, .06);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
            transition: .35s ease
        }

        .gallery-service-select:hover {
            border-color: rgba(119, 221, 119, .45);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8), 0 8px 22px rgba(93, 64, 55, .08)
        }

        .gallery-service-select:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(119, 221, 119, .14), inset 0 1px 0 rgba(255, 255, 255, .8)
        }

        .gallery-service-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin-right: 6px;
            border-radius: 13px;
            background: rgba(119, 221, 119, .14);
            color: var(--contrast-color);
            font-size: 18px;
            flex: 0 0 44px
        }

        .gallery-service-select select {
            width: 100%;
            height: 54px !important;
            margin: 0 !important;
            padding: 0 42px 0 10px !important;
            border: 0 !important;
            outline: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
            color: var(--contrast-color);
            font: inherit;
            font-size: .92rem;
            font-weight: 600;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none
        }

        .gallery-service-select select option {
            background: #fff;
            color: var(--contrast-color);
            font-family: inherit
        }

        .gallery-service-arrow {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-55%);
            color: var(--primary-color);
            font-size: 22px;
            pointer-events: none;
            transition: .3s ease
        }

        .gallery-service-select:focus-within .gallery-service-arrow {
            transform: translateY(-45%) rotate(180deg)
        }

        .gallery-service-field small {
            display: block;
            margin-top: 7px;
            color: var(--text-muted);
            font-size: .74rem
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

        /* --- 7. لیست آیتم‌ها (سرویس / گالری / اسلاید) --- */
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

    <h2>پنل مدیریت گالری</h2>

    <!-- فرم افزودن آیتم جدید -->
    <div class="glass-card">
        <h3>افزودن آیتم جدید به گالری</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="title" placeholder="عنوان تصویر" required>
            <div class="gallery-service-field">
                <label>سرویس مرتبط</label>
                <div class="gallery-service-select">
                    <span class="gallery-service-icon">✦</span>
                    <select name="service_id" required>
                        <option value="">انتخاب سرویس مرتبط</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int)$service['id'] ?>">
                                <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="gallery-service-arrow">⌄</span>
                </div>
                <small>این تصویر در صفحه جزئیات سرویس انتخاب‌شده نمایش داده می‌شود.</small>
            </div>
            <textarea name="description" placeholder="توضیحات کوتاه"></textarea>
            <input type="text" name="tag" value="محبوب" placeholder="تگ (مثلاً: محبوب)">
            <input type="file" name="image_file" accept="image/*" required>

            <button type="submit" name="add_gallery_item" class="btn-add">ثبت و آپلود</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- لیست تصاویر گالری -->
    <div class="glass-card">
        <h3>لیست تصاویر گالری</h3>
        <?php foreach ($galleryItems as $item): ?>
            <div class="slide-item">
                <!-- تصویر -->
                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" class="slide-img">

                <!-- اطلاعات -->
                <div style="flex-grow: 1; margin-right: 15px;">
                    <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($item['tag']); ?></small>
                </div>

                <!-- دکمه حذف (فرم داخل آیتم) -->
                <div style="display:flex;align-items:center;gap:8px;">
                    <a href="edit-gallery.php?id=<?php echo (int)$item['id']; ?>" class="btn-edit">ویرایش</a>
                    <form method="POST" onsubmit="return confirm('آیا مطمئنید؟');" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="gallery_id" value="<?php echo (int)$item['id']; ?>">
                        <button type="submit" name="delete_gallery_item" class="btn-delete">حذف</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>