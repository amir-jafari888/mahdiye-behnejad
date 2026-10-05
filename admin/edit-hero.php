<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: admin-hero.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE id=?");
$stmt->execute([$id]);
$slide = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slide) {
    header('Location: admin-hero.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hero'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path = $slide['image_path'];

    if ($title === '') {
        $error = 'عنوان اسلاید الزامی است.';
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = upload_secure_image(
                $_FILES['image'],
                __DIR__ . '/../uploads/hero/',
                'hero_'
            );

            if ($upload['success']) {
                if (!empty($slide['image_path']) && !preg_match('#^(?:https?:)?//#i', $slide['image_path'])) {
                    $oldFile = '../' . ltrim($slide['image_path'], '/');

                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $image_path = 'uploads/hero/' . $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE hero_slides SET title=?,description=?,image_path=? WHERE id=?");
            $stmt->execute([$title, $description, $image_path, $id]);

            header('Location: admin-hero.php');
            exit;
        }
    }

    $slide['title'] = $title;
    $slide['description'] = $description;
    $slide['image_path'] = $image_path;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<main class="hero-edit-page">
    <div class="hero-edit-header">
        <div>
            <span class="hero-edit-eyebrow">EDIT HERO</span>
            <h1>ویرایش اسلاید هیرو</h1>
            <p>اطلاعات اسلاید انتخاب‌شده را ویرایش کنید.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="hero-alert error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <section class="hero-form-card">
        <div class="hero-card-title">
            <div>
                <h2>ویرایش اسلاید</h2>
                <p>عنوان، توضیحات یا تصویر اسلاید را تغییر دهید.</p>
            </div>
            <span>✏️</span>
        </div>

        <form method="POST" enctype="multipart/form-data" class="hero-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="hero-form-grid">
                <div class="hero-field">
                    <label>عنوان اسلاید</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($slide['title']); ?>" required>
                </div>

                <div class="hero-field hero-field-full">
                    <label>توضیحات اسلاید</label>
                    <textarea name="description" placeholder="توضیحات اسلاید..."><?php echo htmlspecialchars($slide['description'] ?? ''); ?></textarea>
                </div>

                <div class="hero-field hero-field-full">
                    <label>تصویر جدید</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small>اگر تصویر جدید انتخاب نکنید، تصویر فعلی حفظ می‌شود.</small>
                </div>
            </div>

            <div class="hero-admin-actions">
                <button type="submit" name="update_hero" class="hero-submit">
                    💾 ذخیره تغییرات
                </button>

                <a href="admin-hero.php" class="hero-back">
                    ↩️ بازگشت
                </a>
            </div>
        </form>
    </section>

    <section class="hero-current-card">
        <div class="hero-card-title">
            <div>
                <h2>تصویر فعلی</h2>
                <p>تصویری که در حال حاضر برای این اسلاید ذخیره شده است.</p>
            </div>
        </div>

        <div class="hero-current-image">
            <img src="<?php echo htmlspecialchars('../' . ltrim($slide['image_path'], '/')); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
        </div>
    </section>
</main>

<style>
    .hero-edit-page {
        max-width: 1000px;
        margin: 0 auto;
        padding: 120px 20px 50px;
        animation: heroFadeIn .6s cubic-bezier(.25, 1, .5, 1) forwards;
    }

    .hero-edit-header {
        margin-bottom: 25px;
    }

    .hero-edit-eyebrow {
        display: inline-block;
        color: #388e3c;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: 2px;
        margin-bottom: 8px;
    }

    .hero-edit-header h1 {
        color: #5d4037;
        font-size: 1.8rem;
        margin: 0 0 8px;
    }

    .hero-edit-header p {
        color: #777;
        margin: 0;
    }

    .hero-alert {
        padding: 15px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .hero-alert.error {
        background: rgba(255, 82, 82, .12);
        color: #d32f2f;
        border: 1px solid rgba(255, 82, 82, .2);
    }

    .hero-form-card,
    .hero-current-card {
        background: rgba(255, 255, 255, .45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 28px;
        padding: 30px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 25px -5px rgba(0, 0, 0, .05);
        margin-bottom: 25px;
    }

    .hero-card-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .hero-card-title h2 {
        color: #5d4037;
        margin: 0 0 6px;
        font-size: 1.3rem;
    }

    .hero-card-title p {
        color: #777;
        margin: 0;
        font-size: .9rem;
    }

    .hero-card-title>span {
        width: 45px;
        height: 45px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: rgba(119, 221, 119, .16);
        font-size: 20px;
    }

    .hero-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .hero-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .hero-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .hero-field-full {
        grid-column: 1/-1;
    }

    .hero-field label {
        color: #5d4037;
        font-weight: 600;
        font-size: .9rem;
    }

    .hero-field input,
    .hero-field textarea {
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

    .hero-field textarea {
        min-height: 110px;
        max-height: 220px;
        resize: vertical;
    }

    .hero-field input:focus,
    .hero-field textarea:focus {
        background: rgba(255, 255, 255, .95);
        border-color: #77dd77;
        box-shadow: 0 0 0 4px rgba(119, 221, 119, .2);
    }

    .hero-field input[type=file] {
        padding: 10px;
        cursor: pointer;
        border: 1px dashed rgba(93, 64, 55, .3);
    }

    .hero-field small {
        color: #777;
        font-size: .78rem;
    }

    .hero-admin-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .hero-submit,
    .hero-back {
        border: none;
        text-decoration: none;
        padding: 13px 24px;
        border-radius: 17px;
        font-family: inherit;
        font-weight: 600;
        cursor: pointer;
        transition: all .4s cubic-bezier(.4, 0, .2, 1);
    }

    .hero-submit {
        background: #77dd77;
        color: #fff;
        box-shadow: 0 4px 15px rgba(119, 221, 119, .3);
    }

    .hero-submit:hover {
        background: #388e3c;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(56, 142, 60, .3);
    }

    .hero-back {
        background: rgba(255, 255, 255, .5);
        color: #5d4037;
        border: 1px solid rgba(255, 255, 255, .7);
    }

    .hero-back:hover {
        background: rgba(255, 255, 255, .8);
        transform: translateY(-2px);
    }

    .hero-current-image {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .hero-current-image img {
        display: block;
        width: 100%;
        max-width: 700px;
        max-height: 380px;
        object-fit: cover;
        border-radius: 22px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
    }

    @keyframes heroFadeIn {
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
        .hero-edit-page {
            padding: 100px 15px 35px;
        }

        .hero-form-card,
        .hero-current-card {
            padding: 20px;
            border-radius: 22px;
        }

        .hero-form-grid {
            grid-template-columns: 1fr;
        }

        .hero-field-full {
            grid-column: auto;
        }

        .hero-admin-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .hero-submit,
        .hero-back {
            text-align: center;
        }
    }
</style>

<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>