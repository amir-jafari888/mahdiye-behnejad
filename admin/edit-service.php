<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: admin-service.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    header('Location: admin-service.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $image_path = $service['image_path'];

    if ($name === '' || $category === '') {
        $error = 'نام خدمت و دسته‌بندی الزامی است.';
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = upload_secure_image(
                $_FILES['image'],
                __DIR__ . '/../uploads/services/',
                'service_'
            );

            if ($upload['success']) {
                if (!empty($service['image_path']) && !preg_match('#^(?:https?:)?//#i', $service['image_path'])) {
                    $oldFile = '../' . ltrim($service['image_path'], '/');

                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $image_path = 'uploads/services/' . $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE services SET name=?,description=?,price=?,category=?,image_path=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $category, $image_path, $id]);

            header('Location: admin-service.php?msg=edited');
            exit;
        }
    }

    $service['name'] = $name;
    $service['description'] = $description;
    $service['price'] = $price;
    $service['category'] = $category;
    $service['image_path'] = $image_path;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<main class="service-edit-page">
    <div class="service-edit-header">
        <div>
            <span class="service-edit-eyebrow">EDIT SERVICE</span>
            <h1>ویرایش خدمت</h1>
            <p>اطلاعات خدمت انتخاب‌شده را ویرایش کنید.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="service-alert error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <section class="service-form-card">
        <div class="service-card-title">
            <div>
                <h2>ویرایش <?php echo htmlspecialchars($service['name']); ?></h2>
                <p>اطلاعات این خدمت را تغییر دهید و ذخیره کنید.</p>
            </div>
            <span>✏️</span>
        </div>

        <form method="POST" enctype="multipart/form-data" class="service-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="service-form-grid">
                <div class="service-field">
                    <label>نام خدمت</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                </div>

                <div class="service-field">
                    <label>قیمت</label>
                    <input type="text" name="price" value="<?php echo htmlspecialchars($service['price']); ?>" placeholder="مثلاً ۲,۵۰۰,۰۰۰">
                </div>

                <div class="service-field service-field-full">
                    <label>دسته‌بندی</label>
                    <select name="category" required>
                        <option value="میکاپ" <?php echo $service['category'] === 'میکاپ' ? 'selected' : ''; ?>>میکاپ</option>
                        <option value="ناخن" <?php echo $service['category'] === 'ناخن' ? 'selected' : ''; ?>>ناخن</option>
                        <option value="کوپ" <?php echo $service['category'] === 'کوپ' ? 'selected' : ''; ?>>کوپ</option>
                        <option value="رنگ و لایت" <?php echo $service['category'] === 'رنگ و لایت' ? 'selected' : ''; ?>>رنگ و لایت</option>
                        <option value="براشینگ" <?php echo $service['category'] === 'براشینگ' ? 'selected' : ''; ?>>براشینگ</option>
                        <option value="کراتین" <?php echo $service['category'] === 'کراتین' ? 'selected' : ''; ?>>کراتین</option>
                        <option value="اصلاح ابرو" <?php echo $service['category'] === 'اصلاح ابرو' ? 'selected' : ''; ?>>اصلاح ابرو</option>
                        <option value="اصلاح صورت" <?php echo $service['category'] === 'اصلاح صورت' ? 'selected' : ''; ?>>اصلاح صورت</option>
                        <option value="آرایش دایم" <?php echo $service['category'] === 'آرایش دایم' ? 'selected' : ''; ?>>آرایش دایم</option>
                        <option value="آرایش دائم" <?php echo $service['category'] === 'آرایش دائم' ? 'selected' : ''; ?>>آرایش دائم</option>
                        <option value="مژه" <?php echo $service['category'] === 'مژه' ? 'selected' : ''; ?>>مژه</option>
                        <option value="پوست" <?php echo $service['category'] === 'پوست' ? 'selected' : ''; ?>>پوست</option>
                        <option value="اکستنشن مو" <?php echo $service['category'] === 'اکستنشن مو' ? 'selected' : ''; ?>>اکستنشن مو</option>
                        <option value="پدیکور" <?php echo $service['category'] === 'پدیکور' ? 'selected' : ''; ?>>پدیکور</option>
                        <option value="سرویس عروس" <?php echo $service['category'] === 'سرویس عروس' ? 'selected' : ''; ?>>سرویس عروس</option>
                    </select>
                </div>

                <div class="service-field service-field-full">
                    <label>توضیحات</label>
                    <textarea name="description" placeholder="توضیحات کوتاه درباره خدمت..."><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>
                </div>

                <div class="service-field service-field-full">
                    <label>تصویر جدید</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small>اگر تصویر جدید انتخاب نکنید، تصویر فعلی حفظ می‌شود.</small>
                </div>
            </div>

            <div class="service-admin-actions">
                <button type="submit" name="update_service" class="service-submit">
                    💾 ذخیره تغییرات
                </button>

                <a href="admin-service.php" class="service-back">
                    ↩️ بازگشت
                </a>
            </div>
        </form>
    </section>

    <section class="service-current-card">
        <div class="service-card-title">
            <div>
                <h2>تصویر فعلی</h2>
                <p>تصویری که در حال حاضر برای این خدمت ذخیره شده است.</p>
            </div>
        </div>

        <div class="service-current-image">
            <img src="<?php echo htmlspecialchars('../' . ltrim($service['image_path'], '/')); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
        </div>
    </section>
</main>

<style>
    .service-edit-page {
        max-width: 1000px;
        margin: 0 auto;
        padding: 120px 20px 50px;
        animation: serviceFadeIn .6s cubic-bezier(.25, 1, .5, 1) forwards;
    }

    .service-edit-header {
        margin-bottom: 25px;
    }

    .service-edit-eyebrow {
        display: inline-block;
        color: #388e3c;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: 2px;
        margin-bottom: 8px;
    }

    .service-edit-header h1 {
        color: #5d4037;
        font-size: 1.8rem;
        margin: 0 0 8px;
    }

    .service-edit-header p {
        color: #777;
        margin: 0;
    }

    .service-alert {
        padding: 15px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .service-alert.error {
        background: rgba(255, 82, 82, .12);
        color: #d32f2f;
        border: 1px solid rgba(255, 82, 82, .2);
    }

    .service-form-card,
    .service-current-card {
        background: rgba(255, 255, 255, .45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 28px;
        padding: 30px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 25px -5px rgba(0, 0, 0, .05);
        margin-bottom: 25px;
    }

    .service-card-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .service-card-title h2 {
        color: #5d4037;
        margin: 0 0 6px;
        font-size: 1.3rem;
    }

    .service-card-title p {
        color: #777;
        margin: 0;
        font-size: .9rem;
    }

    .service-card-title>span {
        width: 45px;
        height: 45px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background: rgba(119, 221, 119, .16);
        font-size: 20px;
    }

    .service-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .service-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .service-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .service-field-full {
        grid-column: 1/-1;
    }

    .service-field label {
        color: #5d4037;
        font-weight: 600;
        font-size: .9rem;
    }

    .service-field input,
    .service-field textarea,
    .service-field select {
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

    .service-field textarea {
        min-height: 110px;
        max-height: 220px;
        resize: vertical;
    }

    .service-field select {
        cursor: pointer;
    }

    .service-field input:focus,
    .service-field textarea:focus,
    .service-field select:focus {
        background: rgba(255, 255, 255, .95);
        border-color: #77dd77;
        box-shadow: 0 0 0 4px rgba(119, 221, 119, .2);
    }

    .service-field input[type=file] {
        padding: 10px;
        cursor: pointer;
        border: 1px dashed rgba(93, 64, 55, .3);
    }

    .service-field small {
        color: #777;
        font-size: .78rem;
    }

    .service-admin-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .service-submit,
    .service-back {
        border: none;
        text-decoration: none;
        padding: 13px 24px;
        border-radius: 17px;
        font-family: inherit;
        font-weight: 600;
        cursor: pointer;
        transition: all .4s cubic-bezier(.4, 0, .2, 1);
    }

    .service-submit {
        background: #77dd77;
        color: #fff;
        box-shadow: 0 4px 15px rgba(119, 221, 119, .3);
    }

    .service-submit:hover {
        background: #388e3c;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(56, 142, 60, .3);
    }

    .service-back {
        background: rgba(255, 255, 255, .5);
        color: #5d4037;
        border: 1px solid rgba(255, 255, 255, .7);
    }

    .service-back:hover {
        background: rgba(255, 255, 255, .8);
        transform: translateY(-2px);
    }

    .service-current-image {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .service-current-image img {
        display: block;
        width: 100%;
        max-width: 700px;
        max-height: 500px;
        object-fit: contain;
        border-radius: 22px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
    }

    @keyframes serviceFadeIn {
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
        .service-edit-page {
            padding: 100px 15px 35px;
        }

        .service-form-card,
        .service-current-card {
            padding: 20px;
            border-radius: 22px;
        }

        .service-form-grid {
            grid-template-columns: 1fr;
        }

        .service-field-full {
            grid-column: auto;
        }

        .service-admin-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .service-submit,
        .service-back {
            text-align: center;
        }
    }
</style>

<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>