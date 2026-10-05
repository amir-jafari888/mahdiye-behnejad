<!-- start hero -->
<section class="hero">
    <h1 class="visually-hidden">سالن زیبایی مهدیه بهنژاد</h1>
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <?php
            $slides = $pdo->query("SELECT * FROM hero_slides ORDER BY id DESC");
            while ($row = $slides->fetch()) {
                echo '
                <div class="swiper-slide">
                    <img src="' . htmlspecialchars(site_asset_url($row['image_path']), ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '" class="img-slider" />
                    <div class="hero-overlay"></div>
                    <div class="hero-text-container">
                        <h2 class="hero-title">' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</h2>
                        <p class="hero-description">' . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . '</p>
                        <a href="/mahdiye-benejad/partials/service-page.php" class="hero-btn-link">مشاهده خدمات</a>
                    </div>
                </div>';
            }
            ?>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>
<!-- end hero -->