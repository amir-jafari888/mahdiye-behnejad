<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $csrfToken)) {
        $error = 'درخواست نامعتبر است.';
    } elseif (isset($_POST['add_salon_gallery'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $sortOrder = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($sortOrder === false || $sortOrder === null) {
            $sortOrder = 0;
        }

        if ($title === '') {
            $error = 'عنوان تصویر الزامی است.';
        } elseif (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'لطفاً یک تصویر انتخاب کنید.';
        } else {
            $upload = upload_secure_image(
                $_FILES['image_file'],
                __DIR__ . '/../uploads/salon-gallery/',
                'salon_gallery_'
            );

            if ($upload['success']) {
                $imagePath = 'uploads/salon-gallery/' . $upload['file_name'];

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO salon_gallery
                        (image_path,title,description,tag,is_active,sort_order)
                        VALUES (?,?,?,?,?,?)
                    ");
                    $stmt->execute([
                        $imagePath,
                        $title,
                        $description,
                        $tag,
                        $isActive,
                        $sortOrder
                    ]);

                    header('Location: admin-salon-gallery.php?msg=added');
                    exit;
                } catch (PDOException $e) {
                    if (is_file(__DIR__ . '/../' . $imagePath)) {
                        unlink(__DIR__ . '/../' . $imagePath);
                    }
                    error_log('Salon gallery insert failed: ' . $e->getMessage());
                    $error = 'ثبت تصویر با خطا مواجه شد.';
                }
            } else {
                $error = $upload['error'];
            }
        }
    } elseif (isset($_POST['toggle_salon_gallery'])) {
        $id = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE salon_gallery
                SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END
                WHERE id=?
            ");
            $stmt->execute([$id]);

            header('Location: admin-salon-gallery.php?msg=updated');
            exit;
        }
    } elseif (isset($_POST['delete_salon_gallery'])) {
        $id = filter_input(INPUT_POST, 'gallery_id', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare("
                SELECT image_path
                FROM salon_gallery
                WHERE id=?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                if (!empty($item['image_path']) && !preg_match('#^(?:https?:)?//#i', $item['image_path'])) {
                    $file = __DIR__ . '/../' . ltrim($item['image_path'], '/');

                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM salon_gallery WHERE id=?");
                $stmt->execute([$id]);
            }

            header('Location: admin-salon-gallery.php?msg=deleted');
            exit;
        }
    }
}

$salonGallery = $pdo->query("
    SELECT id,image_path,title,description,tag,is_active,sort_order,created_at
    FROM salon_gallery
    ORDER BY sort_order ASC,id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';

if ($msg === 'added') {
    $message = 'تصویر سالن با موفقیت اضافه شد.';
} elseif ($msg === 'updated') {
    $message = 'وضعیت تصویر با موفقیت تغییر کرد.';
} elseif ($msg === 'deleted') {
    $message = 'تصویر با موفقیت حذف شد.';
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<style>
    .salon-gallery-admin-page {
        min-height: 100vh;
        padding: 110px 28px 50px;
        background:
            radial-gradient(circle at 10% 10%, rgba(119, 221, 119, .16), transparent 28%),
            radial-gradient(circle at 90% 20%, rgba(234, 219, 200, .55), transparent 30%),
            linear-gradient(135deg, #f8f5f0, #eee8df);
        color: var(--contrast-color);
    }

    .admin-page-header {
        max-width: 1250px;
        margin: 0 auto 28px;
        padding: 30px 34px;
        border: 1px solid rgba(255, 255, 255, .7);
        border-radius: 28px;
        background: rgba(255, 255, 255, .38);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 18px 45px rgba(93, 64, 55, .08);
    }

    .admin-page-eyebrow {
        display: block;
        margin-bottom: 8px;
        color: rgba(93, 64, 55, .42);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 3px;
        direction: ltr;
    }

    .admin-page-header h1 {
        margin: 0;
        color: var(--contrast-color);
        font-size: clamp(1.7rem, 3vw, 2.35rem);
        font-weight: 800;
    }

    .admin-page-header p {
        margin: 10px 0 0;
        color: var(--text-muted);
        font-size: .95rem;
    }

    .admin-alert {
        max-width: 1250px;
        margin: 0 auto 22px;
        padding: 15px 20px;
        border-radius: 18px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        font-size: .9rem;
        font-weight: 600;
    }

    .admin-alert.success {
        background: rgba(119, 221, 119, .16);
        border: 1px solid rgba(119, 221, 119, .3);
        color: #388e3c;
    }

    .admin-alert.error {
        background: rgba(255, 82, 82, .1);
        border: 1px solid rgba(255, 82, 82, .22);
        color: #d32f2f;
    }

    .salon-gallery-form-card,
    .salon-gallery-list-card {
        max-width: 1250px;
        margin: 0 auto 28px;
        padding: 30px;
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 30px;
        background: rgba(255, 255, 255, .42);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 20px 55px rgba(93, 64, 55, .08);
    }

    .admin-card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 25px;
    }

    .admin-card-title h2 {
        margin: 0;
        color: var(--contrast-color);
        font-size: 1.25rem;
        font-weight: 800;
    }

    .admin-card-title p {
        margin: 7px 0 0;
        color: var(--text-muted);
        font-size: .88rem;
    }

    .admin-card-title>span {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(119, 221, 119, .14);
        border: 1px solid rgba(119, 221, 119, .22);
        font-size: 21px;
    }

    .salon-gallery-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .admin-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .admin-field-full {
        grid-column: 1/-1;
    }

    .admin-field label {
        color: var(--contrast-color);
        font-size: .88rem;
        font-weight: 700;
    }

    .admin-field input,
    .admin-field textarea {
        width: 100%;
        border: 1px solid rgba(93, 64, 55, .12);
        border-radius: 17px;
        padding: 13px 16px;
        background: rgba(255, 255, 255, .55);
        color: var(--contrast-color);
        font-family: inherit;
        font-size: .9rem;
        outline: none;
        transition: border-color .3s ease, box-shadow .3s ease, background .3s ease;
    }

    .admin-field textarea {
        min-height: 115px;
        resize: vertical;
    }

    .admin-field input:focus,
    .admin-field textarea:focus {
        border-color: rgba(119, 221, 119, .65);
        background: rgba(255, 255, 255, .78);
        box-shadow: 0 0 0 4px rgba(119, 221, 119, .1);
    }

    .admin-field input[type=file] {
        padding: 10px;
        cursor: pointer;
    }

    .admin-checkbox {
        grid-column: 1/-1;
        display: flex;
        align-items: center;
        gap: 10px;
        width: max-content;
        padding: 11px 15px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .4);
        color: var(--contrast-color);
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
    }

    .admin-checkbox input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
    }

    .admin-submit-btn {
        width: 100%;
        min-height: 52px;
        margin-top: 22px;
        border: 0;
        border-radius: 17px;
        background: var(--primary-color);
        color: #fff;
        font-family: inherit;
        font-size: .95rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 25px rgba(119, 221, 119, .22);
        transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
    }

    .admin-submit-btn:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 15px 32px rgba(56, 142, 60, .2);
    }

    .admin-submit-btn:active {
        transform: translateY(0) scale(.99);
    }

    .salon-gallery-admin-list {
        display: grid;
        gap: 15px;
    }

    .salon-gallery-admin-item {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) auto;
        align-items: center;
        gap: 20px;
        padding: 16px;
        border: 1px solid rgba(255, 255, 255, .68);
        border-radius: 23px;
        background: rgba(255, 255, 255, .34);
        transition: transform .3s ease, background .3s ease, box-shadow .3s ease;
    }

    .salon-gallery-admin-item:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, .52);
        box-shadow: 0 12px 30px rgba(93, 64, 55, .07);
    }

    .salon-gallery-admin-image {
        display: block;
        width: 120px;
        height: 90px;
        object-fit: cover;
        border-radius: 17px;
        border: 1px solid rgba(255, 255, 255, .7);
        box-shadow: 0 7px 18px rgba(93, 64, 55, .1);
    }

    .salon-gallery-admin-info {
        min-width: 0;
    }

    .salon-gallery-admin-info h3 {
        margin: 0 0 7px;
        color: var(--contrast-color);
        font-size: 1rem;
        font-weight: 800;
    }

    .salon-gallery-admin-info span {
        display: inline-flex;
        padding: 5px 10px;
        margin-bottom: 7px;
        border-radius: 999px;
        background: rgba(119, 221, 119, .13);
        border: 1px solid rgba(119, 221, 119, .2);
        color: #388e3c;
        font-size: .72rem;
        font-weight: 700;
    }

    .salon-gallery-admin-info p {
        margin: 3px 0 7px;
        color: var(--text-muted);
        font-size: .82rem;
        line-height: 1.8;
    }

    .salon-gallery-admin-info small {
        color: rgba(93, 64, 55, .5);
        font-size: .72rem;
    }

    .salon-gallery-admin-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .salon-gallery-admin-actions form {
        margin: 0;
    }

    .admin-action-btn {
        min-width: 72px;
        min-height: 38px;
        padding: 7px 13px;
        border-radius: 13px;
        border: 1px solid transparent;
        font-family: inherit;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease, box-shadow .25s ease;
    }

    .admin-action-btn:hover {
        transform: translateY(-2px);
    }

    .admin-action-btn.active {
        background: rgba(119, 221, 119, .14);
        border-color: rgba(119, 221, 119, .28);
        color: #388e3c;
    }

    .admin-action-btn.inactive {
        background: rgba(93, 64, 55, .08);
        border-color: rgba(93, 64, 55, .12);
        color: var(--text-muted);
    }

    .admin-action-btn.delete {
        background: rgba(255, 82, 82, .08);
        border-color: rgba(255, 82, 82, .18);
        color: #d32f2f;
    }

    .admin-action-btn.delete:hover {
        background: #ff5252;
        color: #fff;
        box-shadow: 0 8px 18px rgba(255, 82, 82, .18);
    }

    .salon-gallery-empty {
        padding: 55px 20px;
        text-align: center;
        border: 1px dashed rgba(93, 64, 55, .16);
        border-radius: 22px;
        background: rgba(255, 255, 255, .2);
    }

    .salon-gallery-empty span {
        display: block;
        margin-bottom: 10px;
        color: var(--primary-color);
        font-size: 30px;
    }

    .salon-gallery-empty h3 {
        margin: 0 0 7px;
        color: var(--contrast-color);
        font-size: 1rem;
    }

    .salon-gallery-empty p {
        margin: 0;
        color: var(--text-muted);
        font-size: .85rem;
    }

    .admin-action-btn.edit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(119, 221, 119, .12);
        border-color: rgba(119, 221, 119, .25);
        color: #388e3c;
        text-decoration: none
    }

    .admin-action-btn.edit:hover {
        background: var(--primary-color);
        color: #fff;
        box-shadow: 0 8px 18px rgba(119, 221, 119, .2)
    }

    @media(max-width:800px) {
        .salon-gallery-admin-page {
            padding: 95px 18px 35px;
        }

        .admin-page-header,
        .salon-gallery-form-card,
        .salon-gallery-list-card {
            padding: 23px 20px;
            border-radius: 24px;
        }

        .salon-gallery-form-grid {
            grid-template-columns: 1fr;
        }

        .admin-field-full,
        .admin-checkbox {
            grid-column: auto;
        }

        .salon-gallery-admin-item {
            grid-template-columns: 90px minmax(0, 1fr);
            gap: 14px;
        }

        .salon-gallery-admin-image {
            width: 90px;
            height: 78px;
        }

        .salon-gallery-admin-actions {
            grid-column: 1/-1;
            width: 100%;
            padding-top: 5px;
            border-top: 1px solid rgba(93, 64, 55, .1);
        }

        .salon-gallery-admin-actions form {
            flex: 1;
        }

        .admin-action-btn {
            width: 100%;
        }
    }

    @media(max-width:480px) {
        .salon-gallery-admin-page {
            padding: 88px 12px 25px;
        }

        .admin-page-header,
        .salon-gallery-form-card,
        .salon-gallery-list-card {
            padding: 20px 16px;
            border-radius: 21px;
        }

        .admin-page-header h1 {
            font-size: 1.45rem;
        }

        .admin-card-title h2 {
            font-size: 1.05rem;
        }

        .salon-gallery-admin-item {
            grid-template-columns: 1fr;
        }

        .salon-gallery-admin-image {
            width: 100%;
            height: 190px;
        }

        .salon-gallery-admin-actions {
            gap: 7px;
        }
    }

    @media(prefers-reduced-motion:reduce) {

        .salon-gallery-admin-item,
        .admin-submit-btn,
        .admin-action-btn,
        .admin-field input,
        .admin-field textarea {
            transition: none;
        }
    }
</style>

<main class="salon-gallery-admin-page">
    <div class="admin-page-header">
        <div>
            <span class="admin-page-eyebrow">SALON GALLERY</span>
            <h1>مدیریت تصاویر سالن</h1>
            <p>تصاویر فضای سالن را اضافه، فعال، غیرفعال یا حذف کنید.</p>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="admin-alert success">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="admin-alert error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="salon-gallery-form-card">
        <div class="admin-card-title">
            <div>
                <h2>افزودن تصویر جدید</h2>
                <p>تصویر جدید فضای سالن را به گالری اضافه کنید.</p>
            </div>
            <span>🖼️</span>
        </div>

        <form method="POST" enctype="multipart/form-data" class="salon-gallery-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="salon-gallery-form-grid">
                <div class="admin-field">
                    <label for="title">عنوان تصویر</label>
                    <input id="title" type="text" name="title" required>
                </div>

                <div class="admin-field">
                    <label for="tag">تگ</label>
                    <input id="tag" type="text" name="tag" placeholder="مثلاً فضای سالن">
                </div>

                <div class="admin-field admin-field-full">
                    <label for="description">توضیحات</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="admin-field">
                    <label for="sort_order">ترتیب نمایش</label>
                    <input id="sort_order" type="number" name="sort_order" value="0" min="0">
                </div>

                <div class="admin-field">
                    <label for="image_file">تصویر</label>
                    <input id="image_file" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" required>
                </div>

                <label class="admin-checkbox">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>تصویر فعال باشد</span>
                </label>
            </div>

            <button type="submit" name="add_salon_gallery" class="admin-submit-btn">
                + افزودن تصویر
            </button>
        </form>
    </section>

    <section class="salon-gallery-list-card">
        <div class="admin-card-title">
            <div>
                <h2>تصاویر سالن</h2>
                <p><?= count($salonGallery) ?> تصویر ثبت شده است.</p>
            </div>
        </div>

        <?php if (!empty($salonGallery)): ?>
            <div class="salon-gallery-admin-list">
                <?php foreach ($salonGallery as $item): ?>
                    <article class="salon-gallery-admin-item">
                        <img
                            src="../<?= htmlspecialchars(ltrim($item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                            class="salon-gallery-admin-image">

                        <div class="salon-gallery-admin-info">
                            <h3><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>

                            <?php if (!empty($item['tag'])): ?>
                                <span><?= htmlspecialchars($item['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>

                            <?php if (!empty($item['description'])): ?>
                                <p><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>

                            <small>
                                ترتیب: <?= (int)$item['sort_order'] ?>
                            </small>
                        </div>

                        <div class="salon-gallery-admin-actions">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" name="toggle_salon_gallery" class="admin-action-btn <?= $item['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $item['is_active'] ? 'فعال' : 'غیرفعال' ?>
                                </button>
                            </form>

                            <form method="POST" onsubmit="return confirm('آیا از حذف این تصویر مطمئن هستید؟');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" name="delete_salon_gallery" class="admin-action-btn delete">
                                    حذف
                                </button>
                                <a href="edit-salon-gallery.php?id=<?= (int)$item['id'] ?>" class="admin-action-btn edit">
                                    ویرایش
                                </a>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="salon-gallery-empty">
                <span>✦</span>
                <h3>هنوز تصویری ثبت نشده است.</h3>
                <p>اولین تصویر سالن را از فرم بالا اضافه کنید.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>