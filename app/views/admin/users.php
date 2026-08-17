<?php $totalPages = max(1, (int)ceil($list['total'] / 15));
$addError = isset($addError) ? $addError : '';
$addData  = isset($addData) && is_array($addData) ? $addData : array();
$addOpen  = ($addError !== '' || !empty($addData)); ?>
<div class="section-head">
  <h2>کاربران (<?= fa_num_format($list['total']) ?>)</h2>
</div>

<?php if ($addError !== ''): ?>
<div class="alert alert-danger" role="alert"><?= e($addError) ?></div>
<?php endif; ?>

<form method="get" action="<?= e(base_url('admin/users')) ?>" class="search-bar">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="جستجوی نام یا ایمیل...">
  <button class="btn btn-primary btn-sm" type="submit"><?= icon_svg('search', 16); ?> جستجو</button>
</form>

<div class="panel-section">
  <details class="add-box"<?= $addOpen ? ' open' : '' ?>>
    <summary class="btn btn-sm btn-primary" style="list-style:none;display:inline-flex"><?= icon_svg('plus', 16); ?> افزودن کاربر جدید</summary>
    <div class="add-form">
      <form method="post" action="<?= e(base_url('admin/user/add?id=0')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>نام</label><input type="text" name="name" value="<?= e($addData['name'] ?? '') ?>" required></div>
          <div class="field"><label>ایمیل</label><input type="email" name="email" value="<?= e($addData['email'] ?? '') ?>" required dir="ltr"></div>
          <div class="field"><label>رمز عبور</label><input type="password" name="password" required dir="ltr"></div>
          <div class="field"><label>نقش</label>
            <select name="role"><option value="user"<?= (($addData['role'] ?? 'user') === 'user') ? ' selected' : '' ?>>کاربر</option><option value="admin"<?= (($addData['role'] ?? '') === 'admin') ? ' selected' : '' ?>>مدیر</option></select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">ساخت کاربر</button>
      </form>
    </div>
  </details>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>وضعیت</th><th>کارت‌ها</th><th>تاریخ عضویت</th><th>عملیات</th></tr></thead>
    <tbody>
      <?php foreach ($list['rows'] as $u): ?>
      <tr>
        <td><div class="td-user"><div class="su-avatar sm"><?= e(mb_substr($u['name'], 0, 1)) ?></div><strong><?= e($u['name']) ?></strong></div></td>
        <td dir="ltr"><?= e($u['email']) ?></td>
        <td><span class="chip <?= $u['role'] === 'admin' ? 'chip-admin' : 'chip-user' ?>"><?= $u['role'] === 'admin' ? 'مدیر' : 'کاربر' ?></span></td>
        <td><span class="chip <?= (int)$u['status'] === 1 ? 'chip-on' : 'chip-off' ?>"><?= (int)$u['status'] === 1 ? 'فعال' : 'مسدود' ?></span></td>
        <td><?= fa_num_format(count_user_cards($u['id'])) ?></td>
        <td><?= fa_date(strtotime($u['created_at'])) ?></td>
        <td>
          <div class="td-actions">
            <form method="post" action="<?= e(base_url('admin/user/toggle?id=' . $u['id'])) ?>" class="inline-form">
              <?= csrf_field() ?>
              <button class="icon-btn" aria-label="<?= (int)$u['status'] === 1 ? 'مسدود کردن' : 'فعال کردن' ?>"><?= icon_svg((int)$u['status'] === 1 ? 'close' : 'check', 16); ?></button>
            </form>
            <button type="button" class="icon-btn js-reset-pwd" data-url="<?= e(base_url('admin/user/reset?id=' . $u['id'])) ?>" data-user-name="<?= e($u['name']) ?>" aria-label="تغییر رمز"><?= icon_svg('lock', 16); ?></button>
            <form method="post" action="<?= e(base_url('admin/user/delete?id=' . $u['id'])) ?>" class="inline-form" data-confirm="کاربر و تمام کارت‌هایش حذف می‌شوند. ادامه می‌دهید؟">
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
    <a class="pg <?= $i === $page ? 'on' : '' ?>" href="<?= e(base_url('admin/users?page=' . $i . ($search ? '&q=' . urlencode($search) : ''))) ?>"><?= fa_num($i) ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Password Reset Modal Template -->
<div id="resetPwdModal" class="modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-labelledby="resetPwdTitle">
  <div class="modal-card">
    <div class="modal-icon" style="background:#eef2ff;color:var(--p1)"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 17v-5m0-3v-.5M10.3 3.9 2.3 18a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    <h3 id="resetPwdTitle">تغییر رمز عبور</h3>
    <p>برای کاربر <strong id="resetPwdUserName"></strong> رمز جدید تعیین کنید.</p>
    <form id="resetPwdForm" method="post" action="">
      <?= csrf_field() ?>
      <div class="field" style="margin-top:16px">
        <label>رمز جدید</label>
        <input type="password" name="password" required dir="ltr" autocomplete="new-password" placeholder="حداقل ۶ کاراکتر">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost js-modal-close">انصراف</button>
        <button type="submit" class="btn btn-primary">تغییر رمز</button>
      </div>
    </form>
  </div>
</div>
