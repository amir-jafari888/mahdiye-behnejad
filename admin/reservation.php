<?php
require_once '../partials/db.php';
require_once 'includes/header-admin.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $reservation_id = (int)($_POST['reservation_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['pending', 'confirmed', 'cancelled'];
    if ($reservation_id > 0 && in_array($new_status, $allowed_statuses, true)) {
        try {
            $stmt = $pdo->prepare("UPDATE reservations SET status=? WHERE id=?");
            $stmt->execute([$new_status, $reservation_id]);
            $message = 'وضعیت رزرو با موفقیت تغییر کرد.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'خطا در تغییر وضعیت رزرو.';
            $message_type = 'error';
        }
    } else {
        $message = 'اطلاعات وضعیت رزرو نامعتبر است.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $reservation_id = (int)($_POST['reservation_id'] ?? 0);
    if ($reservation_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id=?");
            $stmt->execute([$reservation_id]);
            if ($stmt->rowCount() > 0) {
                $message = 'رزرو با موفقیت حذف شد.';
                $message_type = 'success';
            } else {
                $message = 'رزرو موردنظر پیدا نشد.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'خطا در حذف رزرو.';
            $message_type = 'error';
        }
    } else {
        $message = 'شناسه رزرو نامعتبر است.';
        $message_type = 'error';
    }
}

$reservations = [];

try {
    $stmt = $pdo->query("
        SELECT
            r.id,
            r.user_id,
            r.service_name,
            r.res_date,
            r.res_time,
            r.status,
            r.created_at,
            u.fullname,
            u.phone,
            s.name AS staff_name
        FROM reservations AS r
        LEFT JOIN users AS u ON r.user_id=u.id
        LEFT JOIN staff AS s ON r.staff_id=s.id
        ORDER BY r.res_date DESC,r.res_time DESC,r.id DESC
    ");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'خطا در دریافت اطلاعات رزروها.';
    $message_type = 'error';
}
?>

<style>
    .reservation-page {
        width: 100%
    }

    .reservation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        padding: 25px 30px;
        background: rgba(255, 255, 255, .35);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 28px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .5), 0 15px 35px rgba(0, 0, 0, .05)
    }

    .reservation-header h1 {
        margin-bottom: 5px;
        font-size: 1.6rem
    }

    .reservation-header p {
        color: var(--text-muted);
        font-size: .9rem
    }

    .reservation-count {
        padding: 10px 18px;
        border-radius: 30px;
        background: rgba(119, 221, 119, .2);
        color: #2e7d32;
        font-weight: 700
    }

    .reservation-message {
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 18px;
        font-weight: 600;
        backdrop-filter: blur(10px)
    }

    .reservation-message.success {
        background: rgba(119, 221, 119, .25);
        color: #2e7d32;
        border: 1px solid rgba(119, 221, 119, .35)
    }

    .reservation-message.error {
        background: rgba(255, 82, 82, .18);
        color: #c62828;
        border: 1px solid rgba(255, 82, 82, .25)
    }

    .reservation-table-card {
        background: rgba(255, 255, 255, .3);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, .45);
        border-radius: 28px;
        padding: 25px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .5), 0 15px 35px rgba(0, 0, 0, .05)
    }

    .reservation-table-wrapper {
        width: 100%;
        overflow-x: auto
    }

    .reservation-table {
        width: 100%;
        min-width: 1050px;
        border-collapse: separate;
        border-spacing: 0 10px
    }

    .reservation-table th {
        padding: 12px 15px;
        color: var(--text-muted);
        font-size: .85rem;
        font-weight: 700;
        text-align: right
    }

    .reservation-table td {
        padding: 17px 15px;
        background: rgba(255, 255, 255, .35);
        border-top: 1px solid rgba(255, 255, 255, .3);
        border-bottom: 1px solid rgba(255, 255, 255, .3)
    }

    .reservation-table tbody tr {
        transition: var(--transition-smooth)
    }

    .reservation-table tbody tr:hover {
        transform: translateY(-3px)
    }

    .reservation-table tbody tr:hover td {
        background: rgba(255, 255, 255, .6)
    }

    .reservation-table td:first-child {
        border-radius: 0 18px 18px 0
    }

    .reservation-table td:last-child {
        border-radius: 18px 0 0 18px
    }

    .customer-name {
        font-weight: 700
    }

    .customer-phone {
        color: var(--text-muted);
        direction: ltr;
        display: inline-block
    }

    .reservation-date {
        font-weight: 600
    }

    .reservation-time {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 12px;
        background: rgba(93, 64, 55, .08);
        font-weight: 700
    }

    .reservation-staff {
        font-weight: 700;
        color: var(--brown-color, #5d4037);
        white-space: nowrap
    }

    .reservation-staff small {
        display: block;
        margin-top: 4px;
        color: var(--text-muted);
        font-size: .72rem;
        font-weight: 500
    }

    .reservation-status {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: .82rem;
        font-weight: 700;
        white-space: nowrap
    }

    .reservation-status.pending {
        background: rgba(255, 202, 40, .25);
        color: #ef6c00
    }

    .reservation-status.confirmed {
        background: rgba(119, 221, 119, .25);
        color: #2e7d32
    }

    .reservation-status.cancelled {
        background: rgba(255, 82, 82, .18);
        color: #c62828
    }

    .reservation-actions {
        display: flex;
        align-items: center;
        gap: 8px
    }

    .status-form,
    .delete-form {
        margin: 0
    }

    .status-select {
        padding: 8px 10px;
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 12px;
        background: rgba(255, 255, 255, .45);
        color: var(--text-main);
        outline: none;
        cursor: pointer
    }

    .status-select:focus {
        border-color: var(--primary-color)
    }

    .delete-btn {
        width: 38px;
        height: 38px;
        padding: 0;
        border: 1px solid rgba(255, 82, 82, .25);
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 82, 82, .15);
        color: #c62828;
        cursor: pointer;
        transition: var(--transition-smooth);
    }

    .delete-btn svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
        display: block;
    }

    .delete-btn:hover {
        background: rgba(255, 82, 82, .3);
        transform: translateY(-2px);
    }

    .empty-reservations {
        padding: 70px 20px;
        text-align: center;
        color: var(--text-muted)
    }

    .empty-reservations i {
        display: block;
        margin-bottom: 15px;
        font-size: 3rem;
        opacity: .5
    }

    .empty-reservations h3 {
        margin-bottom: 8px;
        color: var(--text-main)
    }

    .reservation-search {
        position: relative;
        width: 330px;
        max-width: 100%
    }

    .reservation-search i {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none
    }

    .reservation-search input {
        width: 100%;
        height: 44px;
        padding: 0 42px 0 15px;
        border: 1px solid rgba(255, 255, 255, .55);
        border-radius: 14px;
        background: rgba(255, 255, 255, .45);
        color: var(--text-main);
        font-family: inherit;
        outline: none;
        transition: var(--transition-smooth)
    }

    .reservation-search input::placeholder {
        color: var(--text-muted)
    }

    .reservation-search input:focus {
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, .65);
        box-shadow: 0 0 0 3px rgba(119, 221, 119, .12)
    }

    .reservation-search input::-webkit-search-cancel-button {
        cursor: pointer
    }

    .no-search-result td {
        text-align: center !important;
        color: var(--text-muted);
        padding: 30px !important
    }

    .edit-reservation-btn {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(119, 221, 119, .16);
        color: var(--primary-hover);
        text-decoration: none;
        border: 1px solid rgba(119, 221, 119, .3);
        transition: var(--transition-smooth);
    }

    .edit-reservation-btn svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
        display: block;
    }

    .edit-reservation-btn:hover {
        background: var(--primary-color);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(119, 221, 119, .3);
    }

    @media(max-width:768px) {
        .reservation-header {
            align-items: stretch
        }

        .reservation-search {
            width: 100%
        }
    }

    @media(max-width:768px) {
        .reservation-header {
            flex-direction: column;
            align-items: flex-start
        }

        .reservation-table-card {
            padding: 15px
        }
    }
</style>

<main class="admin-dashboard reservation-page">
    <div class="reservation-header">
        <div>
            <h1>مدیریت رزروها</h1>
            <p>مشاهده و مدیریت نوبت‌های ثبت‌شده مشتریان</p>
        </div>
        <div class="reservation-count"><?= count($reservations) ?> رزرو</div>
        <div class="reservation-search">
            <i class="fas fa-search"></i>
            <input type="search" id="reservationSearch" placeholder="جستجوی مشتری، تلفن، خدمت، متخصص..." autocomplete="off">
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="reservation-message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="reservation-table-card">
        <div class="reservation-table-wrapper">
            <?php if (!empty($reservations)): ?>
                <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>مشتری</th>
                            <th>شماره تماس</th>
                            <th>خدمت</th>
                            <th>متخصص</th>
                            <th>تاریخ</th>
                            <th>ساعت</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $index => $reservation): ?>
                            <?php $status = $reservation['status'] ?? 'pending'; ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="customer-name"><?= htmlspecialchars($reservation['fullname'] ?? 'کاربر حذف‌شده') ?></span></td>
                                <td><span class="customer-phone"><?= htmlspecialchars($reservation['phone'] ?? '---') ?></span></td>
                                <td><?= htmlspecialchars($reservation['service_name'] ?? '---') ?></td>
                                <td>
                                    <?php if (!empty($reservation['staff_name'])): ?>
                                        <span class="reservation-staff"><?= htmlspecialchars($reservation['staff_name']) ?></span>
                                    <?php else: ?>
                                        <span class="reservation-staff">انتخاب نشده</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="reservation-date"><?= htmlspecialchars($reservation['res_date'] ?? '---') ?></span></td>
                                <td><span class="reservation-time"><?= htmlspecialchars($reservation['res_time'] ?? '---') ?></span></td>
                                <td>
                                    <?php if ($status === 'confirmed'): ?>
                                        <span class="reservation-status confirmed">تایید شده</span>
                                    <?php elseif ($status === 'cancelled'): ?>
                                        <span class="reservation-status cancelled">لغو شده</span>
                                    <?php else: ?>
                                        <span class="reservation-status pending">در انتظار</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="reservation-actions">
                                        <a
                                            href="edit-reservation.php?id=<?= (int)$reservation['id'] ?>"
                                            class="edit-reservation-btn"
                                            title="ویرایش رزرو">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0 0-3L17.5 5.5a2.1 2.1 0 0 0-3 0L4 16v4zM13.5 7.5l3 3" />
                                            </svg>
                                        </a>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>در انتظار</option>
                                                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>تایید شده</option>
                                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>لغو شده</option>
                                            </select>
                                        </form>
                                        <form method="POST" class="delete-form" onsubmit="return confirm('آیا از حذف این رزرو مطمئن هستید؟');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
                                            <button type="submit" class="delete-btn" title="حذف رزرو">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-reservations">
                    <i class="far fa-calendar-times"></i>
                    <h3>هنوز رزروی ثبت نشده است</h3>
                    <p>وقتی مشتری نوبتی ثبت کند، اطلاعات آن در این قسمت نمایش داده می‌شود.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const search = document.getElementById('reservationSearch');
        const table = document.querySelector('.reservation-table');
        if (!search || !table) return;

        const tbody = table.querySelector('tbody');
        const rows = [...tbody.querySelectorAll('tr')];
        const dataRows = rows.filter(row => row.querySelectorAll('td').length === 9);

        const noResult = document.createElement('tr');
        noResult.className = 'no-search-result';
        noResult.style.display = 'none';
        noResult.innerHTML = '<td colspan="9">رزروی با این مشخصات پیدا نشد.</td>';
        tbody.appendChild(noResult);

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let found = 0;

            dataRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = !query || text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) found++;
            });

            noResult.style.display = found === 0 ? 'table-row' : 'none';
        });
    });
</script>

<?php require_once 'includes/footer-admin.php'; ?>