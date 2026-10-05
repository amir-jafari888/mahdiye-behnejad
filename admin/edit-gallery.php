<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
$services = $pdo->query("
    SELECT id, name, category
    FROM services
    ORDER BY category, name
")->fetchAll(PDO::FETCH_ASSOC);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: admin-gallery.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM gallery WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: admin-gallery.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_gallery'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tag = trim($_POST['tag'] ?? 'محبوب');
    $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $image_path = $item['image_path'];

    if ($title === '') {
        $error = 'عنوان تصویر الزامی است.';
    } else {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = upload_secure_image(
                $_FILES['image_file'],
                __DIR__ . '/../uploads/gallery/',
                'gallery_'
            );

            if ($upload['success']) {
                if (!empty($item['image_path']) && !preg_match('#^(?:https?:)?//#i', $item['image_path'])) {
                    $oldFile = '../' . ltrim($item['image_path'], '/');

                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $image_path = 'uploads/gallery/' . $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        }
        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE gallery SET service_id=?,image_path=?,title=?,description=?,tag=? WHERE id=?");
            $stmt->execute([$serviceId, $image_path, $title, $description, $tag, $id]);

            header('Location: admin-gallery.php?msg=edited');
            exit;
        }
    }

    $item['title'] = $title;
    $item['description'] = $description;
    $item['tag'] = $tag;
    $item['image_path'] = $image_path;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<main class="gallery-edit-page">
    <div class="gallery-edit-header">
        <div>
            <span class="gallery-edit-eyebrow">EDIT GALLERY</span>
            <h1>ویرایش تصویر گالری</h1>
            <p>اطلاعات آیتم انتخاب‌شده را ویرایش کنید.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="gallery-alert error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <section class="gallery-form-card">
        <div class="gallery-card-title">
            <div>
                <h2>ویرایش <?php echo htmlspecialchars($item['title']); ?></h2>
                <p>عنوان، توضیحات، تگ یا تصویر را تغییر دهید.</p>
            </div>
            <span>✏️</span>
        </div>

        <form method="POST" enctype="multipart/form-data" class="gallery-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="gallery-form-grid">
                <div class="gallery-field">
                    <label>عنوان تصویر</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>

                <div class="gallery-field gallery-service-field">
                    <label>سرویس مرتبط</label>
                    <div class="gallery-service-select">
                        <span class="gallery-service-icon">✦</span>
                        <select name="service_id" required>
                            <option value="">انتخاب سرویس مرتبط</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int)$service['id'] ?>" <?= ((int)$item['service_id'] === (int)$service['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="gallery-service-arrow">⌄</span>
                    </div>
                    <small>این تصویر در صفحه جزئیات سرویس انتخاب‌شده نمایش داده می‌شود.</small>
                </div>

                <div class="gallery-field">
                    <label>تگ</label>
                    <input type="text" name="tag" value="<?php echo htmlspecialchars($item['tag']); ?>" placeholder="مثلاً: محبوب">
                </div>

                <div class="gallery-field gallery-field-full">
                    <label>توضیحات</label>
                    <textarea name="description" placeholder="توضیحات کوتاه درباره تصویر..."><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                </div>

                <div class="gallery-field gallery-field-full">
                    <label>تصویر جدید</label>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
                    <small>اگر تصویر جدید انتخاب نکنید، تصویر فعلی حفظ می‌شود.</small>
                </div>
            </div>

            <div class="gallery-admin-actions">
                <button type="submit" name="update_gallery" class="gallery-submit">
                    💾 ذخیره تغییرات
                </button>

                <a href="admin-gallery.php" class="gallery-back">
                    ↩️ بازگشت
                </a>
            </div>
        </form>
    </section>

    <section class="gallery-current-card">
        <div class="gallery-card-title">
            <div>
                <h2>تصویر فعلی</h2>
                <p>تصویری که در حال حاضر برای این آیتم ذخیره شده است.</p>
            </div>
        </div>

        <div class="gallery-current-image">
            <img src="<?php echo htmlspecialchars('../' . ltrim($item['image_path'], '/')); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
        </div>
    </section>
</main>

<style>
    .gallery-edit-page {
        max-width: 1000px;
        margin: 0 auto;
        padding: 120px 20px 50px;
        animation: galleryFadeIn .6s cubic-bezier(.25, 1, .5, 1) forwards;
    }

    .gallery-edit-header {
        margin-bottom: 25px;
    }

    .gallery-edit-eyebrow {
        display: inline-block;
        color: #388e3c;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: 2px;
        margin-bottom: 8px;
    }

    .gallery-edit-header h1 {
        color: #5d4037;
        font-size: 1.8rem;
        margin: 0 0 8px;
    }

    .gallery-edit-header p {
        color: #777;
        margin: 0;
    }

    .gallery-alert {
        padding: 15px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .gallery-alert.error {
        background: rgba(255, 82, 82, .12);
        color: #d32f2f;
        border: 1px solid rgba(255, 82, 82, .2);
    }

    .gallery-form-card,
    .gallery-current-card {
        background: rgba(255, 255, 255, .45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 28px;
        padding: 30px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 25px -5px rgba(0, 0, 0, .05);
        margin-bottom: 25px;
    }

    .gallery-card-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .gallery-card-title h2 {
        color: #5d4037;
        margin: 0 0 6px;
        font-size: 1.3rem;
    }

    .gallery-card-title p {
        color: #777;
        margin: 0;
        font-size: .9rem;
    }

    .gallery-card-title>span {
        width: 45px;
        height: 45px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: rgba(119, 221, 119, .16);
        font-size: 20px;
    }

    .gallery-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .gallery-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .gallery-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .gallery-field-full {
        grid-column: 1/-1;
    }

    .gallery-field label {
        color: #5d4037;
        font-weight: 600;
        font-size: .9rem;
    }

    .gallery-field input,
    .gallery-field textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 14px 18px;
        background: rgba(255, 255, 255, .6);
        border: 1px solid rgba(255, 255, 255, .8);
        border-radius: 16px;
        color: #444;
        font-family: inherit;
        font-size: .95rem;
        outline: none;
        transition: all .4s cubic-bezier(.4, 0, .2, 1);
    }

    .gallery-field textarea {
        min-height: 110px;
        max-height: 220px;
        resize: vertical;
    }

    .gallery-field input:focus,
    .gallery-field textarea:focus {
        background: rgba(255, 255, 255, .95);
        border-color: #77dd77;
        box-shadow: 0 0 0 4px rgba(119, 221, 119, .2);
    }

    .gallery-field input[type=file] {
        padding: 10px;
        cursor: pointer;
        border: 1px dashed rgba(93, 64, 55, .3);
    }

    .gallery-field small {
        color: #777;
        font-size: .78rem;
    }

    .gallery-admin-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .gallery-submit,
    .gallery-back {
        border: none;
        text-decoration: none;
        padding: 13px 24px;
        border-radius: 17px;
        font-family: inherit;
        font-weight: 600;
        cursor: pointer;
        transition: all .4s cubic-bezier(.4, 0, .2, 1);
    }

    .gallery-submit {
        background: #77dd77;
        color: #fff;
        box-shadow: 0 4px 15px rgba(119, 221, 119, .3);
    }

    .gallery-submit:hover {
        background: #388e3c;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(56, 142, 60, .3);
    }

    .gallery-back {
        background: rgba(255, 255, 255, .5);
        color: #5d4037;
        border: 1px solid rgba(255, 255, 255, .7);
    }

    .gallery-back:hover {
        background: rgba(255, 255, 255, .8);
        transform: translateY(-2px);
    }

    .gallery-current-image {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .gallery-current-image img {
        display: block;
        width: 100%;
        max-width: 700px;
        max-height: 500px;
        object-fit: contain;
        border-radius: 22px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
    }

    .gallery-service-field {
        position: relative
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
        height: 54px;
        padding: 0 42px 0 10px;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--contrast-color);
        font: inherit;
        font-size: .92rem;
        font-weight: 600;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none
    }

    .gallery-service-select select option {
        background: var(--accent-color);
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

    @keyframes galleryFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    @media(max-width:700px) {
        .gallery-edit-page {
            padding: 100px 15px 35px;
        }

        .gallery-form-card,
        .gallery-current-card {
            padding: 20px;
            border-radius: 22px;
        }

        .gallery-form-grid {
            grid-template-columns: 1fr;
        }

        .gallery-field-full {
            grid-column: auto;
        }

        .gallery-admin-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .gallery-submit,
        .gallery-back {
            text-align: center;
        }
    }
</style>

<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>