<?php $totalPages = max(1, (int)ceil($list['total'] / 15)); ?>
<div class="section-head">
  <h2>کارت‌ها (<?= fa_num_format($list['total']) ?>)</h2>
</div>

<form method="get" action="<?= e(base_url('admin/cards')) ?>" class="search-bar">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="جستجوی نام، شرکت یا کد...">
  <button class="btn btn-primary btn-sm" type="submit"><?= icon_svg('search', 16); ?> جستجو</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کارت</th><th>کد</th><th>کاربر</th><th>بازدید</th><th>وضعیت</th><th>ساخته شده</th><th>عملیات</th></tr></thead>
    <tbody>
      <?php foreach ($list['rows'] as $c): ?>
      <tr>
        <td><div class="td-user"><div class="mini-qr-sm"><img src="<?= e(card_qr_url($c, 'px=8')) ?>" alt=""></div><div><strong><?= e($c['full_name'] ?: 'بدون نام') ?></strong><span class="td-sub"><?= e($c['company']) ?></span></div></div></td>
        <td dir="ltr"><?= e($c['code']) ?></td>
        <td><?= e($c['owner_name']) ?></td>
        <td><?= fa_num_format($c['visits']) ?></td>
        <td><span class="chip <?= (int)$c['active'] === 1 ? 'chip-on' : 'chip-off' ?>"><?= (int)$c['active'] === 1 ? 'فعال' : 'غیرفعال' ?></span></td>
        <td><?= fa_date(strtotime($c['created_at'])) ?></td>
        <td>
          <div class="td-actions">
            <a class="icon-btn" target="_blank" rel="noopener" href="<?= e(card_public_url($c)) ?>" title="مشاهده"><?= icon_svg('eye', 16); ?></a>
            <label class="mini-switch" title="<?= (int)$c['active'] === 1 ? 'غیرفعال کردن' : 'فعال کردن' ?>">
              <input type="checkbox" class="js-card-toggle" <?= (int)$c['active'] === 1 ? 'checked' : '' ?> data-url="<?= e(base_url('admin/card/toggle?id=' . $c['id'])) ?>" data-csrf="<?= e(csrf_token()) ?>">
              <span class="ms-track"></span>
            </label>
            <details class="reset-box">
              <summary class="icon-btn" title="تغییر کد کوتاه"><?= icon_svg('link', 16); ?></summary>
              <div class="reset-form">
                <form method="post" action="<?= e(base_url('admin/card/code?id=' . $c['id'])) ?>">
                  <?= csrf_field() ?>
                  <input type="text" name="code" value="<?= e($c['code']) ?>" maxlength="16" dir="ltr" pattern="[A-Za-z0-9]{4,16}" title="فقط حروف انگلیسی و اعداد" required>
                  <button class="btn btn-primary btn-sm" type="submit">ذخیره کد</button>
                </form>
              </div>
            </details>
            <form method="post" action="<?= e(base_url('admin/card/delete?id=' . $c['id'])) ?>" class="inline-form" data-confirm="این کارت برای همیشه حذف می‌شود. ادامه می‌دهید؟">
              <?= csrf_field() ?>
              <button class="icon-btn icon-danger" title="حذف"><?= icon_svg('trash', 16); ?></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pager">
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="pg <?= $i === $page ? 'on' : '' ?>" href="<?= e(base_url('admin/cards?page=' . $i . ($search ? '&q=' . urlencode($search) : ''))) ?>"><?= fa_num($i) ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
