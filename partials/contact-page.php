<?php
$pageTitle = 'تماس با سالن زیبایی مهدیه بهنژاد';
$pageDescription = 'اطلاعات تماس، آدرس، ساعات کاری و راه‌های ارتباط با سالن زیبایی مهدیه بهنژاد.';
require_once __DIR__ . '/head.php';
require_once __DIR__ . '/header.php';
?>

<main class="contact-page" dir="rtl">
    <section class="contact-section">
        <div class="contact-container">

            <header class="contact-header">
                <span class="contact-eyebrow">CONTACT US</span>
                <h1 class="contact-title">با ما در ارتباط باشید</h1>
                <p class="contact-subtitle">
                    برای دریافت اطلاعات بیشتر، مشاوره یا هماهنگی نوبت، با ما در تماس باشید.
                </p>
            </header>

            <div class="contact-grid">

                <div class="contact-info-card">
                    <div class="contact-card-heading">
                        <span class="contact-card-icon">✦</span>
                        <div>
                            <span class="contact-card-label">راه‌های ارتباطی</span>
                            <h2>تماس با ما</h2>
                        </div>
                    </div>

                    <div class="contact-info-list">

                        <a href="#" class="contact-info-item">
                            <span class="contact-info-icon">⌖</span>
                            <span class="contact-info-content">
                                <strong>آدرس سالن</strong>
                                <small>تهران، خیابان ولیعصر، پلاک ۱۲۳</small>
                            </span>
                        </a>

                        <a href="tel:02112345678" class="contact-info-item">
                            <span class="contact-info-icon">☎</span>
                            <span class="contact-info-content">
                                <strong>شماره تماس</strong>
                                <small>۰۲۱-۱۲۳۴۵۶۷۸</small>
                            </span>
                        </a>

                        <div class="contact-info-item">
                            <span class="contact-info-icon">◷</span>
                            <span class="contact-info-content">
                                <strong>ساعات کاری</strong>
                                <small>شنبه تا پنج‌شنبه | ۱۰ صبح تا ۸ شب</small>
                            </span>
                        </div>

                        <a href="mailto:info@example.com" class="contact-info-item">
                            <span class="contact-info-icon">✉</span>
                            <span class="contact-info-content">
                                <strong>ایمیل</strong>
                                <small>info@example.com</small>
                            </span>
                        </a>

                    </div>

                    <div class="contact-social-box">
                        <span>ما را دنبال کنید</span>

                        <div class="contact-social-links">
                            <a href="#" class="contact-social-link" aria-label="Instagram">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <rect x="2" y="2" width="28" height="28" rx="7" fill="currentColor" />
                                    <circle cx="16" cy="16" r="6" fill="#fff" />
                                    <circle cx="16" cy="16" r="3.5" fill="currentColor" />
                                    <circle cx="22.5" cy="9.5" r="1.5" fill="#fff" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="contact-form-card">
                    <div class="contact-form-heading">
                        <span class="contact-card-label">SEND MESSAGE</span>
                        <h2>پیام خود را ارسال کنید</h2>
                        <p>در اولین فرصت پاسخگوی شما خواهیم بود.</p>
                    </div>

                    <form class="contact-form" action="#" method="post">

                        <div class="contact-form-row">
                            <div class="contact-field">
                                <label for="contact-name">نام و نام خانوادگی</label>
                                <input
                                    type="text"
                                    id="contact-name"
                                    name="name"
                                    placeholder="نام شما"
                                    autocomplete="name">
                            </div>

                            <div class="contact-field">
                                <label for="contact-phone">شماره تماس</label>
                                <input
                                    type="tel"
                                    id="contact-phone"
                                    name="phone"
                                    placeholder="شماره موبایل"
                                    autocomplete="tel">
                            </div>
                        </div>

                        <div class="contact-field">
                            <label for="contact-email">ایمیل</label>
                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                placeholder="ایمیل شما"
                                autocomplete="email">
                        </div>

                        <div class="contact-field">
                            <label for="contact-message">پیام شما</label>
                            <textarea
                                id="contact-message"
                                name="message"
                                rows="6"
                                placeholder="پیام خود را بنویسید..."></textarea>
                        </div>

                        <button type="submit" class="contact-submit">
                            <span>ارسال پیام</span>
                            <b>←</b>
                        </button>

                    </form>
                </div>

            </div>

            <div class="contact-bottom-card">
                <div class="contact-bottom-decoration">✦</div>
                <div class="contact-bottom-content">
                    <span>برای رزرو نوبت آماده‌اید؟</span>
                    <h2>زیبایی شما، افتخار ماست.</h2>
                </div>
                <a href="/mahdiye-benejad/user-login.php" class="contact-reserve-btn">
                    رزرو آنلاین
                    <b>←</b>
                </a>
            </div>

        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>