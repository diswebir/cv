<?php $siteName = (string)get_setting('app_name', 'کارت ویزیت من'); ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="theme-color" content="#6366f1">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="fa_IR">
<meta name="twitter:card" content="summary">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="page-public">
<a class="skip-link" href="#publicMain">پرش به محتوای اصلی</a>
<div class="site-topbar">
  <div class="container topbar-inner">
    <a class="brand" href="<?= e(base_url('')) ?>">
      <span class="brand-logo"><?= icon_svg('card'); ?></span>
      <span class="brand-name"><?= e($siteName) ?></span>
    </a>
    <nav class="topbar-nav" aria-label="ناوبری اصلی">
      <?php if (is_logged_in()): ?>
        <a class="link" href="<?= e(base_url('panel')) ?>">پنل کاربری</a>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('logout')) ?>">خروج</a>
      <?php else: ?>
        <a class="link" href="<?= e(base_url('login')) ?>">ورود</a>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('register')) ?>">ثبت‌نام</a>
      <?php endif; ?>
    </nav>
  </div>
</div>
<main class="public-main" id="publicMain"><?= $content ?></main>
<footer class="site-footer">
  <div class="container">
    <p>© <?= fa_num(date('Y')) ?> <?= e($siteName) ?> — تمامی حقوق محفوظ است.<?php $ft = trim((string)get_setting('footer_text', '')); if ($ft !== '') echo '<br>' . e($ft); ?></p>
  </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
