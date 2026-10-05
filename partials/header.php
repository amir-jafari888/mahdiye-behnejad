<body>
    <button id="backToTop" class="back-to-top" aria-label="Back to top">
        <!-- دایره پیشرفت (SVG) -->
        <svg class="progress-ring" width="60" height="60">
            <circle
                class="progress-ring__circle"
                stroke="white"
                stroke-width="3"
                fill="transparent"
                r="27"
                cx="30"
                cy="30" />
        </svg>
        <!-- آیکون اصلی -->
        <i class="fas fa-arrow-up"></i>
    </button>

    <header class="main-header">
        <div class="head-container">
            <button type="button" class="humburgermenu" id="menuToggle" aria-label="باز کردن منو" aria-controls="mobileMenu" aria-expanded="false">
                <span class="hamburger-icon-wrapper" aria-hidden="true">
                    <svg class="hamburger-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path class="line line-top" d="M4 6H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        <path class="line line-middle" d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        <path class="line line-bottom" d="M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                    <svg class="close-menu" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </span>
            </button>
            <div class="menu-overlay" id="menuOverlay"></div>

            <nav class="mobile-menu" id="mobileMenu" aria-label="منوی اصلی" aria-hidden="true">
                <ul class="menu-links">
                    <li><a href="/mahdiye-benejad/index.php">صفحه اصلی</a></li>
                    <li><a href="/mahdiye-benejad/partials/service-page.php">خدمات ما</a></li>
                    <li><a href="/mahdiye-benejad/partials/gallery-page.php">گالری تصاویر</a></li>
                    <li><a href="/mahdiye-benejad/partials/salon-page.php">تصاویر سالن</a></li>
                    <li><a href="/mahdiye-benejad/partials/about-page.php">درباره ما</a></li>
                    <li><a href="/mahdiye-benejad/partials/contact-page.php">تماس با ما</a></li>
                </ul>
            </nav>


            <div class="middle-logo">
                <img
                    src="/mahdiye-benejad/assets/image/IMG_20260819_163540_273.PNG"
                    alt="سالن زیبایی مهدیه بهنژاد"
                    class="logo" />
            </div>

            <div class="reserve-button">
                <a href="/mahdiye-benejad/user-login.php" class="reserve-btn">
                    <span class="reserve-text">رزرو</span>
                </a>
            </div>
        </div>

    </header>