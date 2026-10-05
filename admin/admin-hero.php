<?php

require_once __DIR__ . '/includes/auth.php';
// ۱. ابتدا نشست را شروع کنید
// session_start();

// ۲. پردازش‌های سمت سرور (حذف و افزودن) را اینجا انجام دهید
// چون هنوز هیچ خروجی HTML ای تولید نشده است، header به درستی کار می‌کند.
require_once '../partials/db.php'; // دیتابیس اول لود شود
require_once __DIR__ . '/includes/upload-security.php';
// منطق حذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hero_slide'])) {
    $id = filter_input(INPUT_POST, 'hero_id', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        $slide = $stmt->fetch();

        if ($slide) {
            $imagePath = __DIR__ . '/../' . ltrim($slide['image_path'], '/');

            if (!empty($slide['image_path']) && is_file($imagePath)) {
                unlink($imagePath);
            }

            $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['msg'] = "اسلاید با موفقیت حذف شد.";
        }
    }

    header("Location: admin-hero.php");
    exit();
}
// منطق افزودن
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $upload = upload_secure_image(
        $_FILES['image'],
        __DIR__ . '/../uploads/hero/',
        'hero_'
    );

    if ($upload['success']) {
        $image_path = 'uploads/hero/' . $upload['file_name'];

        $stmt = $pdo->prepare(
            "INSERT INTO hero_slides (title, description, image_path)
         VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $title,
            $description,
            $image_path
        ]);

        $_SESSION['msg'] = "اسلاید با موفقیت اضافه شد!";
    } else {
        $_SESSION['msg'] = $upload['error'];
    }
    header("Location: admin-hero.php");
    exit();
}

// ۳. حالا که پردازش‌ها تمام شد و هنوز هیچ چیزی چاپ نکردیم،
// می‌توانید هدرها و بقیه فایل‌ها را لود کنید
require_once __DIR__ . '/includes/header-admin.php';
require_once __DIR__ . '/includes/auth.php';

// مدیریت پیام (چون بعد از header است، مشکلی ندارد)
$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// ۴. دریافت لیست اسلایدها برای نمایش در HTML
$stmt = $pdo->query("SELECT * FROM hero_slides ORDER BY id DESC");
$slides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت اسلایدر هیرو | Mahdiye Beauty</title>
    <!-- <link rel="stylesheet" href="css/admin_style.css">  -->
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

        .admin-container h1 {
            color: var(--contrast-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* --- 3. پیام‌های سیستم (Alert) --- */
        .alert {
            padding: 15px 20px !important;
            background: rgba(119, 221, 119, 0.25) !important;
            color: #2e7d32 !important;
            border: 1px solid rgba(119, 221, 119, 0.4);
            border-radius: 16px !important;
            backdrop-filter: blur(10px);
            font-weight: 600;
            margin-bottom: 0 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        /* --- 4. کارت‌های Glassmorphism (.glass-card) --- */
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
        /* --- 5. فرم‌ها و ورودی‌ها --- */
        .glass-card form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .glass-card input[type="text"],
        .glass-card textarea {
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

        /* تغییر جدید: فقط تغییر ارتفاع مجاز است */
        .glass-card textarea {
            resize: vertical;
            min-height: 80px;
            max-height: 200px;
        }

        .glass-card input[type="text"]:focus,
        .glass-card textarea:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(119, 221, 119, 0.2);
        }


        .glass-card label {
            color: var(--contrast-color);
            font-weight: 500;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .glass-card input[type="file"] {
            padding: 10px;
            background: rgba(255, 255, 255, 0.4);
            border: 1px dashed rgba(93, 64, 55, 0.3);
            border-radius: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .glass-card input[type="file"]:hover {
            background: rgba(255, 255, 255, 0.7);
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
        }

        .btn-delete:hover {
            background: var(--error-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 82, 82, 0.3);
            transform: translateY(-2px);
        }

        /* --- 7. آیتم‌های لیست اسلایدها --- */
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
            border-radius: 12px !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
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

<body>

    <div class="admin-container">
        <h1>✨ مدیریت اسلایدر هیرو</h1>

        <?php if ($message): ?>
            <div class="alert" style="padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- فرم افزودن اسلاید -->
        <div class="glass-card">
            <h3>➕ افزودن اسلاید جدید</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="title" placeholder="عنوان اسلاید" required>
                <textarea name="description" placeholder="توضیحات اسلاید..." rows="3"></textarea>
                <label>انتخاب تصویر اسلاید:</label>
                <input type="file" name="image" accept="image/*" required>
                <button type="submit" class="btn-add">ذخیره اسلاید</button>
            </form>
        </div>

        <!-- لیست اسلایدها -->
        <div class="glass-card">
            <h3>🖼️ اسلایدهای فعال</h3>
            <?php if (empty($slides)): ?>
                <p>هیچ اسلایدی یافت نشد.</p>
            <?php else: ?>
                <?php foreach ($slides as $slide): ?>
                    <div class="slide-item">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo htmlspecialchars('../' . ltrim($slide['image_path'], '/')); ?>" class="slide-img" style="width: 80px; height: 50px; object-fit: cover;">
                            <div>
                                <strong><?php echo htmlspecialchars($slide['title']); ?></strong>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <a href="edit-hero.php?id=<?php echo (int)$slide['id']; ?>" class="btn-edit">ویرایش</a>
                            <form method="POST" onsubmit="return confirm('آیا مطمئن هستید؟')" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="hero_id" value="<?= (int)$slide['id'] ?>">
                                <button type="submit" name="delete_hero_slide" class="btn-delete">حذف</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>