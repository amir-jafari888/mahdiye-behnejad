<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon.ico" sizes="any">
    <link rel="icon" href="/mahdiye-benejad/assets/image/favicon-96x96.png" type="image/png" sizes="96x96">
    <link rel="apple-touch-icon" href="/mahdiye-benejad/assets/image/apple-touch-icon.png">
    <link rel="manifest" href="/mahdiye-benejad/assets/image/site.webmanifest">
    <meta name="theme-color" content="#ffffff">
    <title>پنل مدیریت Mahdiye Beauty</title>
    <style>
        /* --- تنظیمات پایه و متغیرها (مطمئن شو این در فایل هست) --- */
        /* --- 1. متغیرهای پایه (Root) --- */
        :root {
            --primary-color: #77dd77;
            --secondary-color: #eadbc8;
            --contrast-color: #5d4037;
            --accent-color: #ffffff;
            --text-main: #444;
            --text-muted: #777;
            --bg-soft-pink: rgba(255, 170, 183, 0.32);
            --error-color: #ff5252;
            --transition-smooth: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* --- 2. تنظیمات عمومی --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- 1. تنظیمات عمومی --- */
        body {
            background: var(--secondary-color);
            color: var(--text-main);
            line-height: 1.6;
        }

        /* --- 2. هدر شناور (Floating Nav) --- */
        .floating-nav {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: fit-content;
            min-width: 300px;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 12px 25px;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6),
                0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: var(--transition-smooth);
        }

        .floating-nav a {
            text-decoration: none;
            color: var(--text-main);
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: var(--transition-smooth);
        }

        .floating-nav a:hover {
            color: var(--primary-color);
            background: rgba(255, 255, 255, 0.4);
        }

        /* اضافه شده: استایل لینک فعال */
        .floating-nav a.active {
            background: var(--primary-color);
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(119, 221, 119, 0.3);
        }

        /* --- 3. بدنه اصلی --- */
        .admin-dashboard {
            padding: 120px 20px 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 28px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6), 0 8px 32px rgba(0, 0, 0, 0.05);
        }

        /* --- 4. کارت‌ها (Bento Grid) --- */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            /* کدهای اصلی خودت */
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 28px;
            padding: 30px;
            transition: var(--transition-smooth);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6),
                0 10px 25px -5px rgba(0, 0, 0, 0.05);

            /* اضافه شده: وسط‌چین کردن محتوا */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .card:hover {
            /* کدهای اصلی خودت */
            transform: translateY(-10px) scale(1.01);
            background: rgba(255, 255, 255, 0.6);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.7),
                0 25px 40px -10px rgba(0, 0, 0, 0.1);

            /* اضافه شده: بوردر داخلی رنگی هنگام هاور */
            box-shadow: inset 0 0 0 3px var(--primary-color),
                0 25px 40px -10px rgba(0, 0, 0, 0.1);
        }

        /* --- 5. جدول رزروها --- */
        .table-card {
            padding: 30px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        tr {
            transition: transform 0.3s ease;
        }

        tr:hover {
            transform: scale(1.005);
            background: rgba(255, 255, 255, 0.5) !important;
        }

        td {
            padding: 20px 15px;
            background: rgba(255, 255, 255, 0.3);
        }

        tr td:first-child {
            border-radius: 20px 0 0 20px;
        }

        tr td:last-child {
            border-radius: 0 20px 20px 0;
        }

        /* --- 6. المان‌های جانبی --- */
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .status-active {
            background: rgba(119, 221, 119, 0.3);
            color: #2e7d32;
        }

        .status-pending {
            background: rgba(255, 202, 40, 0.3);
            color: #ef6c00;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        .staff-admin-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 20px 50px
        }

        .staff-admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            padding: 30px;
            background: rgba(255, 255, 255, .4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, .5);
            border-radius: 28px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 30px rgba(0, 0, 0, .05)
        }

        .staff-admin-eyebrow {
            font-size: 11px;
            letter-spacing: 3px;
            color: var(--primary-color)
        }

        .staff-admin-header h1 {
            margin-top: 5px;
            color: var(--contrast-color);
            font-size: 28px
        }

        .staff-admin-header p {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 14px
        }

        .staff-count {
            min-width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .45);
            border: 1px solid rgba(255, 255, 255, .7);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .06)
        }

        .staff-count strong {
            font-size: 28px;
            color: var(--contrast-color)
        }

        .staff-count span {
            font-size: 12px;
            color: var(--text-muted)
        }

        .staff-alert {
            margin-bottom: 20px;
            padding: 15px 20px;
            border-radius: 18px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            font-weight: 600
        }

        .staff-alert.success {
            background: rgba(119, 221, 119, .2);
            color: #2e7d32;
            border: 1px solid rgba(119, 221, 119, .35)
        }

        .staff-alert.error {
            background: rgba(255, 82, 82, .12);
            color: #c62828;
            border: 1px solid rgba(255, 82, 82, .25)
        }

        .staff-form-card,
        .staff-list-section {
            padding: 30px;
            background: rgba(255, 255, 255, .4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, .5);
            border-radius: 28px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 30px rgba(0, 0, 0, .05);
            margin-bottom: 25px
        }

        .staff-card-title,
        .staff-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px
        }

        .staff-card-title h2,
        .staff-list-header h2 {
            color: var(--contrast-color);
            font-size: 20px
        }

        .staff-card-title p,
        .staff-list-header p {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 5px
        }

        .staff-card-title>i {
            font-size: 28px;
            color: var(--primary-color)
        }

        .staff-form {
            display: flex;
            flex-direction: column;
            gap: 20px
        }

        .staff-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px
        }

        .staff-field {
            display: flex;
            flex-direction: column;
            gap: 8px
        }

        .staff-field-full {
            grid-column: 1/-1
        }

        .staff-field label {
            font-size: 13px;
            color: var(--contrast-color);
            font-weight: 600
        }

        .staff-field input,
        .staff-field textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, .8);
            border-radius: 16px;
            background: rgba(255, 255, 255, .6);
            color: var(--text-main);
            font-family: inherit;
            outline: none;
            transition: var(--transition-smooth)
        }

        .staff-field textarea {
            min-height: 100px;
            resize: vertical
        }

        .staff-field input:focus,
        .staff-field textarea:focus {
            background: rgba(255, 255, 255, .9);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(119, 221, 119, .15)
        }

        .staff-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--contrast-color);
            font-size: 13px;
            cursor: pointer
        }

        .staff-checkbox input {
            accent-color: var(--primary-color)
        }

        .staff-submit {
            border: 0;
            border-radius: 16px;
            padding: 14px 22px;
            background: var(--primary-color);
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth)
        }

        .staff-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(119, 221, 119, .25)
        }

        .staff-admin-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px
        }

        .staff-admin-card {
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .4);
            border: 1px solid rgba(255, 255, 255, .55);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .04);
            transition: var(--transition-smooth)
        }

        .staff-admin-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(0, 0, 0, .08)
        }

        .staff-admin-card.inactive {
            opacity: .6
        }

        .staff-admin-image {
            position: relative;
            width: 145px;
            height: 145px;
            margin: 0 auto 15px
        }

        .staff-admin-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, .7)
        }

        .staff-status {
            position: absolute;
            bottom: 2px;
            right: 5px;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700
        }

        .staff-status.active {
            background: rgba(119, 221, 119, .9);
            color: #256d25
        }

        .staff-status.inactive {
            background: rgba(255, 82, 82, .85);
            color: #fff
        }

        .staff-admin-info {
            text-align: center
        }

        .staff-admin-info h3 {
            color: var(--contrast-color);
            font-size: 15px
        }

        .staff-admin-info p {
            margin-top: 4px;
            color: var(--primary-hover);
            font-size: 12px;
            font-weight: 600
        }

        .staff-admin-info span {
            display: block;
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.7
        }

        .staff-admin-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 16px
        }

        .staff-admin-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 18px
        }

        .staff-admin-actions form {
            margin: 0;
            padding: 0
        }

        .staff-admin-actions a,
        .staff-admin-actions button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 13px;
            background: rgba(255, 255, 255, .42);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: var(--contrast-color);
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(93, 64, 55, .08);
            transition: var(--transition-smooth)
        }

        .staff-admin-actions a:hover,
        .staff-admin-actions button:hover {
            transform: translateY(-3px);
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 8px 18px rgba(119, 221, 119, .2)
        }

        .staff-admin-actions .delete:hover {
            background: var(--error-color);
            border-color: var(--error-color);
            box-shadow: 0 8px 18px rgba(255, 82, 82, .2)
        }

        .staff-empty {
            grid-column: 1/-1;
            padding: 40px;
            text-align: center;
            color: var(--text-muted)
        }

        .staff-admin-actions svg {
            width: 18px;
            height: 18px;
            display: block
        }

        .dashboard-staff-name {
            display: inline-block;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap
        }

        .staff-services-select {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }

        .staff-service-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 58px;
            padding: 10px 12px;
            border: 1px solid rgba(93, 64, 55, .14);
            border-radius: 14px;
            background: rgba(255, 255, 255, .55);
            cursor: pointer;
            transition: .25s ease;
        }

        .staff-service-option:hover {
            border-color: var(--primary-color);
            background: rgba(119, 221, 119, .10);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(93, 64, 55, .08);
        }

        .staff-service-option input {
            appearance: none;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            margin: 0;
            border: 1.5px solid rgba(93, 64, 55, .30);
            border-radius: 6px;
            background: var(--accent-color);
            cursor: pointer;
            transition: .2s ease;
        }

        .staff-service-option input:checked {
            border-color: var(--primary-color);
            background: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(119, 221, 119, .16);
        }

        .staff-service-option input:checked::after {
            content: "✓";
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--accent-color);
            font-size: 13px;
            font-weight: 700;
        }

        .staff-service-option span {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            color: var(--contrast-color);
            font-size: 14px;
            font-weight: 500;
        }

        .staff-service-option small {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 400;
        }

        @media (max-width:768px) {
            .staff-services-select {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .staff-service-option {
                min-height: 54px;
                padding: 9px 10px;
            }
        }

        @media (max-width:480px) {
            .staff-services-select {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="floating-nav">
        <a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">داشبورد</a>

        <!-- لینک‌های مدیریتی جدید -->
        <a href="admin-hero.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-hero.php') ? 'active' : ''; ?>">هیرو</a>
        <a href="admin-service.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-service.php') ? 'active' : ''; ?>">خدمات</a>
        <a href="admin-gallery.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-gallery.php') ? 'active' : ''; ?>">گالری</a>
        <a href="admin-salon-gallery.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-salon-gallery.php') ? 'active' : ''; ?>">سالن گالری</a>
        <a href="admin-staff.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-staff.php') ? 'active' : ''; ?>">کادر</a>
        <a href="reservation.php"
            class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reservation.php') ? 'active' : ''; ?>">
            رزروها
        </a>

        <a href="logout.php" style="color: var(--error-color);">خروج</a>
    </nav>