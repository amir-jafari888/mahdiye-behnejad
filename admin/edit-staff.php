<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: admin-staff.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id=?");
$stmt->execute([$id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    header('Location: admin-staff.php');
    exit;
}
$serviceStmt = $pdo->prepare("
    SELECT service_id
    FROM staff_services
    WHERE staff_id = ?
");
$serviceStmt->execute([$id]);
$selectedServices = array_map('intval', $serviceStmt->fetchAll(PDO::FETCH_COLUMN));

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $name = trim($_POST['name'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $image_path = $staff['image_path'];
    $selectedServices = $_POST['services'] ?? [];

    if (!is_array($selectedServices)) {
        $selectedServices = [];
    }

    $selectedServices = array_values(array_unique(array_filter(
        array_map('intval', $selectedServices),
        fn($serviceId) => $serviceId > 0
    )));

    if ($name === '' || $specialty === '') {
        $error = 'نام و تخصص کادر الزامی است.';
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = upload_secure_image(
                $_FILES['image'],
                __DIR__ . '/../uploads/staff/',
                'staff_'
            );

            if ($upload['success']) {
                if (!empty($staff['image_path']) && !preg_match('#^(?:https?:)?//#i', $staff['image_path'])) {
                    $oldFile = '../' . ltrim($staff['image_path'], '/');
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $image_path = 'uploads/staff/' . $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
            UPDATE staff
            SET name=?, specialty=?, image_path=?, bio=?, is_active=?, sort_order=?
            WHERE id=?
        ");
                $stmt->execute([
                    $name,
                    $specialty,
                    $image_path,
                    $bio,
                    $is_active,
                    $sort_order,
                    $id
                ]);

                $deleteServices = $pdo->prepare("
            DELETE FROM staff_services
            WHERE staff_id = ?
        ");
                $deleteServices->execute([$id]);

                if (!empty($selectedServices)) {
                    $serviceStmt = $pdo->prepare("
                INSERT INTO staff_services (staff_id, service_id)
                VALUES (?, ?)
            ");

                    foreach ($selectedServices as $serviceId) {
                        $serviceStmt->execute([$id, $serviceId]);
                    }
                }

                $pdo->commit();

                header('Location: admin-staff.php?msg=edited');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Staff update failed: ' . $e->getMessage());
                $error = 'ذخیره تغییرات با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
            }
        }
        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE staff SET name=?,specialty=?,image_path=?,bio=?,is_active=?,sort_order=? WHERE id=?");
            $stmt->execute([$name, $specialty, $image_path, $bio, $is_active, $sort_order, $id]);
            header('Location: admin-staff.php?msg=edited');
            exit;
        }
    }

    $staff['name'] = $name;
    $staff['specialty'] = $specialty;
    $staff['bio'] = $bio;
    $staff['is_active'] = $is_active;
    $staff['sort_order'] = $sort_order;
    $staff['image_path'] = $image_path;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<main class="staff-admin-page">
    <div class="staff-admin-header">
        <div>
            <span class="staff-admin-eyebrow">EDIT STAFF</span>
            <h1>ویرایش کادر حرفه‌ای</h1>
            <p>اطلاعات عضو انتخاب‌شده را ویرایش کنید.</p>
        </div>
        <div class="staff-count">
            <strong><?php echo (int)$staff['sort_order']; ?></strong>
            <span>ترتیب نمایش</span>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="staff-alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <section class="staff-form-card">
        <div class="staff-card-title">
            <div>
                <h2>ویرایش <?php echo htmlspecialchars($staff['name']); ?></h2>
                <p>اطلاعات این عضو را تغییر دهید و ذخیره کنید.</p>
            </div>
            <i class="fas fa-user-edit"></i>
        </div>

        <form method="POST" enctype="multipart/form-data" class="staff-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="staff-form-grid">
                <div class="staff-field">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>" required>
                </div>

                <div class="staff-field">
                    <label>لاین / تخصص</label>
                    <input type="text" name="specialty" value="<?php echo htmlspecialchars($staff['specialty']); ?>" required>
                </div>

                <div class="staff-field staff-field-full">
                    <label>سرویس‌های قابل ارائه</label>
                    <div class="staff-services-select">
                        <?php
                        $services = $pdo->query("
            SELECT id, name, category
            FROM services
            ORDER BY category, name
        ")->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php foreach ($services as $service): ?>
                            <label class="staff-service-option">
                                <input
                                    type="checkbox"
                                    name="services[]"
                                    value="<?= (int)$service['id'] ?>"
                                    <?= in_array((int)$service['id'], $selectedServices, true) ? 'checked' : '' ?>>
                                <span>
                                    <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($service['category'])): ?>
                                        <small><?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="staff-field staff-field-full">
                    <label>توضیحات کوتاه</label>
                    <textarea name="bio" placeholder="توضیحات کوتاه درباره متخصص..."><?php echo htmlspecialchars($staff['bio'] ?? ''); ?></textarea>
                </div>

                <div class="staff-field">
                    <label>ترتیب نمایش</label>
                    <input type="number" name="sort_order" value="<?php echo (int)$staff['sort_order']; ?>" min="0">
                </div>

                <div class="staff-field">
                    <label>تصویر جدید</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <label class="staff-checkbox">
                <input type="checkbox" name="is_active" value="1" <?php echo $staff['is_active'] ? 'checked' : ''; ?>>
                <span>نمایش در سایت</span>
            </label>

            <div class="staff-admin-actions" style="justify-content:flex-start;margin-top:0;">
                <button type="submit" name="update_staff" class="staff-submit">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>

                <a href="admin-staff.php" title="بازگشت">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </form>
    </section>

    <section class="staff-list-section">
        <div class="staff-list-header">
            <div>
                <h2>تصویر فعلی</h2>
                <p>اگر تصویر جدید انتخاب نکنید، همین تصویر حفظ می‌شود.</p>
            </div>
        </div>

        <div style="display:flex;justify-content:center;">
            <div class="staff-admin-image">
                <img src="<?php echo htmlspecialchars('../' . $staff['image_path']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                <span class="staff-status <?php echo $staff['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $staff['is_active'] ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>