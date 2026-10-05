<?php

require_once __DIR__ . '/partials/db.php';

$serviceId = filter_input(INPUT_GET, 'service', FILTER_VALIDATE_INT);

if (!$serviceId) {
    http_response_code(404);
    exit('سرویس مورد نظر پیدا نشد.');
}

$stmt = $pdo->prepare("
    SELECT id, name, description, price, category, image_path
    FROM services
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $serviceId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    http_response_code(404);
    exit('سرویس مورد نظر پیدا نشد.');
}

$galleryStmt = $pdo->prepare("
    SELECT id, image_path, title, description, tag
    FROM gallery
    WHERE service_id = ?
    ORDER BY created_at DESC
");
$galleryStmt->execute([$serviceId]);
$galleryItems = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);

$staffStmt = $pdo->prepare("
    SELECT s.id, s.name, s.specialty, s.image_path, s.bio
    FROM staff s
    INNER JOIN staff_services ss ON ss.staff_id = s.id
    WHERE ss.service_id = ?
      AND s.is_active = 1
    ORDER BY s.sort_order ASC, s.id ASC
");
$staffStmt->execute([$serviceId]);
$staffItems = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$serviceName = e($service['name']);
$description = e($service['description'] ?? '');
$category = e($service['category'] ?? '');

// $price = (float)$service['price'];
$deposit = (float)$service['price'];

$image = $service['image_path'] ?? '';

$imageUrl = '';

if ($image) {
    $imageUrl = function_exists('site_asset_url')
        ? site_asset_url($image)
        : $image;
}

$pageTitle = $service['name'] . ' | سالن زیبایی مهدیه بهنژاد';
$pageDescription = $service['description'] ?: 'اطلاعات و جزئیات ' . $service['name'] . ' در سالن زیبایی مهدیه بهنژاد.';
?>

<?php require_once __DIR__ . '/partials/head.php'; ?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<style>

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .details-page {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 120px 20px 90px;
        text-align: center;
        overflow: hidden;
    }

    /* =========================================================
   SERVICE
========================================================= */

    .service-details {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin: 0 auto 110px;
        text-align: center;
    }

    /* =========================================================
   MAIN IMAGE
========================================================= */

    .service-details__media {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 92%;
        max-width: 1150px;
        min-height: 520px;
        padding: 12px;
        margin: 0 auto;
        overflow: hidden;
        border-radius: 36px;
        background: rgba(255, 255, 255, .30);
        border: 1px solid rgba(255, 255, 255, .72);
        box-shadow:
            0 30px 80px rgba(93, 64, 55, .13),
            inset 0 1px 0 rgba(255, 255, 255, .9);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
    }

    .service-details__media::before {
        content: "";
        position: absolute;
        top: -100px;
        right: -100px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(119, 221, 119, .12);
        filter: blur(30px);
        pointer-events: none;
    }

    .service-details__media::after {
        content: "";
        position: absolute;
        inset: 12px;
        border-radius: 27px;
        border: 1px solid rgba(255, 255, 255, .35);
        pointer-events: none;
    }

    .service-details__image {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        max-height: 700px;
        object-fit: cover;
        object-position: center;
        border-radius: 27px;
    }

    .service-details__image--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 500px;
        color: #777;
        background: rgba(255, 255, 255, .4);
        border-radius: 27px;
    }

    /* =========================================================
   SERVICE INFO
========================================================= */

    .service-details__content {
        position: relative;
        z-index: 5;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: min(760px, 88%);
        margin: -55px auto 0;
        padding: 38px 45px 42px;
        border-radius: 32px;
        background: rgba(255, 255, 255, .48);
        border: 1px solid rgba(255, 255, 255, .78);
        box-shadow:
            0 25px 65px rgba(93, 64, 55, .13),
            inset 0 1px 0 rgba(255, 255, 255, .9);
        backdrop-filter: blur(28px);
        -webkit-backdrop-filter: blur(28px);
        text-align: center;
    }

    .service-details__content::before {
        content: "";
        position: absolute;
        top: 0;
        left: 50%;
        width: 65px;
        height: 3px;
        border-radius: 20px;
        background: #77dd77;
        transform: translateX(-50%);
    }

    .service-details__category {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 18px;
        margin-bottom: 14px;
        border-radius: 50px;
        background: rgba(119, 221, 119, .14);
        border: 1px solid rgba(119, 221, 119, .30);
        color: #388e3c;
        font-size: .72rem;
        font-weight: 700;
    }

    .service-details__title {
        width: 100%;
        margin: 0 0 13px;
        color: #5d4037;
        font-size: clamp(2rem, 4vw, 3.1rem);
        font-weight: 800;
        line-height: 1.4;
    }

    .service-details__description {
        width: min(580px, 100%);
        margin: 0 auto;
        color: #746760;
        font-size: .94rem;
        line-height: 2.1;
    }

    /* =========================================================
   PRICE
========================================================= */

    .service-details__pricing {
        display: flex;
        justify-content: center;
        width: min(480px, 100%);
        margin: 27px auto 0;
    }

    .price-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 78px;
        padding: 13px 20px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .43);
        border: 1px solid rgba(255, 255, 255, .72);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .85);
    }

    .price-box--deposit {
        background: rgba(119, 221, 119, .14);
        border-color: rgba(119, 221, 119, .30);
    }

    .price-box__label {
        display: block;
        margin-bottom: 5px;
        color: #81736c;
        font-size: .7rem;
    }

    .price-box__value {
        color: #388e3c;
        font-size: 1.18rem;
        font-weight: 800;
    }

    .price-box__value small {
        margin-right: 4px;
        color: #6f625d;
        font-size: .65rem;
        font-weight: 500;
    }

    /* =========================================================
   CTA
========================================================= */

    .service-details__cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: min(480px, 100%);
        min-height: 59px;
        margin: 12px auto 0;
        padding: 14px 22px;
        border-radius: 19px;
        background: #77dd77;
        color: #fff;
        text-decoration: none;
        font-size: .88rem;
        font-weight: 800;
        box-shadow:
            0 14px 30px rgba(119, 221, 119, .23);
        transition:
            transform .3s ease,
            background .3s ease,
            box-shadow .3s ease;
    }

    .service-details__cta span {
        transition: transform .3s ease;
    }

    .service-details__cta:hover {
        background: #388e3c;
        color: #fff;
        transform: translateY(-3px);
        box-shadow:
            0 18px 35px rgba(56, 142, 60, .24);
    }

    .service-details__cta:hover span {
        transform: translateX(-5px);
    }

    /* =========================================================
   SECTION TITLES
========================================================= */

    .service-staff,
    .service-gallery {
        width: 100%;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }

    .service-staff {
        margin-bottom: 110px;
    }

    .service-staff__heading,
    .service-gallery__heading {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin-bottom: 42px;
        text-align: center;
    }

    .service-staff__heading span,
    .service-gallery__heading span {
        display: block;
        margin-bottom: 9px;
        color: #388e3c;
        font-size: .67rem;
        font-weight: 800;
        letter-spacing: 3px;
    }

    .service-staff__heading h2,
    .service-gallery__heading h2 {
        width: 100%;
        margin: 0;
        color: #5d4037;
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        font-weight: 800;
        line-height: 1.45;
    }

    /* =========================================================
   STAFF
========================================================= */

    .service-staff__grid {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        justify-content: center;
        gap: 25px;
        width: 100%;
        max-width: 1120px;
        margin: 0 auto;
    }

    .service-staff__card {
        flex: 0 1 330px;
        width: 330px;
        max-width: 100%;
        overflow: hidden;
        border-radius: 30px;
        background: rgba(255, 255, 255, .39);
        border: 1px solid rgba(255, 255, 255, .74);
        box-shadow:
            0 20px 50px rgba(93, 64, 55, .10),
            inset 0 1px 0 rgba(255, 255, 255, .88);
        backdrop-filter: blur(21px);
        -webkit-backdrop-filter: blur(21px);
        transition:
            transform .35s ease,
            box-shadow .35s ease;
    }

    .service-staff__card:hover {
        transform: translateY(-7px);
        box-shadow:
            0 28px 60px rgba(93, 64, 55, .15),
            inset 0 1px 0 rgba(255, 255, 255, .92);
    }

    .service-staff__image {
        display: block;
        width: 100%;
        height: 350px;
        object-fit: cover;
        object-position: center;
    }

    .service-staff__content {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 15px 23px;
        text-align: center;
    }

    .service-staff__content h3 {
        margin: 0 0 7px;
        color: #5d4037;
        font-size: 1.08rem;
        font-weight: 800;
    }

    .service-staff__content p {
        margin: 0;
        color: #388e3c;
        font-size: .8rem;
        line-height: 1.8;
    }

    .service-staff__content span {
        display: block;
        margin-top: 8px;
        color: #81736d;
        font-size: .73rem;
        line-height: 1.9;
    }

    .service-staff__empty {
        width: min(600px, 100%);
        margin: 0 auto;
        padding: 55px 20px;
        border-radius: 27px;
        background: rgba(255, 255, 255, .35);
        border: 1px solid rgba(255, 255, 255, .68);
        color: #777;
        box-shadow:
            0 18px 40px rgba(93, 64, 55, .08);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        text-align: center;
    }

    /* =========================================================
   GALLERY
========================================================= */

    .service-gallery__grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        width: 100%;
        max-width: 1120px;
        margin: 0 auto;
    }

    .service-gallery__item {
        position: relative;
        width: 100%;
        height: 300px;
        overflow: hidden;
        border-radius: 27px;
        background: rgba(255, 255, 255, .35);
        border: 1px solid rgba(255, 255, 255, .70);
        box-shadow:
            0 17px 40px rgba(93, 64, 55, .08),
            inset 0 1px 0 rgba(255, 255, 255, .8);
        backdrop-filter: blur(17px);
        -webkit-backdrop-filter: blur(17px);
    }

    .service-gallery__item:nth-child(1) {
        grid-column: span 2;
        height: 380px;
    }

    .service-gallery__item:nth-child(2) {
        grid-column: span 2;
        height: 380px;
    }

    .service-gallery__item img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform .6s ease;
    }

    .service-gallery__item:hover img {
        transform: scale(1.05);
    }

    .service-gallery__empty {
        grid-column: 1 / -1;
        width: 100%;
        padding: 55px 20px;
        border-radius: 27px;
        background: rgba(255, 255, 255, .35);
        border: 1px solid rgba(255, 255, 255, .68);
        color: #777;
        box-shadow:
            0 18px 40px rgba(93, 64, 55, .08);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    /* =========================================================
   TABLET
========================================================= */

    @media (max-width: 900px) {

        .details-page {
            padding: 100px 16px 75px;
        }

        .service-details__media {
            width: 94%;
            min-height: 460px;
        }

        .service-details__content {
            width: min(700px, 88%);
            margin-top: -45px;
            padding: 34px 30px 36px;
        }

        .service-staff__card {
            flex-basis: 310px;
            width: 310px;
        }

        .service-gallery__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .service-gallery__item,
        .service-gallery__item:nth-child(1),
        .service-gallery__item:nth-child(2) {
            grid-column: span 1;
            height: 300px;
        }
    }

    /* =========================================================
   MOBILE
========================================================= */

    @media (max-width: 600px) {

        .details-page {
            width: 100%;
            padding: 82px 10px 55px;
            overflow-x: hidden;
        }

        .service-details {
            margin-bottom: 75px;
        }

        .service-details__media {
            width: 96%;
            min-height: 330px;
            padding: 7px;
            border-radius: 25px;
        }

        .service-details__media::after {
            inset: 7px;
            border-radius: 19px;
        }

        .service-details__image {
            width: 100%;
            max-height: 430px;
            border-radius: 19px;
        }

        .service-details__image--empty {
            min-height: 320px;
            border-radius: 19px;
        }

        .service-details__content {
            width: 94%;
            margin-top: -35px;
            padding: 27px 17px 24px;
            border-radius: 24px;
        }

        .service-details__title {
            font-size: 1.7rem;
        }

        .service-details__description {
            font-size: .82rem;
            line-height: 2;
        }

        .service-details__pricing,
        .service-details__cta {
            width: 100%;
        }

        /* STAFF */

        .service-staff {
            margin-bottom: 75px;
        }

        .service-staff__heading,
        .service-gallery__heading {
            margin-bottom: 28px;
        }

        .service-staff__heading h2,
        .service-gallery__heading h2 {
            font-size: 1.55rem;
        }

        .service-staff__grid {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 17px;
            width: 100%;
        }

        .service-staff__card {
            flex: 0 1 auto;
            width: min(390px, 94%);
            max-width: 390px;
        }

        .service-staff__image {
            height: 310px;
        }

        /* GALLERY */

        .service-gallery__grid {
            grid-template-columns: 1fr 1fr;
            gap: 11px;
            width: 100%;
        }

        .service-gallery__item,
        .service-gallery__item:nth-child(1),
        .service-gallery__item:nth-child(2) {
            grid-column: span 1;
            height: 210px;
        }

        .service-gallery__item:nth-child(1) {
            grid-column: span 2;
            height: 270px;
        }

        .service-gallery__empty {
            grid-column: 1 / -1;
        }
    }

    /* =========================================================
   SMALL MOBILE
========================================================= */

    @media (max-width: 380px) {

        .details-page {
            padding-left: 8px;
            padding-right: 8px;
        }

        .service-details__media {
            width: 96%;
            min-height: 290px;
        }

        .service-details__content {
            width: 95%;
            padding: 24px 13px 21px;
        }

        .service-details__title {
            font-size: 1.5rem;
        }

        .service-details__description {
            font-size: .77rem;
        }

        .service-staff__card {
            width: 95%;
        }

        .service-staff__image {
            height: 285px;
        }

        .service-gallery__item,
        .service-gallery__item:nth-child(1) {
            height: 245px;
        }

        .service-gallery__item:nth-child(2) {
            height: 190px;
        }
    }
</style>

<main class="details-page">

    <section class="service-details">

        <div class="service-details__content">

            <span class="service-details__category">
                <?= $category ?>
            </span>

            <h1 class="service-details__title">
                <?= $serviceName ?>
            </h1>

            <?php if ($description !== ''): ?>
                <p class="service-details__description">
                    <?= nl2br($description) ?>
                </p>
            <?php endif; ?>

            <div class="service-details__pricing">
                <div class="price-box price-box--deposit">
                    <span class="price-box__label">مبلغ بیعانه</span>
                    <strong class="price-box__value">
                        <?= number_format($deposit) ?>
                        <small>تومان</small>
                    </strong>
                </div>
            </div>

            <a
                href="user-reservation.php?service=<?= (int)$service['id'] ?>"
                class="service-details__cta">
                رزرو این سرویس
                <span>←</span>
            </a>

        </div>

        <div class="service-details__media">

            <?php if ($imageUrl): ?>

                <img
                    src="<?= e($imageUrl) ?>"
                    alt="<?= $serviceName ?>"
                    class="service-details__image">

            <?php else: ?>

                <div class="service-details__image service-details__image--empty">
                    تصویر سرویس موجود نیست
                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="service-staff">

        <div class="service-staff__heading">
            <span>OUR TEAM</span>
            <h2>متخصص‌های <?= $serviceName ?></h2>
        </div>

        <div class="service-staff__grid">

            <?php if ($staffItems): ?>

                <?php foreach ($staffItems as $staff): ?>

                    <?php
                    $staffImageUrl = function_exists('site_asset_url')
                        ? site_asset_url($staff['image_path'])
                        : $staff['image_path'];
                    ?>

                    <article class="service-staff__card">

                        <?php if ($staffImageUrl): ?>
                            <img
                                src="<?= e($staffImageUrl) ?>"
                                alt="<?= e($staff['name']) ?>"
                                class="service-staff__image"
                                loading="lazy">
                        <?php endif; ?>

                        <div class="service-staff__content">

                            <h3>
                                <?= e($staff['name']) ?>
                            </h3>

                            <?php if (!empty($staff['specialty'])): ?>
                                <p>
                                    <?= e($staff['specialty']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($staff['bio'])): ?>
                                <span>
                                    <?= e($staff['bio']) ?>
                                </span>
                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="service-staff__empty">
                    هنوز متخصصی برای این سرویس ثبت نشده است.
                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="service-gallery">

        <div class="service-gallery__heading">
            <span>OUR WORK</span>
            <h2>نمونه‌کارهای <?= $serviceName ?></h2>
        </div>

        <div class="service-gallery__grid">

            <?php if ($galleryItems): ?>

                <?php foreach ($galleryItems as $gallery): ?>

                    <?php
                    $galleryUrl = function_exists('site_asset_url')
                        ? site_asset_url($gallery['image_path'])
                        : $gallery['image_path'];
                    ?>

                    <div class="service-gallery__item">

                        <img
                            src="<?= e($galleryUrl) ?>"
                            alt="<?= e($gallery['title']) ?>"
                            loading="lazy">

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="service-gallery__empty">
                    هنوز نمونه‌کاری برای این سرویس ثبت نشده است.
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>