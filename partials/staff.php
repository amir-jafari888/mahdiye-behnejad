<section class="staff-section" id="staff">
    <div class="staff-container">
        <div class="section-header">
            <h2 class="section-title">تیم حرفه ای ما</h2>
            <h2 class="section-title-two">Our Pro Team</h2>
        </div>
        <div class="staff-grid">
            <?php
            $stmt = $pdo->query("SELECT id,name,specialty,image_path FROM staff WHERE is_active=1 ORDER BY sort_order ASC,id ASC");
            $staffMembers = $stmt->fetchAll();
            foreach ($staffMembers as $staff):
            ?>
                <article class="staff-card">
                    <div class="staff-image-wrap">
                        <img class="staff-image" src="<?php echo htmlspecialchars(site_asset_url($staff['image_path'])); ?>" alt="تصویر <?= htmlspecialchars($staff['name'], ENT_QUOTES, 'UTF-8') ?>، متخصص <?= htmlspecialchars($staff['specialty'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    </div>
                    <div class="staff-info">
                        <h3 class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></h3>
                        <p class="staff-specialty"><?php echo htmlspecialchars($staff['specialty']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>