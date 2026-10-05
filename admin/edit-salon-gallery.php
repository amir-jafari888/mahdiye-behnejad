<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: admin-salon-gallery.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id,image_path,title,description,tag,is_active,sort_order,created_at
    FROM salon_gallery
    WHERE id=?
    LIMIT 1
");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: admin-salon-gallery.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_salon_gallery'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    $sortOrder = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imagePath = $item['image_path'];

    if ($title === '') {
        $error = 'عنوان تصویر الزامی است.';
    }

    if ($sortOrder === false || $sortOrder === null || $sortOrder < 0) {
        $sortOrder = 0;
    }

    if ($error === '' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_secure_image(
            $_FILES['image_file'],
            __DIR__ . '/../uploads/salon-gallery/',
            'salon_gallery_'
        );

        if ($upload['success']) {
            if (!empty($item['image_path']) && !preg_match('#^(?:https?:)?//#i', $item['image_path'])) {
                $oldFile = __DIR__ . '/../' . ltrim($item['image_path'], '/');

                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $imagePath = 'uploads/salon-gallery/' . $upload['file_name'];
        } else {
            $error = $upload['error'];
        }
    }

    if ($error === '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE salon_gallery
                SET image_path=?,title=?,description=?,tag=?,is_active=?,sort_order=?
                WHERE id=?
            ");

            $stmt->execute([
                $imagePath,
                $title,
                $description,
                $tag,
                $isActive,
                $sortOrder,
                $id
            ]);

            header('Location: admin-salon-gallery.php?msg=edited');
            exit;
        } catch (PDOException $e) {
            error_log('Salon gallery update failed: ' . $e->getMessage());
            $error = 'ذخیره تغییرات با خطا مواجه شد.';
        }
    }

    $item['title'] = $title;
    $item['description'] = $description;
    $item['tag'] = $tag;
    $item['is_active'] = $isActive;
    $item['sort_order'] = $sortOrder;
    $item['image_path'] = $imagePath;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<style>
    .salon-gallery-edit-page {
        min-height: 100vh;
        padding: 110px 28px 50px;
        background: radial-gradient(circle at 10% 10%, rgba(119, 221, 119, .16), transparent 28%), radial-gradient(circle at 90% 20%, rgba(234, 219, 200, .55), transparent 30%), linear-gradient(135deg, #f8f5f0, #eee8df);
        color: var(--contrast-color)
    }

    .salon-gallery-edit-header,
    .salon-gallery-edit-card,
    .salon-gallery-current-card {
        max-width: 1000px;
        margin: 0 auto 25px
    }

    .salon-gallery-edit-header {
        padding: 30px 34px;
        border: 1px solid rgba(255, 255, 255, .7);
        border-radius: 28px;
        background: rgba(255, 255, 255, .38);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 18px 45px rgba(93, 64, 55, .08)
    }

    .salon-gallery-edit-eyebrow {
        display: block;
        margin-bottom: 8px;
        color: rgba(93, 64, 55, .42);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 3px;
        direction: ltr
    }

    .salon-gallery-edit-header h1 {
        margin: 0;
        color: var(--contrast-color);
        font-size: clamp(1.7rem, 3vw, 2.25rem);
        font-weight: 800
    }

    .salon-gallery-edit-header p {
        margin: 10px 0 0;
        color: var(--text-muted);
        font-size: .95rem
    }

    .salon-gallery-edit-alert {
        max-width: 1000px;
        margin: 0 auto 20px;
        padding: 15px 20px;
        border-radius: 18px;
        background: rgba(255, 82, 82, .1);
        border: 1px solid rgba(255, 82, 82, .22);
        color: #d32f2f;
        font-size: .9rem;
        font-weight: 600
    }

    .salon-gallery-edit-card,
    .salon-gallery-current-card {
        padding: 30px;
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 30px;
        background: rgba(255, 255, 255, .42);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 20px 55px rgba(93, 64, 55, .08)
    }

    .salon-gallery-edit-card-title {
        display: flex;
        align-items: center;
        margin-bottom: 25px
    }

    .salon-gallery-edit-card-title>div {
        display: flex;
        align-items: center;
        gap: 15px
    }

    .salon-gallery-edit-card-title>div>span {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(119, 221, 119, .14);
        border: 1px solid rgba(119, 221, 119, .22);
        font-size: 21px
    }

    .salon-gallery-edit-card-title h2 {
        margin: 0;
        color: var(--contrast-color);
        font-size: 1.15rem;
        font-weight: 800
    }

    .salon-gallery-edit-card-title p {
        margin: 6px 0 0;
        color: var(--text-muted);
        font-size: .84rem
    }

    .salon-gallery-edit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px
    }

    .salon-gallery-edit-field {
        display: flex;
        flex-direction: column;
        gap: 8px
    }

    .salon-gallery-edit-field.full {
        grid-column: 1/-1
    }

    .salon-gallery-edit-field label {
        color: var(--contrast-color);
        font-size: .88rem;
        font-weight: 700
    }

    .salon-gallery-edit-field input,
    .salon-gallery-edit-field textarea {
        width: 100%;
        border: 1px solid rgba(93, 64, 55, .12);
        border-radius: 17px;
        padding: 13px 16px;
        background: rgba(255, 255, 255, .55);
        color: var(--contrast-color);
        font-family: inherit;
        font-size: .9rem;
        outline: none;
        transition: border-color .3s ease, box-shadow .3s ease, background .3s ease
    }

    .salon-gallery-edit-field textarea {
        min-height: 120px;
        resize: vertical
    }

    .salon-gallery-edit-field input:focus,
    .salon-gallery-edit-field textarea:focus {
        border-color: rgba(119, 221, 119, .65);
        background: rgba(255, 255, 255, .78);
        box-shadow: 0 0 0 4px rgba(119, 221, 119, .1)
    }

    .salon-gallery-edit-field input[type=file] {
        padding: 10px;
        cursor: pointer
    }

    .salon-gallery-edit-field small {
        color: var(--text-muted);
        font-size: .72rem
    }

    .salon-gallery-edit-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        width: max-content;
        margin-top: 20px;
        padding: 11px 15px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .4);
        color: var(--contrast-color);
        font-size: .86rem;
        font-weight: 600;
        cursor: pointer
    }

    .salon-gallery-edit-checkbox input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color)
    }

    .salon-gallery-edit-actions {
        display: flex;
        gap: 10px;
        margin-top: 25px
    }

    .salon-gallery-edit-submit,
    .salon-gallery-edit-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 50px;
        padding: 10px 24px;
        border-radius: 16px;
        font-family: inherit;
        font-size: .9rem;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease, box-shadow .25s ease
    }

    .salon-gallery-edit-submit {
        flex: 1;
        border: 0;
        background: var(--primary-color);
        color: #fff;
        box-shadow: 0 10px 25px rgba(119, 221, 119, .22)
    }

    .salon-gallery-edit-submit:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 15px 32px rgba(56, 142, 60, .2)
    }

    .salon-gallery-edit-back {
        min-width: 130px;
        border: 1px solid rgba(93, 64, 55, .12);
        background: rgba(255, 255, 255, .5);
        color: var(--contrast-color)
    }

    .salon-gallery-edit-back:hover {
        background: rgba(255, 255, 255, .8);
        transform: translateY(-2px)
    }

    .salon-gallery-current-image {
        overflow: hidden;
        border-radius: 22px;
        border: 1px solid rgba(255, 255, 255, .7);
        background: rgba(255, 255, 255, .3)
    }

    .salon-gallery-current-image img {
        display: block;
        width: 100%;
        max-height: 500px;
        object-fit: contain
    }

    @media(max-width:700px) {
        .salon-gallery-edit-page {
            padding: 95px 18px 35px
        }

        .salon-gallery-edit-header,
        .salon-gallery-edit-card,
        .salon-gallery-current-card {
            padding: 23px 20px;
            border-radius: 24px
        }

        .salon-gallery-edit-grid {
            grid-template-columns: 1fr
        }

        .salon-gallery-edit-field.full {
            grid-column: auto
        }
    }

    @media(max-width:480px) {
        .salon-gallery-edit-page {
            padding: 88px 12px 25px
        }

        .salon-gallery-edit-header,
        .salon-gallery-edit-card,
        .salon-gallery-current-card {
            padding: 20px 16px;
            border-radius: 21px
        }

        .salon-gallery-edit-header h1 {
            font-size: 1.45rem
        }

        .salon-gallery-edit-actions {
            flex-direction: column
        }

        .salon-gallery-edit-submit,
        .salon-gallery-edit-back {
            width: 100%
        }
    }

    @media(prefers-reduced-motion:reduce) {

        .salon-gallery-edit-field input,
        .salon-gallery-edit-field textarea,
        .salon-gallery-edit-submit,
        .salon-gallery-edit-back {
            transition: none
        }
    }
</style>

<main class="salon-gallery-edit-page">
    <div class="salon-gallery-edit-header">
        <span class="salon-gallery-edit-eyebrow">EDIT SALON GALLERY</span>
        <h1>ویرایش تصویر سالن</h1>
        <p>اطلاعات و وضعیت تصویر انتخاب‌شده را مدیریت کنید.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="salon-gallery-edit-alert error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="salon-gallery-edit-card">
        <div class="salon-gallery-edit-card-title">
            <div>
                <span>✏️</span>
                <div>
                    <h2>ویرایش <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>عنوان، توضیحات، تگ، تصویر و وضعیت نمایش را تغییر دهید.</p>
                </div>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="salon-gallery-edit-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="salon-gallery-edit-grid">
                <div class="salon-gallery-edit-field">
                    <label for="title">عنوان تصویر</label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                        required>
                </div>

                <div class="salon-gallery-edit-field">
                    <label for="tag">تگ</label>
                    <input
                        id="tag"
                        type="text"
                        name="tag"
                        value="<?= htmlspecialchars($item['tag'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="مثلاً فضای سالن">
                </div>

                <div class="salon-gallery-edit-field full">
                    <label for="description">توضیحات</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="توضیحات کوتاه درباره تصویر..."><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="salon-gallery-edit-field">
                    <label for="sort_order">ترتیب نمایش</label>
                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        value="<?= (int)$item['sort_order'] ?>"
                        min="0">
                </div>

                <div class="salon-gallery-edit-field">
                    <label for="image_file">تصویر جدید</label>
                    <input
                        id="image_file"
                        type="file"
                        name="image_file"
                        accept=".jpg,.jpeg,.png,.webp">
                    <small>در صورت عدم انتخاب، تصویر فعلی حفظ می‌شود.</small>
                </div>
            </div>

            <label class="salon-gallery-edit-checkbox">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?= (int)$item['is_active'] === 1 ? 'checked' : '' ?>>
                <span>این تصویر در سایت نمایش داده شود</span>
            </label>

            <div class="salon-gallery-edit-actions">
                <button type="submit" name="update_salon_gallery" class="salon-gallery-edit-submit">
                    💾 ذخیره تغییرات
                </button>

                <a href="admin-salon-gallery.php" class="salon-gallery-edit-back">
                    ↩ بازگشت
                </a>
            </div>
        </form>
    </section>

    <section class="salon-gallery-current-card">
        <div class="salon-gallery-edit-card-title">
            <div>
                <span>🖼️</span>
                <div>
                    <h2>تصویر فعلی</h2>
                    <p>تصویری که در حال حاضر برای این آیتم ذخیره شده است.</p>
                </div>
            </div>
        </div>

        <div class="salon-gallery-current-image">
            <img
                src="../<?= htmlspecialchars(ltrim($item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </section>
</main>