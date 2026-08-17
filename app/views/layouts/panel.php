<?php $siteName = (string)get_setting('app_name', 'cv4u'); $me = current_user(); ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="theme-color" content="<?= e((string)get_setting('theme_color', '#6366f1')) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="page-panel">
<a class="skip-link" href="#panelMain">پرش به محتوای اصلی</a>
<div class="panel-shell">
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(base_url('panel')) ?>">
      <span class="brand-logo"><?= icon_svg('card'); ?></span>
      <span class="brand-name"><?= e($siteName) ?></span>
    </a>
    <nav class="side-nav" aria-label="منوی پنل">
      <a class="side-link <?= $active === 'dashboard' ? 'on' : '' ?>" href="<?= e(base_url('panel')) ?>"><?= icon_svg('dashboard', 18); ?><span>داشبورد</span></a>
      <a class="side-link <?= $active === 'cards' ? 'on' : '' ?>" href="<?= e(base_url('panel')) ?>#cards"><?= icon_svg('cards', 18); ?><span>کارت‌های من</span></a>
      <a class="side-link side-link-accent" href="<?= e(base_url('panel/card/new')) ?>"><?= icon_svg('plus', 18); ?><span>کارت جدید</span></a>

      <?php if (is_admin()): ?>
      <div class="side-sep">پنل مدیریت</div>
      <a class="side-link <?= $active === 'admin-dashboard' ? 'on' : '' ?>" href="<?= e(base_url('admin')) ?>"><?= icon_svg('dashboard', 18); ?><span>داشبورد مدیریت</span></a>
      <a class="side-link <?= $active === 'admin-users' ? 'on' : '' ?>" href="<?= e(base_url('admin/users')) ?>"><?= icon_svg('users', 18); ?><span>مدیریت کاربران</span></a>
      <a class="side-link <?= $active === 'admin-cards' ? 'on' : '' ?>" href="<?= e(base_url('admin/cards')) ?>"><?= icon_svg('cards', 18); ?><span>مدیریت کارت‌ها</span></a>
      <a class="side-link <?= $active === 'admin-settings' ? 'on' : '' ?>" href="<?= e(base_url('admin/settings')) ?>"><?= icon_svg('settings', 18); ?><span>تنظیمات سایت</span></a>
      <?php endif; ?>

      <div class="side-sep">عمومی</div>
      <a class="side-link" target="_blank" rel="noopener" href="<?= e(base_url('')) ?>"><?= icon_svg('globe', 18); ?><span>صفحه اصلی</span></a>
      <a class="side-link" href="<?= e(base_url('logout')) ?>"><?= icon_svg('logout', 18); ?><span>خروج</span></a>
    </nav>
    <div class="sidebar-user">
      <div class="su-avatar"><?= e(mb_substr($me['name'], 0, 1)) ?></div>
      <div class="su-meta">
        <strong><?= e($me['name']) ?></strong>
        <span><?= $me['role'] === 'admin' ? 'مدیر سایت' : 'کاربر' ?></span>
      </div>
    </div>
  </aside>

  <div class="panel-main" id="panelMain">
    <header class="panel-topbar">
      <button class="icon-btn menu-btn" id="menuBtn" aria-label="باز کردن منوی کناری" aria-expanded="false" aria-controls="sidebar"><?= icon_svg('menu', 20); ?></button>
      <h1 class="panel-title"><?= e($pageTitle) ?></h1>
    </header>
    <div class="panel-content container">
      <?= flash_render() ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
