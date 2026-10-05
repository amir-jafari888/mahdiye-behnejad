<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';
require_once __DIR__ . '/includes/upload-security.php';
$message = '';
$error = '';
$services = $pdo->query("
    SELECT id, name, category
    FROM services
    ORDER BY category, name
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff'])) {
        $name = trim($_POST['name'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $selectedServices = $_POST['services'] ?? [];
        if (!is_array($selectedServices)) {
            $selectedServices = [];
        }
        $selectedServices = array_values(array_unique(array_filter(
            array_map('intval', $selectedServices),
            fn($id) => $id > 0
        )));
        $image_path = '';
        if (isset($_FILES['image'])) {
            $upload = upload_secure_image(
                $_FILES['image'],
                __DIR__ . '/../uploads/staff/',
                'staff_'
            );
            if ($upload['success']) {
                $image_path = 'uploads/staff/' . $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        }
        if ($name === '' || $specialty === '') {
            $error = 'نام و تخصص کادر الزامی است.';
        } elseif ($image_path === '' && $error === '') {
            $error = 'لطفاً تصویر کادر را انتخاب کنید.';
        } elseif ($error === '') {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO staff
                    (name, specialty, image_path, bio, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name,
                    $specialty,
                    $image_path,
                    $bio,
                    $is_active,
                    $sort_order
                ]);
                $staffId = (int)$pdo->lastInsertId();
                if (!empty($selectedServices)) {
                    $serviceStmt = $pdo->prepare("
                        INSERT INTO staff_services (staff_id, service_id)
                        VALUES (?, ?)
                    ");
                    foreach ($selectedServices as $serviceId) {
                        $serviceStmt->execute([$staffId, $serviceId]);
                    }
                }
                $pdo->commit();
                header("Location: admin-staff.php?msg=added");
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Staff insert failed: ' . $e->getMessage());
                $error = 'ثبت کادر با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
            }
        }
    }

    if (isset($_POST['delete_staff'])) {
        $id = (int)($_POST['staff_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT image_path FROM staff WHERE id=?");
            $stmt->execute([$id]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($staff) {
                if (!empty($staff['image_path']) && file_exists('../' . $staff['image_path'])) {
                    unlink('../' . $staff['image_path']);
                }
                $pdo->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
            }
        }
        header("Location: admin-staff.php?msg=deleted");
        exit;
    }

    if (isset($_POST['toggle_staff'])) {
        $id = (int)($_POST['staff_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE staff SET is_active=IF(is_active=1,0,1) WHERE id=?");
            $stmt->execute([$id]);
        }
        header("Location: admin-staff.php?msg=updated");
        exit;
    }
}
$staffMembers = $pdo->query("SELECT * FROM staff ORDER BY sort_order ASC,id ASC")->fetchAll(PDO::FETCH_ASSOC);
$message = $_GET['msg'] ?? '';
require_once __DIR__ . '/includes/header-admin.php';
?>
<main class="staff-admin-page">
    <div class="staff-admin-header">
        <div>
            <span class="staff-admin-eyebrow">STAFF MANAGEMENT</span>
            <h1>مدیریت کادر حرفه‌ای</h1>
            <p>افزودن، ویرایش و مدیریت اعضای کادر مجموعه</p>
        </div>
        <div class="staff-count">
            <strong><?php echo count($staffMembers); ?></strong>
            <span>عضو کادر</span>
        </div>
    </div>
    <?php if ($message === 'added'): ?>
        <div class="staff-alert success">کادر جدید با موفقیت اضافه شد.</div>
    <?php elseif ($message === 'edited'): ?>
        <div class="staff-alert success">اطلاعات کادر با موفقیت ویرایش شد.</div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="staff-alert success">کادر با موفقیت حذف شد.</div>
    <?php elseif ($message === 'updated'): ?>
        <div class="staff-alert success">وضعیت کادر تغییر کرد.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="staff-alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <section class="staff-form-card">
        <div class="staff-card-title">
            <div>
                <h2>افزودن عضو جدید</h2>
                <p>اطلاعات کادر جدید را وارد کنید.</p>
            </div>
            <i class="fas fa-user-plus"></i>
        </div>
        <form method="POST" enctype="multipart/form-data" class="staff-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="staff-form-grid">
                <div class="staff-field">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="name" placeholder="مثلاً مهدیه بهنژاد" required>
                </div>
                <div class="staff-field">
                    <label>لاین / تخصص</label>
                    <input type="text" name="specialty" placeholder="مثلاً لاین میکاپ" required>
                </div>
                <div class="staff-field staff-field-full">
                    <label>سرویس‌های قابل ارائه</label>
                    <div class="staff-services-select">
                        <?php foreach ($services as $service): ?>
                            <label class="staff-service-option">
                                <input type="checkbox" name="services[]" value="<?= (int)$service['id'] ?>">
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
                    <textarea name="bio" placeholder="توضیحات کوتاه درباره متخصص..."></textarea>
                </div>
                <div class="staff-field">
                    <label>ترتیب نمایش</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <div class="staff-field">
                    <label>تصویر</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
                </div>
            </div>
            <label class="staff-checkbox">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>نمایش در سایت</span>
            </label>
            <button type="submit" name="add_staff" class="staff-submit"><i class="fas fa-plus"></i> افزودن کادر</button>
        </form>
    </section>
    <section class="staff-list-section">
        <div class="staff-list-header">
            <div>
                <h2>اعضای کادر</h2>
                <p>مدیریت اعضای ثبت‌شده در سایت</p>
            </div>
        </div>
        <div class="staff-admin-grid">
            <?php foreach ($staffMembers as $staff): ?>
                <article class="staff-admin-card <?php echo $staff['is_active'] ? '' : 'inactive'; ?>">
                    <div class="staff-admin-image">
                        <img src="<?php echo htmlspecialchars('../' . $staff['image_path']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                        <span class="staff-status <?php echo $staff['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $staff['is_active'] ? 'فعال' : 'غیرفعال'; ?></span>
                    </div>
                    <div class="staff-admin-info">
                        <h3><?php echo htmlspecialchars($staff['name']); ?></h3>
                        <p><?php echo htmlspecialchars($staff['specialty']); ?></p>
                        <?php if (!empty($staff['bio'])): ?><span><?php echo htmlspecialchars($staff['bio']); ?></span><?php endif; ?>
                    </div>
                    <div class="staff-admin-actions">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="staff_id" value="<?php echo (int)$staff['id']; ?>">
                            <button type="submit" name="toggle_staff" title="تغییر وضعیت">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2v10M6.2 5.5a9 9 0 1 0 11.6 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </button>
                        </form>
                        <a href="edit-staff.php?id=<?php echo (int)$staff['id']; ?>" title="ویرایش">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 20h4L19 9l-4-4L4 16v4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="m13.5 6.5 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </a>
                        <form method="POST" onsubmit="return confirm('آیا از حذف این کادر مطمئن هستید؟');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="staff_id" value="<?php echo (int)$staff['id']; ?>">
                            <button type="submit" name="delete_staff" class="delete" title="حذف">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M5 7h14M9 7V4h6v3M8 7l1 13h6l1-13M10 11v5M14 11v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($staffMembers)): ?><div class="staff-empty">هنوز عضوی برای کادر ثبت نشده است.</div><?php endif; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer-admin.php'; ?>