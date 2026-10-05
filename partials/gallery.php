<section class="gallery">
    <div class="section-header">
        <h2 class="section-title">نمونه کارها</h2>
        <h2 class="section-title-two">Portfolio</h2>
    </div>

    <div class="swiper gallerySwiper">
        <div class="swiper-wrapper">
            <?php
            // استفاده از همان فایل اتصال به دیتابیس (مطابق با بخش خدمات)
            require_once __DIR__ . '/db.php';

            // دریافت اطلاعات گالری از دیتابیس
            $query = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
            $galleryItems = $query->fetchAll();

            // شروع حلقه برای نمایش آیتم‌های گالری
            foreach ($galleryItems as $item) : ?>
                <div class="swiper-slide">
                    <img
                        src="<?php echo htmlspecialchars(site_asset_url($item['image_path'])); ?>"
                        alt="<?php echo htmlspecialchars($item['title']); ?>"
                        class="gallery-img"
                        loading="lazy" />
                    <div class="gallery-overlay">
                        <span class="gallery-tag"><?php echo htmlspecialchars($item['tag']); ?></span>
                        <h3 class="gallery-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="gallery-desc">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        <a href="/mahdiye-benejad/partials/gallery-page.php" class="gallery-btn">مشاهده جزئیات</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>