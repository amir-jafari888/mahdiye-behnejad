<?php
session_start();
if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit;
}
$host = 'localhost';
$db = 'mahdiye_beauty';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    die("خطا در اتصال به دیتابیس.");
}
$stmtUser = $pdo->prepare("SELECT id,fullname,phone FROM users WHERE id=? LIMIT 1");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    session_destroy();
    header("Location: user-login.php");
    exit;
}
$stmtServices = $pdo->query("SELECT id,name,description,price,image_path,category FROM services ORDER BY id DESC");
$services = $stmtServices->fetchAll();
$stmtReservations = $pdo->prepare("SELECT r.id,r.service_name,r.res_date,r.res_time,r.status,r.created_at,s.name AS staff_name FROM reservations r LEFT JOIN staff s ON s.id=r.staff_id WHERE r.user_id=? ORDER BY r.res_date DESC,r.res_time DESC LIMIT 10");
$stmtReservations->execute([$_SESSION['user_id']]);
$userReservations = $stmtReservations->fetchAll();
$categories = [];
foreach ($services as $service) {
    $category = trim($service['category'] ?? '');
    if ($category === '') $category = 'سایر خدمات';
    if (!in_array($category, $categories, true)) $categories[] = $category;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>رزرو نوبت | Mahdiye Beauty</title>
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon.ico" sizes="any">
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon-96x96.png" type="image/png" sizes="96x96">
    <link rel="apple-touch-icon" href="/mahdiye-benejad/assets/image/apple-touch-icon.png">
    <link rel="manifest" href="/mahdiye-benejad/assets/image/site.webmanifest">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <link rel="stylesheet" href="./assets/css/user-rexevation.css">
</head>

<body class="user-body">
    <header class="topbar">
        <div class="brand">
            <div class="brand-logo">MB</div>
            <div class="brand-text"><strong>Mahdiye Beauty</strong><span>سیستم رزرو آنلاین</span></div>
        </div>
        <div class="user-area">
            <div class="user-name">سلام، <?= htmlspecialchars($currentUser['fullname']) ?> 👋</div>
            <div class="user-actions">
                <a href="change-password.php" class="change-password-btn">تغییر رمز</a>
                <a href="logout-user.php" class="logout-btn">خروج</a>
            </div>
        </div>
    </header>
    <main class="page">
        <section class="welcome">
            <div>
                <h1>وقتشه برای خودت وقت بذاری ✨</h1>
                <p>خدمات موردنظرت رو انتخاب کن و در چند مرحله ساده نوبتت رو رزرو کن.</p>
            </div>
            <div class="welcome-icon">✨</div>
        </section>
        <div class="booking-layout">
            <div class="booking-main">
                <section class="booking-card">
                    <div class="stepper">
                        <div class="step active" id="stepService">
                            <div class="step-number">1</div><span>انتخاب خدمت</span>
                        </div>
                        <div class="step" id="stepDate">
                            <div class="step-number">2</div><span>متخصص و زمان</span>
                        </div>
                        <div class="step" id="stepConfirm">
                            <div class="step-number">3</div><span>تأیید رزرو</span>
                        </div>
                    </div>
                    <section class="section">
                        <div class="section-heading">
                            <div class="section-title-reserve"><span>💆</span>
                                <h2>چه خدمتی می‌خوای؟</h2>
                            </div>
                            <div class="section-hint">یک مورد انتخاب کن</div>
                        </div>
                        <?php if (!empty($categories)): ?><div class="categories"><button type="button" class="category-btn active" data-category="all">همه</button><?php foreach ($categories as $category): ?><button type="button" class="category-btn" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></button><?php endforeach; ?></div><?php endif; ?>
                        <div class="services-grid-reserve" id="servicesGrid">
                            <?php if (empty($services)): ?><div class="empty-bookings">هنوز خدمتی ثبت نشده است.</div><?php else: foreach ($services as $service): $category = trim($service['category'] ?? '');
                                                                                                                            if ($category === '') $category = 'سایر خدمات'; ?>
                                    <button type="button" class="service-card-user" data-service-id="<?= intval($service['id']) ?>" data-service="<?= htmlspecialchars($service['name']) ?>" data-price="<?= intval($service['price']) ?>" data-category="<?= htmlspecialchars($category) ?>">
                                        <div class="service-name"><?= htmlspecialchars($service['name']) ?></div>
                                        <div class="service-description"><?= htmlspecialchars($service['description'] ?: 'خدمات تخصصی و باکیفیت سالن') ?></div>
                                        <div class="service-footer"><span class="service-price"><?= number_format(intval($service['price'])) ?> تومان</span><span class="service-select">انتخاب</span></div>
                                    </button>
                            <?php endforeach;
                                                                                                                    endif; ?>
                        </div>
                        <input type="hidden" id="selectedService"><input type="hidden" id="selectedPrice">
                    </section>
                    <section class="section staff-section" id="staffSection" hidden>
                        <div class="section-heading">
                            <div class="section-title-reserve"><span>👩‍💼</span>
                                <h2>متخصص موردنظرت رو انتخاب کن</h2>
                            </div>
                            <div class="section-hint">یک متخصص انتخاب کن</div>
                        </div>
                        <div class="staff-status" id="staffStatus">ابتدا خدمت موردنظر را انتخاب کن.</div>
                        <div class="staff-grid" id="staffGrid"></div>
                        <input type="hidden" id="selectedStaff">
                    </section>
                    <section class="section">
                        <div class="section-heading">
                            <div class="section-title"><span>📅</span>
                                <h2>چه روزی؟</h2>
                            </div>
                            <div class="section-hint">تا یک ماه آینده</div>
                        </div>
                        <div class="date-wrapper"><span class="date-icon">📅</span><input type="text" id="persianDateInput" class="date-input" placeholder="ابتدا متخصص را انتخاب کن" readonly disabled><input type="hidden" id="gregorianDate"></div>
                    </section>
                    <section class="section">
                        <div class="section-heading">
                            <div class="section-title"><span>🕐</span>
                                <h2>چه ساعتی؟</h2>
                            </div>
                            <div class="section-hint">ساعت آزاد را انتخاب کن</div>
                        </div>
                        <div class="time-status" id="timeStatus">ابتدا متخصص و تاریخ را انتخاب کن.</div>
                        <div class="time-grid" id="timeGrid">
                            <div class="loading-slots">برای نمایش ساعت‌ها متخصص و تاریخ را انتخاب کنید.</div>
                        </div>
                        <input type="hidden" id="selectedTime">
                    </section>
                </section>
                <section class="my-bookings">
                    <div class="section-heading">
                        <div class="section-title"><span>📋</span>
                            <h2>رزروهای من</h2>
                        </div>
                        <div class="section-hint">آخرین نوبت‌های شما</div>
                    </div>
                    <div class="booking-list">
                        <?php if (empty($userReservations)): ?><div class="empty-bookings">هنوز هیچ رزروی ثبت نکرده‌ای.<br>اولین نوبتت رو همین الان رزرو کن ✨</div>
                            <?php else: foreach ($userReservations as $reservation): $status = $reservation['status'] ?? 'pending';
                                $statusText = ['pending' => 'در انتظار تأیید', 'confirmed' => 'تأیید شده', 'cancelled' => 'لغو شده'];
                                $statusClass = ['pending' => 'status-pending', 'confirmed' => 'status-confirmed', 'cancelled' => 'status-cancelled']; ?>
                                <div class="reservation-row">
                                    <div class="reservation-service"><?= htmlspecialchars($reservation['service_name']) ?></div>
                                    <?php if (!empty($reservation['staff_name'])): ?><div class="reservation-staff">متخصص: <?= htmlspecialchars($reservation['staff_name']) ?></div><?php endif; ?>
                                    <div class="reservation-meta persian-reservation-date" data-date="<?= htmlspecialchars($reservation['res_date']) ?>"><?= htmlspecialchars($reservation['res_date']) ?></div>
                                    <div class="reservation-meta">ساعت <?= htmlspecialchars($reservation['res_time']) ?></div>
                                    <span class="status <?= $statusClass[$status] ?? 'status-pending' ?>"><?= htmlspecialchars($statusText[$status] ?? 'در انتظار') ?></span>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </section>
            </div>
            <aside class="booking-card summary">
                <div class="summary-header">
                    <h2>خلاصه رزرو</h2><span class="summary-badge">آنلاین</span>
                </div>
                <div class="summary-content" id="summaryContent">
                    <div class="summary-empty">هنوز چیزی انتخاب نکردی.<br><br>از خدمات شروع کن 👆</div>
                </div>
                <button type="button" class="book-btn" id="bookBtn" disabled>ادامه و تأیید رزرو</button>
            </aside>
        </div>
    </main>
    <div class="modal" id="confirmModal">
        <div class="modal-box">
            <div class="modal-icon">✨</div>
            <h3>تأیید نهایی رزرو</h3>
            <p>اطلاعات نوبتت رو یک بار بررسی کن.</p>
            <div class="confirm-details">
                <div class="confirm-row"><span>خدمت</span><strong id="confirmService">-</strong></div>
                <div class="confirm-row"><span>متخصص</span><strong id="confirmStaff">-</strong></div>
                <div class="confirm-row"><span>تاریخ</span><strong id="confirmDate">-</strong></div>
                <div class="confirm-row"><span>ساعت</span><strong id="confirmTime">-</strong></div>
                <div class="confirm-row"><span>مبلغ</span><strong id="confirmPrice">-</strong></div>
            </div>
            <div class="modal-actions"><button type="button" class="modal-btn modal-cancel" id="closeModal">ویرایش</button><button type="button" class="modal-btn modal-confirm" id="finalConfirm">ثبت نهایی رزرو</button></div>
        </div>
    </div>
    <div class="toast" id="toast"></div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker/dist/js/persian-datepicker.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const serviceCards = document.querySelectorAll(".service-card-user"),
                selectedService = document.getElementById("selectedService"),
                selectedPrice = document.getElementById("selectedPrice"),
                selectedTime = document.getElementById("selectedTime"),
                persianDateInput = document.getElementById("persianDateInput"),
                gregorianDate = document.getElementById("gregorianDate"),
                timeGrid = document.getElementById("timeGrid"),
                timeStatus = document.getElementById("timeStatus"),
                bookBtn = document.getElementById("bookBtn"),
                summaryContent = document.getElementById("summaryContent"),
                modal = document.getElementById("confirmModal"),
                toast = document.getElementById("toast"),
                staffSection = document.getElementById("staffSection"),
                staffGrid = document.getElementById("staffGrid"),
                staffStatus = document.getElementById("staffStatus"),
                selectedStaff = document.getElementById("selectedStaff");
            let bookingState = {
                serviceId: 0,
                service: "",
                price: 0,
                staffId: 0,
                staffName: "",
                date: "",
                persianDate: "",
                time: ""
            };

            function showToast(message, error = false) {
                toast.textContent = message;
                toast.classList.toggle("error", error);
                toast.classList.add("show");
                setTimeout(() => toast.classList.remove("show"), 3000);
            }

            function updateStepper() {
                const stepService = document.getElementById("stepService"),
                    stepDate = document.getElementById("stepDate"),
                    stepConfirm = document.getElementById("stepConfirm");
                [stepService, stepDate, stepConfirm].forEach(step => step.classList.remove("active", "done"));
                if (!bookingState.service) {
                    stepService.classList.add("active");
                } else if (!bookingState.staffId || !bookingState.date || !bookingState.time) {
                    stepService.classList.add("done");
                    stepDate.classList.add("active");
                } else {
                    stepService.classList.add("done");
                    stepDate.classList.add("done");
                    stepConfirm.classList.add("active");
                }
            }

            function escapeHtml(value) {
                const div = document.createElement("div");
                div.textContent = value ?? "";
                return div.innerHTML;
            }

            function staffImageUrl(path) {
                const value = String(path || "").trim();
                if (!value) return "";
                if (/^(https?:)?\/\//i.test(value)) return value;
                return "/mahdiye-benejad/" + value.replace(/^\/+/, "");
            }
            async function loadStaff(serviceId) {
                staffSection.hidden = false;
                staffStatus.textContent = "در حال دریافت متخصص‌ها...";
                staffGrid.innerHTML = '<div class="loading-slots">در حال بررسی متخصص‌های این خدمت...</div>';
                try {
                    const response = await fetch("ajax-booking.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
                        },
                        body: new URLSearchParams({
                            action: "get_staff",
                            service_id: serviceId,
                            csrf_token: <?= json_encode($_SESSION['user_csrf_token']) ?>,
                        })
                    });
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        console.error("STAFF AJAX RESPONSE:", responseText);
                        throw new Error("پاسخ نامعتبر از سرور");
                    }
                    if (!data.success) {
                        staffGrid.innerHTML = "";
                        staffStatus.textContent = data.message || "متخصصی برای این خدمت پیدا نشد.";
                        return;
                    }
                    renderStaff(data.staff || []);
                } catch (error) {
                    console.error(error);
                    staffGrid.innerHTML = "";
                    staffStatus.textContent = "ارتباط با سرور برقرار نشد.";
                }
            }

            function renderStaff(staffList) {
                staffGrid.innerHTML = "";
                if (!staffList.length) {
                    staffStatus.textContent = "برای این خدمت متخصص فعالی ثبت نشده است.";
                    staffGrid.innerHTML = '<div class="loading-slots">متخصصی برای این خدمت موجود نیست.</div>';
                    return;
                }
                staffStatus.textContent = "متخصص موردنظرت را انتخاب کن.";
                staffList.forEach(staff => {
                    const card = document.createElement("button");
                    card.type = "button";
                    card.className = "staff-card";
                    card.dataset.staffId = staff.id;
                    const image = staff.image_path ? `<img src="${staffImageUrl(staff.image_path)}" alt="${escapeHtml(staff.name)}">` : '<div class="staff-placeholder">👩</div>';
                    card.innerHTML = `<div class="staff-image">${image}</div><div class="staff-info"><strong>${escapeHtml(staff.name)}</strong><span>${escapeHtml(staff.specialty)}</span></div>`;
                    card.addEventListener("click", function() {
                        document.querySelectorAll(".staff-card").forEach(item => item.classList.remove("selected"));
                        this.classList.add("selected");
                        bookingState.staffId = Number(this.dataset.staffId);
                        bookingState.staffName = staff.name;
                        selectedStaff.value = bookingState.staffId;
                        bookingState.date = "";
                        bookingState.persianDate = "";
                        bookingState.time = "";
                        persianDateInput.value = "";
                        gregorianDate.value = "";
                        selectedTime.value = "";
                        persianDateInput.disabled = false;
                        persianDateInput.placeholder = "انتخاب تاریخ";
                        timeGrid.innerHTML = '<div class="loading-slots">تاریخ را انتخاب کنید.</div>';
                        timeStatus.textContent = "ابتدا تاریخ را انتخاب کن.";
                        updateSummary();
                        updateStepper();
                    });
                    staffGrid.appendChild(card);
                });
            }
            serviceCards.forEach(card => {
                card.addEventListener("click", function() {
                    serviceCards.forEach(item => item.classList.remove("selected"));
                    this.classList.add("selected");
                    bookingState.serviceId = Number(this.dataset.serviceId || 0);
                    bookingState.service = this.dataset.service;
                    bookingState.price = Number(this.dataset.price || 0);
                    bookingState.staffId = 0;
                    bookingState.staffName = "";
                    bookingState.date = "";
                    bookingState.persianDate = "";
                    bookingState.time = "";
                    selectedService.value = bookingState.service;
                    selectedPrice.value = bookingState.price;
                    selectedStaff.value = "";
                    persianDateInput.value = "";
                    persianDateInput.disabled = true;
                    persianDateInput.placeholder = "ابتدا متخصص را انتخاب کن";
                    gregorianDate.value = "";
                    selectedTime.value = "";
                    timeGrid.innerHTML = '<div class="loading-slots">ابتدا متخصص را انتخاب کنید.</div>';
                    timeStatus.textContent = "ابتدا متخصص را انتخاب کن.";
                    updateSummary();
                    updateStepper();
                    loadStaff(bookingState.serviceId);
                });
            });
            const categoryButtons = document.querySelectorAll(".category-btn");
            categoryButtons.forEach(button => {
                button.addEventListener("click", function() {
                    categoryButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    const category = this.dataset.category;
                    serviceCards.forEach(card => {
                        card.style.display = category === "all" || card.dataset.category === category ? "" : "none";
                    });
                });
            });
            const today = new persianDate(),
                maxDate = new persianDate().add("month", 1);
            $("#persianDateInput").persianDatepicker({
                format: "YYYY/MM/DD",
                autoClose: true,
                initialValue: false,
                responsive: true,
                observer: true,
                minDate: today.valueOf(),
                maxDate: maxDate.valueOf(),
                calendar: {
                    persian: {
                        locale: "fa",
                        leapYearMode: "astronomical"
                    }
                },
                navigator: {
                    enabled: true
                },
                toolbox: {
                    enabled: true,
                    todayButton: {
                        enabled: true,
                        text: {
                            fa: "امروز",
                            en: "Today"
                        }
                    }
                },
                onSelect: function(unix) {
                    if (!bookingState.staffId) {
                        showToast("ابتدا متخصص را انتخاب کن.", true);
                        return;
                    }
                    const selectedPersianDate = new persianDate(unix).format("YYYY/MM/DD"),
                        timestamp = Number(unix);
                    if (!Number.isFinite(timestamp)) {
                        showToast("تاریخ انتخاب‌شده قابل تبدیل نیست.", true);
                        return;
                    }
                    const jsDate = new Date(timestamp > 100000000000 ? timestamp : timestamp * 1000),
                        parts = new Intl.DateTimeFormat("en-CA", {
                            timeZone: "Asia/Tehran",
                            year: "numeric",
                            month: "2-digit",
                            day: "2-digit"
                        }).formatToParts(jsDate),
                        dateParts = {};
                    parts.forEach(part => {
                        if (part.type !== "literal") dateParts[part.type] = part.value;
                    });
                    const selectedGregorianDate = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
                    bookingState.date = selectedGregorianDate;
                    bookingState.persianDate = selectedPersianDate;
                    persianDateInput.value = selectedPersianDate;
                    gregorianDate.value = selectedGregorianDate;
                    bookingState.time = "";
                    selectedTime.value = "";
                    updateSummary();
                    updateStepper();
                    loadTimeSlots(selectedGregorianDate);
                }
            });
            async function loadTimeSlots(date) {
                if (!bookingState.staffId) {
                    timeGrid.innerHTML = '<div class="loading-slots">ابتدا متخصص را انتخاب کنید.</div>';
                    timeStatus.textContent = "ابتدا متخصص را انتخاب کن.";
                    return;
                }
                timeGrid.innerHTML = '<div class="loading-slots">در حال بررسی ساعت‌های آزاد...</div>';
                timeStatus.textContent = "در حال بررسی ظرفیت این روز...";
                try {
                    const response = await fetch("ajax-booking.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
                        },
                        body: new URLSearchParams({
                            action: "get_slots",
                            date: date,
                            service_id: bookingState.serviceId,
                            staff_id: bookingState.staffId,
                            csrf_token: <?= json_encode($_SESSION['user_csrf_token']) ?>
                        })
                    });
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        console.error("AJAX RESPONSE:", responseText);
                        throw new Error("پاسخ نامعتبر از سرور");
                    }
                    if (!data.success) {
                        timeGrid.innerHTML = "";
                        timeStatus.textContent = data.message || "خطا در دریافت ساعت‌ها.";
                        return;
                    }
                    renderTimeSlots(data.slots || []);
                } catch (error) {
                    console.error(error);
                    timeGrid.innerHTML = "";
                    timeStatus.textContent = "ارتباط با سرور برقرار نشد.";
                }
            }

            function renderTimeSlots(slots) {
                timeGrid.innerHTML = "";
                if (!slots.length) {
                    timeGrid.innerHTML = '<div class="loading-slots">برای این روز ساعت آزادی باقی نمانده است.</div>';
                    timeStatus.textContent = "ساعت آزاد پیدا نشد.";
                    bookingState.time = "";
                    selectedTime.value = "";
                    updateSummary();
                    return;
                }
                timeStatus.textContent = "یکی از ساعت‌های آزاد را انتخاب کن.";
                slots.forEach(time => {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = "time-slot";
                    button.dataset.time = time;
                    button.textContent = time;
                    button.addEventListener("click", function() {
                        document.querySelectorAll(".time-slot").forEach(item => item.classList.remove("selected"));
                        this.classList.add("selected");
                        bookingState.time = this.dataset.time;
                        selectedTime.value = bookingState.time;
                        updateSummary();
                        updateStepper();
                    });
                    timeGrid.appendChild(button);
                });
            }

            function updateSummary() {
                const hasService = bookingState.service !== "",
                    hasStaff = bookingState.staffId > 0,
                    hasDate = bookingState.date !== "",
                    hasTime = bookingState.time !== "";
                if (!hasService) {
                    summaryContent.innerHTML = '<div class="summary-empty">هنوز چیزی انتخاب نکردی.<br><br>از خدمات شروع کن 👆</div>';
                    bookBtn.disabled = true;
                    return;
                }
                summaryContent.innerHTML = `<div class="summary-item"><span class="summary-label">خدمت</span><strong class="summary-value">${escapeHtml(bookingState.service)||"انتخاب نشده"}</strong></div><div class="summary-item"><span class="summary-label">متخصص</span><strong class="summary-value">${escapeHtml(bookingState.staffName)||"انتخاب نشده"}</strong></div><div class="summary-item"><span class="summary-label">تاریخ</span><strong class="summary-value">${escapeHtml(bookingState.persianDate)||"انتخاب نشده"}</strong></div><div class="summary-item"><span class="summary-label">ساعت</span><strong class="summary-value">${escapeHtml(bookingState.time)||"انتخاب نشده"}</strong></div><div class="summary-item"><span class="summary-label">مبلغ</span><strong class="summary-value">${bookingState.price?Number(bookingState.price).toLocaleString("fa-IR"):"-"} تومان</strong></div>`;
                bookBtn.disabled = !(hasService && hasStaff && hasDate && hasTime);
            }
            bookBtn.addEventListener("click", function() {
                if (!bookingState.service || !bookingState.staffId || !bookingState.date || !bookingState.time) {
                    showToast("لطفاً خدمت، متخصص، تاریخ و ساعت را انتخاب کن.", true);
                    return;
                }
                document.getElementById("confirmService").textContent = bookingState.service;
                document.getElementById("confirmStaff").textContent = bookingState.staffName;
                document.getElementById("confirmDate").textContent = bookingState.persianDate;
                document.getElementById("confirmTime").textContent = bookingState.time;
                document.getElementById("confirmPrice").textContent = Number(bookingState.price).toLocaleString("fa-IR") + " تومان";
                modal.classList.add("show");
            });
            document.getElementById("closeModal").addEventListener("click", () => modal.classList.remove("show"));
            modal.addEventListener("click", event => {
                if (event.target === modal) modal.classList.remove("show");
            });
            document.getElementById("finalConfirm").addEventListener("click", async function() {
                const button = this;
                if (!bookingState.serviceId || !bookingState.staffId || !bookingState.date || !bookingState.time) {
                    showToast("اطلاعات رزرو کامل نیست.", true);
                    return;
                }
                button.disabled = true;
                button.textContent = "در حال ثبت...";
                try {
                    const response = await fetch("ajax-booking.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
                        },
                        body: new URLSearchParams({
                            action: "create_booking",
                            service_id: bookingState.serviceId,
                            staff_id: bookingState.staffId,
                            date: bookingState.date,
                            time: bookingState.time,
                            csrf_token: <?= json_encode($_SESSION['user_csrf_token']) ?>,
                        })
                    });
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        console.error("BOOKING RESPONSE:", responseText);
                        throw new Error("پاسخ نامعتبر از سرور");
                    }
                    if (!data.success) {
                        showToast(data.message || "رزرو ثبت نشد.", true);
                        return;
                    }
                    modal.classList.remove("show");
                    showToast("رزرو شما با موفقیت ثبت شد ✨");
                    setTimeout(() => window.location.reload(), 1500);
                } catch (error) {
                    console.error(error);
                    showToast("خطا در ارتباط با سرور.", true);
                } finally {
                    button.disabled = false;
                    button.textContent = "ثبت نهایی رزرو";
                }
            });
            document.querySelectorAll(".persian-reservation-date").forEach(element => {
                const date = element.dataset.date;
                if (!date) return;
                try {
                    element.textContent = new persianDate(date).toCalendar("persian").format("YYYY/MM/DD");
                } catch (error) {
                    console.log(error);
                }
            });
            updateSummary();
            updateStepper();
        });
    </script>
</body>

</html>