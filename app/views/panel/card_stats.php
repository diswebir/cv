<?php
$short = card_public_url($card);
$chartMax = 1;
foreach ($chart['counts'] as $c) $chartMax = max($chartMax, (int)$c);
?>
<div class="stats-head">
  <div class="sh-info">
    <h2><?= e($card['full_name'] ?: 'کارت') ?></h2>
    <p><?= (int)$card['active'] === 1 ? 'کارت فعال' : 'کارت غیرفعال' ?> • ساخته شده در <?= fa_date(strtotime($card['created_at'])) ?></p>
  </div>
  <div class="sh-actions">
    <a class="btn btn-sm btn-primary" target="_blank" rel="noopener" href="<?= e($short) ?>"><?= icon_svg('eye', 16); ?> مشاهده</a>
    <a class="btn btn-sm btn-ghost" href="<?= e(base_url('panel/card/edit?id=' . $card['id'])) ?>"><?= icon_svg('edit', 16); ?> ویرایش</a>
  </div>
</div>

<div class="link-box">
  <div class="lb-label">لینک کوتاه کارت</div>
  <div class="lb-row">
    <code class="lb-link" dir="ltr"><?= e($short) ?></code>
    <button type="button" class="btn btn-sm btn-primary copy-btn" data-copy="<?= e($short) ?>"><?= icon_svg('copy', 15); ?> کپی</button>
  </div>
  <div class="lb-hint">این لینک در کد QR قرار گرفته است. می‌توانید آن را در سربرگ، پیام‌رسان‌ها و شبکه‌های اجتماعی به اشتراک بگذارید.</div>
</div>

<div class="qr-and-stats">
  <div class="qr-panel">
    <div class="qr-panel-img">
      <img src="<?= e(card_qr_url($card, 'px=14')) ?>" alt="QR کارت">
    </div>
    <div class="qr-panel-actions">
      <a class="btn btn-sm btn-primary" href="<?= e(card_qr_url($card, 'px=30&download=1')) ?>" download><?= icon_svg('download', 15); ?> دانلود PNG</a>
      <a class="btn btn-sm btn-ghost" href="<?= e(card_qr_url($card, 'download=1&fmt=svg')) ?>" download><?= icon_svg('download', 15); ?> SVG</a>
    </div>
    <p class="qr-panel-note">تم فعلی: <?= e($card['qr_theme']) ?> • نقطه‌های <?= e($card['qr_dots']) ?>. در صفحه ویرایش می‌توانید تم را عوض کنید.</p>
  </div>

  <div class="stats-cols">
    <div class="stat-card">
      <div class="stat-icon si-2"><?= icon_svg('eye', 22); ?></div>
      <div class="stat-body"><strong><?= fa_num_format($card['visits']) ?></strong><span>کل بازدیدها</span></div>
    </div>
    <div class="chart-card">
      <h3>بازدید ۱۴ روز اخیر</h3>
      <div class="bar-chart" dir="ltr">
        <?php foreach ($chart['counts'] as $i => $c): ?>
        <div class="bar-col" title="<?= e($chart['labels'][$i]) ?>: <?= fa_num($c) ?>">
          <div class="bar" style="height: <?= max(3, round((int)$c / $chartMax * 100)) ?>%"></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($recent): ?>
<div class="panel-section">
  <div class="section-head"><h2>بازدیدهای اخیر</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>زمان</th><th>آی‌پی</th><th>مرورگر</th><th>منبع</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $v): ?>
        <tr>
          <td><?= fa_datetime(strtotime($v['visited_at'])) ?></td>
          <td dir="ltr"><?= e($v['ip']) ?></td>
          <td class="td-trunc"><?= e(substr($v['user_agent'], 0, 60)) ?></td>
          <td class="td-trunc"><?= $v['referer'] ? e(parse_url($v['referer'], PHP_URL_HOST) ?: $v['referer']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
