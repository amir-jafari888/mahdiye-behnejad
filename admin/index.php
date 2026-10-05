<?php
require_once '../partials/db.php';
require_once 'includes/header-admin.php';

$admin_name = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'خانم لطفی';

try {
    $stmtHero = $pdo->query("SELECT COUNT(*) FROM hero_slides");
    $hero_count = $stmtHero->fetchColumn();
} catch (PDOException $e) {
    $hero_count = 0;
}

try {
    $stmtServices = $pdo->query("SELECT COUNT(*) FROM services");
    $services_count = $stmtServices->fetchColumn();
} catch (PDOException $e) {
    $services_count = 0;
}

try {
    $stmtGallery = $pdo->query("SELECT COUNT(*) FROM gallery");
    $gallery_count = $stmtGallery->fetchColumn();
} catch (PDOException $e) {
    $gallery_count = 0;
}

try {
    $stmtStaff = $pdo->query("SELECT COUNT(*) FROM staff WHERE is_active=1");
    $staff_count = $stmtStaff->fetchColumn();
} catch (PDOException $e) {
    $staff_count = 0;
}

$reservations = [];

try {
    $stmtReservations = $pdo->query("
        SELECT
            r.id,
            u.fullname,
            u.phone,
            r.service_name,
            r.res_date,
            r.res_time,
            r.status,
            s.name AS staff_name
        FROM reservations AS r
        LEFT JOIN users AS u ON r.user_id=u.id
        LEFT JOIN staff AS s ON r.staff_id=s.id
        ORDER BY r.id DESC
        LIMIT 100
    ");
    $reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservations = [];
}
?>

<main class="admin-dashboard">

    <div class="welcome-banner">
        <div>
            <h1>خوش آمدید، <span><?= htmlspecialchars($admin_name) ?></span> عزیز 👋</h1>
            <p>خلاصه وضعیت و رزروهای اخیر مجموعه Mahdiye Beauty</p>
        </div>
        <div class="quick-date">
            <i class="far fa-calendar-alt"></i>
            امروز: <?= date('Y/m/d') ?>
        </div>
    </div>

    <div class="bento-grid">
        <div class="card">
            <div class="card-content-wrapper">
                <div>
                    <h3>اسلایدر هیرو</h3>
                    <p class="stat-number"><?= $hero_count ?></p>
                </div>
                <i class="fas fa-images card-icon"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-content-wrapper">
                <div>
                    <h3>خدمات فعال</h3>
                    <p class="stat-number"><?= $services_count ?></p>
                </div>
                <i class="fas fa-spa card-icon"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-content-wrapper">
                <div>
                    <h3>تصاویر گالری</h3>
                    <p class="stat-number"><?= $gallery_count ?></p>
                </div>
                <i class="fas fa-photo-video card-icon"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-content-wrapper">
                <div>
                    <h3>کادر حرفه‌ای</h3>
                    <p class="stat-number"><?= $staff_count ?></p>
                </div>
                <i class="fas fa-user-tie card-icon"></i>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <section class="crud-container">

            <div class="crud-header">
                <div>
                    <h2>آخرین رزروهای مشتریان</h2>
                    <span class="badge">۱۰۰ رزرو اخیر</span>
                </div>

                <div class="reservation-search">
                    <i class="fas fa-search"></i>
                    <input
                        type="search"
                        id="reservationSearch"
                        placeholder="جستجوی مشتری، تلفن، خدمت، متخصص..."
                        autocomplete="off">
                </div>
            </div>

            <div class="table-wrapper">
                <table id="reservationsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام مشتری</th>
                            <th>شماره تماس</th>
                            <th>نوع خدمت</th>
                            <th>متخصص</th>
                            <th>تاریخ</th>
                            <th>ساعت</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>

                    <tbody id="reservationsBody">
                        <?php if (!empty($reservations)): ?>

                            <?php foreach ($reservations as $index => $reservation): ?>

                                <?php $status = $reservation['status'] ?? 'pending'; ?>

                                <tr class="fade-in reservation-row">
                                    <td><?= $index + 1 ?></td>

                                    <td>
                                        <?= htmlspecialchars($reservation['fullname'] ?? 'نامشخص') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['phone'] ?? '---') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['service_name'] ?? '---') ?>
                                    </td>

                                    <td>
                                        <span class="dashboard-staff-name">
                                            <?= htmlspecialchars($reservation['staff_name'] ?? 'انتخاب نشده') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['res_date'] ?? '---') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['res_time'] ?? '---') ?>
                                    </td>

                                    <td>
                                        <?php if ($status === 'confirmed' || $status === 'تایید شده'): ?>
                                            <span class="status-badge status-active">تایید شده</span>
                                        <?php elseif ($status === 'cancelled' || $status === 'لغو شده'): ?>
                                            <span class="status-badge status-inactive">لغو شده</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">در انتظار</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            <tr id="noSearchResult" style="display:none">
                                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem">
                                    رزروی با این مشخصات پیدا نشد.
                                </td>
                            </tr>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem">
                                    هنوز هیچ رزروی ثبت نشده است.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </div>

</main>

<style>
    .reservation-search {
        position: relative;
        width: min(100%, 360px);
        margin-top: 15px
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
        outline: none;
        transition: var(--transition-smooth);
        font-family: inherit
    }

    .reservation-search input::placeholder {
        color: var(--text-muted)
    }

    .reservation-search input:focus {
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, .65);
        box-shadow: 0 0 0 3px rgba(119, 221, 119, .12)
    }

    .dashboard-staff-name {
        display: inline-block;
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap
    }

    .crud-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap
    }

    @media(max-width:768px) {
        .crud-header {
            align-items: stretch;
            flex-direction: column
        }

        .reservation-search {
            width: 100%;
            max-width: none
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const search = document.getElementById('reservationSearch');
        const rows = document.querySelectorAll('.reservation-row');
        const noResult = document.getElementById('noSearchResult');

        if (!search) return;

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = !query || text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            if (noResult) noResult.style.display = visible === 0 ? 'table-row' : 'none';
        });
    });
</script>

<?php require_once 'includes/footer-admin.php'; ?>