<?php $siteName = (string)get_setting('app_name', 'cv4u'); ?>
<div class="stat-row">
  <div class="stat-card">
    <div class="stat-icon si-1"><?= icon_svg('cards', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format(count($cards)) ?></strong><span>کارت شما</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-2"><?= icon_svg('eye', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format($totalVisits) ?></strong><span>کل بازدیدها</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-3"><?= icon_svg('check-circle', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format(count(array_filter($cards, function ($c) { return (int)$c['active'] === 1; }))) ?></strong><span>کارت فعال</span></div>
  </div>
</div>

<section id="cards" class="panel-section">
  <div class="section-head">
    <h2>کارت‌های ویزیت شما</h2>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('panel/card/new')) ?>"><?= icon_svg('plus', 16); ?> کارت جدید</a>
  </div>

  <?php if (!$cards): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= icon_svg('card', 42); ?></div>
    <h3>هنوز کارتی نساخته‌اید</h3>
    <p>اولین کارت ویزیت مجازی خود را بسازید و لینک کوتاه آن را بگیرید.</p>
    <a class="btn btn-primary" href="<?= e(base_url('panel/card/new')) ?>">ساخت کارت ویزیت</a>
  </div>
  <?php else: ?>
  <div class="card-list">
    <?php foreach ($cards as $c):
        $short = card_public_url($c); ?>
    <div class="vc-row">
      <div class="vc-qr">
        <img src="<?= e(card_qr_url($c, 'px=12')) ?>" alt="QR کارت" loading="lazy">
      </div>
      <div class="vc-info">
        <div class="vc-topline">
          <strong class="vc-name"><?= e($c['full_name'] ?: 'کارت بدون نام') ?></strong>
          <?php if ((int)$c['active'] === 1): ?><span class="chip chip-on">فعال</span><?php else: ?><span class="chip chip-off">غیرفعال</span><?php endif; ?>
        </div>
        <div class="vc-meta">
          <?php if ($c['job_title']): ?><span><?= e($c['job_title']) ?></span><?php endif; ?>
          <?php if ($c['company']): ?><span>• <?= e($c['company']) ?></span><?php endif; ?>
        </div>
        <div class="vc-link" dir="ltr"><?= e($short) ?>
          <button type="button" class="icon-btn copy-btn" data-copy="<?= e($short) ?>" aria-label="کپی لینک" title="کپی لینک"><?= icon_svg('copy', 15); ?></button>
        </div>
        <div class="vc-stats"><span><?= icon_svg('eye', 14); ?> <?= fa_num_format($c['visits']) ?> بازدید</span><span><?= icon_svg('calendar', 14); ?> <?= fa_date(strtotime($c['created_at'])) ?></span></div>
      </div>
      <div class="vc-actions">
        <a class="icon-btn" target="_blank" rel="noopener" href="<?= e($short) ?>" aria-label="مشاهده کارت"><?= icon_svg('eye', 17); ?></a>
        <a class="icon-btn" href="<?= e(base_url('panel/card/' . $c['id'])) ?>" aria-label="آمار بازدید"><?= icon_svg('dashboard', 17); ?></a>
        <a class="icon-btn" href="<?= e(base_url('panel/card/edit?id=' . $c['id'])) ?>" aria-label="ویرایش کارت"><?= icon_svg('edit', 17); ?></a>
        <a class="icon-btn" href="<?= e(base_url('qr/' . $c['code'] . '.png?download=1&px=24')) ?>" aria-label="دانلود QR" download><?= icon_svg('download', 17); ?></a>
        <label class="mini-switch" title="<?= (int)$c['active'] === 1 ? 'غیرفعال کردن' : 'فعال کردن' ?>">
          <input type="checkbox" class="js-card-toggle" <?= (int)$c['active'] === 1 ? 'checked' : '' ?> data-url="<?= e(base_url('panel/card/toggle?id=' . $c['id'])) ?>" data-csrf="<?= e(csrf_token()) ?>">
          <span class="ms-track"></span>
        </label>
        <form method="post" action="<?= e(base_url('panel/card/delete?id=' . $c['id'])) ?>" class="inline-form" data-confirm="این کارت برای همیشه حذف می‌شود. ادامه می‌دهید؟">
          <?= csrf_field() ?>
          <button type="submit" class="icon-btn icon-danger" aria-label="حذف کارت"><?= icon_svg('trash', 17); ?></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
