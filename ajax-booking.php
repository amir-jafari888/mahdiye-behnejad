<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once __DIR__ . '/partials/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'روش درخواست نامعتبر است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['user_csrf_token'], $csrfToken)
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'درخواست نامعتبر است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'لطفاً ابتدا وارد حساب کاربری شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';

$businessHours = [
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00'
];

function validateBookingDate(string $date): bool
{
    $date = trim($date);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    [$year, $month, $day] = array_map('intval', explode('-', $date));

    if (!checkdate($month, $day, $year)) {
        return false;
    }

    $bookingDate = DateTime::createFromFormat('Y-m-d', $date);
    $today = new DateTime('today');

    if (!$bookingDate) {
        return false;
    }

    return $bookingDate >= $today;
}


function getValidStaff(PDO $pdo, int $staffId, int $serviceId): ?array
{
    $stmt = $pdo->prepare("
        SELECT s.id,s.name,s.specialty
        FROM staff s
        INNER JOIN staff_services ss ON ss.staff_id=s.id
        WHERE s.id=?
        AND ss.service_id=?
        AND s.is_active=1
        LIMIT 1
    ");

    $stmt->execute([$staffId, $serviceId]);
    $staff = $stmt->fetch();

    return $staff ?: null;
}

if ($action === 'get_staff') {

    $serviceId = (int)($_POST['service_id'] ?? 0);

    if ($serviceId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'خدمت انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtService = $pdo->prepare("
        SELECT id, name, category
        FROM services
        WHERE id = ?
        LIMIT 1
    ");

    $stmtService->execute([$serviceId]);
    $serviceRow = $stmtService->fetch();

    if (!$serviceRow) {
        echo json_encode([
            'success' => false,
            'message' => 'خدمت انتخاب‌شده پیدا نشد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtStaff = $pdo->prepare("
    SELECT s.id,s.name,s.specialty,s.image_path,s.bio
    FROM staff s
    INNER JOIN staff_services ss ON ss.staff_id=s.id
    WHERE ss.service_id=?
    AND s.is_active=1
    ORDER BY s.sort_order ASC,s.id ASC
");

    $stmtStaff->execute([$serviceId]);
    $staff = $stmtStaff->fetchAll();

    echo json_encode([
        'success' => true,
        'service_id' => $serviceRow['id'],
        'service' => $serviceRow['name'],
        'category' => $serviceRow['category'],
        'staff' => $staff
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($action === 'get_slots') {

    $date = trim($_POST['date'] ?? '');
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);

    if (!validateBookingDate($date)) {
        echo json_encode([
            'success' => false,
            'message' => 'تاریخ انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($staffId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'لطفاً ابتدا متخصص را انتخاب کنید.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($serviceId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'سرویس انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtService = $pdo->prepare("
        SELECT category
        FROM services
        WHERE id = ?
        LIMIT 1
    ");

    $stmtService->execute([$serviceId]);
    $serviceRow = $stmtService->fetch();

    if (!$serviceRow) {
        echo json_encode([
            'success' => false,
            'message' => 'سرویس انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $staffRow = getValidStaff($pdo, $staffId, $serviceId);

    if (!$staffRow) {
        echo json_encode([
            'success' => false,
            'message' => 'این متخصص برای سرویس انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT res_time
        FROM reservations
        WHERE staff_id = ?
        AND res_date = ?
        AND status != 'cancelled'
    ");

    $stmt->execute([
        $staffId,
        $date
    ]);

    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $bookedSlots = array_map(
        static fn($time) => substr((string)$time, 0, 5),
        $bookedSlots
    );

    $availableSlots = [];

    foreach ($businessHours as $hour) {
        if (!in_array($hour, $bookedSlots, true)) {
            $availableSlots[] = $hour;
        }
    }

    echo json_encode([
        'success' => true,
        'slots' => $availableSlots
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($action === 'create_booking') {

    $userId = (int)$_SESSION['user_id'];
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');

    if ($serviceId <= 0 || $staffId <= 0 || $date === '' || $time === '') {
        echo json_encode([
            'success' => false,
            'message' => 'اطلاعات رزرو کامل نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!validateBookingDate($date)) {
        echo json_encode([
            'success' => false,
            'message' => 'تاریخ انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (
        !preg_match('/^\d{2}:\d{2}$/', $time) ||
        !in_array($time, $businessHours, true)
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'ساعت انتخاب‌شده معتبر نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($date === date('Y-m-d')) {
        $currentTime = date('H:i');

        if ($time <= $currentTime) {
            echo json_encode([
                'success' => false,
                'message' => 'این ساعت گذشته است. لطفاً ساعت دیگری انتخاب کنید.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $stmtService = $pdo->prepare("
        SELECT id, name, category
        FROM services
        WHERE id = ?
        LIMIT 1
    ");

    $stmtService->execute([$serviceId]);
    $serviceRow = $stmtService->fetch();

    if (!$serviceRow) {
        echo json_encode([
            'success' => false,
            'message' => 'خدمت انتخاب‌شده پیدا نشد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $staffRow = getValidStaff($pdo, $staffId, $serviceId);

    if (!$staffRow) {
        echo json_encode([
            'success' => false,
            'message' => 'متخصص انتخاب‌شده با این خدمت مطابقت ندارد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("
            SELECT id
            FROM reservations
            WHERE staff_id = ?
            AND res_date = ?
            AND res_time = ?
            AND status != 'cancelled'
            LIMIT 1
            FOR UPDATE
        ");

        $stmtCheck->execute([
            $staffId,
            $date,
            $time
        ]);

        if ($stmtCheck->fetch()) {
            $pdo->rollBack();

            echo json_encode([
                'success' => false,
                'message' => 'متأسفانه این ساعت برای این متخصص قبلاً رزرو شده است. یک ساعت دیگر انتخاب کنید.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO reservations
            (
                user_id,
                staff_id,
                service_name,
                res_date,
                res_time,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        $stmtInsert->execute([
            $userId,
            $staffId,
            $serviceRow['name'],
            $date,
            $time
        ]);

        $reservationId = $pdo->lastInsertId();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'رزرو با موفقیت ثبت شد.',
            'reservation_id' => $reservationId,
            'staff' => $staffRow['name']
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Booking insert failed: ' . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'ثبت رزرو انجام نشد. دوباره تلاش کنید.'
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'درخواست نامعتبر است.'
], JSON_UNESCAPED_UNICODE);

exit;
