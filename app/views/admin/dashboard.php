<?php $chartMax = 1; foreach ($chart['counts'] as $c) $chartMax = max($chartMax, (int)$c); ?>
<div class="stat-row">
  <div class="stat-card">
    <div class="stat-icon si-1"><?= icon_svg('users', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format($stats['users']) ?></strong><span>کاربران</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-2"><?= icon_svg('cards', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format($stats['cards']) ?></strong><span>کارت‌ها</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-3"><?= icon_svg('eye', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format($stats['visits']) ?></strong><span>کل بازدیدها</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-4"><?= icon_svg('clock', 22); ?></div>
    <div class="stat-body"><strong><?= fa_num_format($stats['today']) ?></strong><span>بازدید امروز</span></div>
  </div>
</div>

<div class="chart-card">
  <h3>بازدید سایت — ۱۴ روز اخیر</h3>
  <div class="bar-chart" dir="ltr">
    <?php foreach ($chart['counts'] as $i => $c): ?>
    <div class="bar-col" title="<?= e($chart['labels'][$i]) ?>: <?= fa_num($c) ?>">
      <div class="bar" style="height: <?= max(3, round((int)$c / $chartMax * 100)) ?>%"></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="two-col">
  <div class="panel-section">
    <div class="section-head">
      <h2>آخرین کاربران</h2>
      <a class="link-more" href="<?= e(base_url('admin/users')) ?>">همه</a>
    </div>
    <div class="mini-list">
      <?php foreach ($users as $u): ?>
      <div class="mini-row">
        <div class="su-avatar sm"><?= e(mb_substr($u['name'], 0, 1)) ?></div>
        <div class="mini-meta"><strong><?= e($u['name']) ?></strong><span><?= e($u['email']) ?></span></div>
        <span class="chip <?= (int)$u['status'] === 1 ? 'chip-on' : 'chip-off' ?>"><?= (int)$u['status'] === 1 ? 'فعال' : 'غیرفعال' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="panel-section">
    <div class="section-head">
      <h2>آخرین کارت‌ها</h2>
      <a class="link-more" href="<?= e(base_url('admin/cards')) ?>">همه</a>
    </div>
    <div class="mini-list">
      <?php foreach ($cards as $c): ?>
      <div class="mini-row">
        <div class="mini-qr-sm"><img src="<?= e(card_qr_url($c, 'px=8')) ?>" alt=""></div>
        <div class="mini-meta"><strong><?= e($c['full_name'] ?: 'بدون نام') ?></strong><span><?= e($c['owner_name']) ?> • <?= fa_num_format($c['visits']) ?> بازدید</span></div>
        <span class="chip <?= (int)$c['active'] === 1 ? 'chip-on' : 'chip-off' ?>"><?= (int)$c['active'] === 1 ? 'فعال' : 'غیرفعال' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
