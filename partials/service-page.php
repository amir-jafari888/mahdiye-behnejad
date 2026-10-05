<?php
$pageTitle = 'خدمات سالن زیبایی مهدیه بهنژاد';
$pageDescription = 'معرفی خدمات تخصصی سالن زیبایی مهدیه بهنژاد شامل میکاپ، شینیون، خدمات عروس و دیگر خدمات زیبایی.';
require_once 'head.php';
?>
<?php require_once 'header.php' ?>

<section class="services-section">
    <div class="container-service">
        <div class="section-header">
            <h2 class="section-title">خدمات ما</h2>
            <h2 class="section-title-two">Our Services</h2>
        </div>

        <div class="services-grid">
            <?php
            require_once __DIR__ . '/db.php';

            $query = $pdo->query("
                SELECT id, name, description, image_path
                FROM services
                ORDER BY id ASC
            ");
            $services = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($services as $service) :
            ?>
                <div class="service-card">
                    <div class="card-image">
                        <img
                            src="<?= htmlspecialchars(site_asset_url($service['image_path']), ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="card-overlay"></div>

                    <div class="card-content">
                        <h3 class="card-title">
                            <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p class="card-description">
                            <?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <a
                            href="/mahdiye-benejad/details.php?service=<?= (int)$service['id'] ?>"
                            class="learn-more">
                            مشاهده جزئیات
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'footer.php' ?>