<?php
require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT id,image_path,title,description,tag
    FROM salon_gallery
    WHERE is_active=1
    ORDER BY sort_order ASC,id DESC
");
$salonGallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/head.php';
require_once __DIR__ . '/header.php';
?>

<main class="salon-gallery-page">
    <section class="salon-gallery-section">
        <div class="salon-gallery-container">
            <header class="salon-gallery-header">
                <span class="salon-gallery-eyebrow">OUR SALON</span>
                <h1 class="salon-gallery-title">تصاویر سالن</h1>
                <p class="salon-gallery-subtitle">فضایی آرام، زیبا و حرفه‌ای برای تجربه‌ای متفاوت</p>
            </header>

            <?php if (!empty($salonGallery)): ?>
                <div class="salon-gallery-grid">
                    <?php foreach ($salonGallery as $item): ?>
                        <article class="salon-gallery-card">
                            <div class="salon-gallery-image-wrap">
                                <img
                                    class="salon-gallery-image"
                                    src="<?= htmlspecialchars(site_asset_url($item['image_path']), ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy">
                                <?php if (!empty($item['tag'])): ?>
                                    <span class="salon-gallery-tag"><?= htmlspecialchars($item['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="salon-gallery-info">
                                <h2><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <?php if (!empty($item['description'])): ?>
                                    <p><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="salon-gallery-empty">
                    <span>✦</span>
                    <h2>تصویری برای نمایش وجود ندارد</h2>
                    <p>به‌زودی تصاویر سالن در این بخش قرار می‌گیرند.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>