<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../partials/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: reservation.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        r.*,
        u.fullname,
        u.phone
    FROM reservations AS r
    LEFT JOIN users AS u ON r.user_id=u.id
    WHERE r.id=?
");
$stmt->execute([$id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    header('Location: reservation.php');
    exit;
}

$staffs = $pdo->query("
    SELECT id,name,specialty
    FROM staff
    WHERE is_active=1
    ORDER BY sort_order ASC,id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$services = $pdo->query("
    SELECT DISTINCT name
    FROM services
    WHERE name IS NOT NULL
    AND name!=''
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reservation'])) {

    $service_name = trim($_POST['service_name'] ?? '');
    $res_date = trim($_POST['res_date'] ?? '');
    $res_time = trim($_POST['res_time'] ?? '');
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';

    $allowed_statuses = ['pending', 'confirmed', 'cancelled'];

    if ($service_name === '' || $res_date === '' || $res_time === '' || $staff_id <= 0) {
        $error = 'لطفاً تمام اطلاعات رزرو را کامل کنید.';
    } elseif (!in_array($status, $allowed_statuses, true)) {
        $error = 'وضعیت رزرو نامعتبر است.';
    } else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM reservations
            WHERE staff_id=?
            AND res_date=?
            AND res_time=?
            AND id!=?
            AND status!='cancelled'
            LIMIT 1
        ");

        $stmt->execute([
            $staff_id,
            $res_date,
            $res_time,
            $id
        ]);

        if ($stmt->fetch()) {
            $error = 'این متخصص در این تاریخ و ساعت قبلاً رزرو دارد.';
        } else {

            $stmt = $pdo->prepare("
                UPDATE reservations
                SET
                    service_name=?,
                    staff_id=?,
                    res_date=?,
                    res_time=?,
                    status=?
                WHERE id=?
            ");

            $stmt->execute([
                $service_name,
                $staff_id,
                $res_date,
                $res_time,
                $status,
                $id
            ]);

            header('Location: reservation.php?msg=edited');
            exit;
        }
    }

    $reservation['service_name'] = $service_name;
    $reservation['res_date'] = $res_date;
    $reservation['res_time'] = $res_time;
    $reservation['staff_id'] = $staff_id;
    $reservation['status'] = $status;
}

require_once __DIR__ . '/includes/header-admin.php';
?>

<style>
    .edit-reservation-page {
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
    }

    .edit-reservation-card {
        background: rgba(255, 255, 255, .3);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, .45);
        border-radius: 28px;
        padding: 30px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .5), 0 15px 35px rgba(0, 0, 0, .05);
    }

    .edit-reservation-header {
        margin-bottom: 25px;
    }

    .edit-reservation-header h1 {
        margin-bottom: 6px;
        font-size: 1.6rem;
    }

    .edit-reservation-header p {
        color: var(--text-muted);
        font-size: .9rem;
    }

    .edit-reservation-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full {
        grid-column: 1/-1;
    }

    .form-group label {
        font-size: .9rem;
        font-weight: 700;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        height: 48px;
        padding: 0 15px;
        border: 1px solid rgba(255, 255, 255, .55);
        border-radius: 15px;
        background: rgba(255, 255, 255, .45);
        color: var(--text-main);
        font-family: inherit;
        outline: none;
        transition: var(--transition-smooth);
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, .65);
        box-shadow: 0 0 0 3px rgba(119, 221, 119, .12);
    }

    .reservation-customer {
        padding: 16px 18px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .25);
        border: 1px solid rgba(255, 255, 255, .4);
    }

    .reservation-customer strong {
        display: block;
        margin-bottom: 5px;
    }

    .reservation-customer span {
        color: var(--text-muted);
        direction: ltr;
        display: inline-block;
    }

    .error-message {
        grid-column: 1/-1;
        padding: 14px 18px;
        border-radius: 15px;
        background: rgba(255, 82, 82, .18);
        color: #c62828;
        border: 1px solid rgba(255, 82, 82, .25);
        font-weight: 600;
    }

    .form-actions {
        grid-column: 1/-1;
        display: flex;
        justify-content: flex-start;
        gap: 12px;
        margin-top: 10px;
    }

    .btn-save,
    .btn-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 130px;
        height: 46px;
        padding: 0 20px;
        border-radius: 15px;
        text-decoration: none;
        font-family: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition-smooth);
    }

    .btn-save {
        border: 1px solid rgba(119, 221, 119, .3);
        background: rgba(119, 221, 119, .2);
        color: var(--primary-hover);
    }

    .btn-save:hover {
        background: var(--primary-color);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(119, 221, 119, .3);
    }

    .btn-back {
        border: 1px solid rgba(255, 255, 255, .45);
        background: rgba(255, 255, 255, .4);
        color: var(--text-main);
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, .65);
        transform: translateY(-2px);
    }

    @media(max-width:768px) {
        .edit-reservation-card {
            padding: 20px;
        }

        .edit-reservation-form {
            grid-template-columns: 1fr;
        }

        .form-group.full,
        .error-message,
        .form-actions {
            grid-column: auto;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-save,
        .btn-back {
            width: 100%;
        }
    }
</style>

<main class="admin-dashboard edit-reservation-page">

    <div class="edit-reservation-card">

        <div class="edit-reservation-header">
            <h1>ویرایش رزرو</h1>
            <p>اطلاعات رزرو مشتری را ویرایش کنید.</p>
        </div>

        <form method="POST" class="edit-reservation-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($error !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="form-group full">
                <label>مشتری</label>

                <div class="reservation-customer">
                    <strong>
                        <?= htmlspecialchars($reservation['fullname'] ?? 'کاربر حذف‌شده') ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($reservation['phone'] ?? '---') ?>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label for="service_name">خدمت</label>

                <select name="service_name" id="service_name" required>
                    <option value="">انتخاب خدمت</option>

                    <?php foreach ($services as $service): ?>
                        <option
                            value="<?= htmlspecialchars($service['name']) ?>"
                            <?= ($reservation['service_name'] ?? '') === $service['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($service['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="staff_id">متخصص</label>

                <select name="staff_id" id="staff_id" required>
                    <option value="">انتخاب متخصص</option>

                    <?php foreach ($staffs as $staff): ?>
                        <option
                            value="<?= (int)$staff['id'] ?>"
                            <?= (int)($reservation['staff_id'] ?? 0) === (int)$staff['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($staff['name']) ?>
                            -
                            <?= htmlspecialchars($staff['specialty']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="res_date">تاریخ</label>

                <input
                    type="text"
                    name="res_date"
                    id="res_date"
                    value="<?= htmlspecialchars($reservation['res_date'] ?? '') ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="res_time">ساعت</label>

                <input
                    type="time"
                    name="res_time"
                    id="res_time"
                    value="<?= htmlspecialchars(substr($reservation['res_time'] ?? '', 0, 5)) ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="status">وضعیت</label>

                <select name="status" id="status" required>
                    <option value="pending" <?= ($reservation['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                        در انتظار
                    </option>

                    <option value="confirmed" <?= ($reservation['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>
                        تایید شده
                    </option>

                    <option value="cancelled" <?= ($reservation['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>
                        لغو شده
                    </option>
                </select>
            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    name="update_reservation"
                    class="btn-save">
                    ذخیره تغییرات
                </button>

                <a href="reservation.php" class="btn-back">
                    بازگشت
                </a>

            </div>

        </form>

    </div>

</main>

<?php require_once 'includes/footer-admin.php'; ?>