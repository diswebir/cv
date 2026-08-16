<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="settings-grid">
  <form method="post" class="panel-section settings-form">
    <?= csrf_field() ?>
    <div class="section-head"><h2>تنظیمات عمومی</h2></div>
    <div class="form-grid">
      <div class="field span2">
        <label>نام سایت</label>
        <input type="text" name="app_name" value="<?= e(get_setting('app_name', 'کارت ویزیت من')) ?>" maxlength="80">
      </div>
      <div class="field span2">
        <label>آدرس پایه (Base URL) — مبنای لینک‌های کوتاه</label>
        <input type="url" name="base_url" value="<?= e(get_setting('base_url', $detected)) ?>" dir="ltr">
        <p class="form-hint">اگر سایت را جابه‌جا کردید (مثلاً از ریشه به یک زیرپوشه)، اینجا را تغییر دهید. آدرس فعلی تشخیص داده شده: <code dir="ltr"><?= e($detected) ?></code></p>
      </div>
      <div class="field span2">
        <label>متن فوتر (اختیاری)</label>
        <input type="text" name="footer_text" value="<?= e(get_setting('footer_text', '')) ?>" maxlength="500">
      </div>
      <div class="field span2">
        <label>طول کد کوتاه (لینک کارت‌ها)</label>
        <input type="number" name="code_length" min="4" max="12" value="<?= e(get_setting('code_length', '6')) ?>" dir="ltr">
        <p class="form-hint">تعداد کاراکتر کد خودکارِ لینک کوتاه هر کارت (بین ۴ تا ۱۲). فقط روی کارت‌های <b>جدید</b> اعمال می‌شود؛ کدهای موجود تغییری نمی‌کنند.</p>
      </div>
      <div class="field span2">
        <div class="check-line"><input type="checkbox" name="allow_registration" value="1" id="allowReg" <?= get_setting('allow_registration', '1') === '1' ? 'checked' : '' ?>> <label for="allowReg">ثبت‌نام کاربران جدید فعال باشد</label></div>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
  </form>

  <div class="panel-section">
    <div class="section-head"><h2>نمونه لینک‌های کوتاه</h2></div>
    <div class="sample-links">
      <div class="sl-item"><span>نمونه کارت:</span><code dir="ltr"><?= e(base_url('AbC123')) ?></code></div>
      <div class="sl-item"><span>نمونه QR:</span><code dir="ltr"><?= e(get_setting('base_url', $detected)) ?>/qr/AbC123.png</code></div>
      <div class="sl-item"><span>دانلود مخاطب:</span><code dir="ltr"><?= e(get_setting('base_url', $detected)) ?>/vcf/AbC123</code></div>
    </div>
    <p class="form-hint">پس از تغییر آدرس پایه، لینک‌های قدیمی منتشرشده ممکن است دیگر کار نکنند؛ کد QR را دوباره دانلود کنید.</p>
  </div>

  <div class="panel-section">
    <div class="section-head"><h2>نکات امنیتی</h2></div>
    <ul class="tips-list">
      <li>فایل <b>install.php</b> را از سرور حذف کنید (مهم).</li>
      <li>فایل <b>includes/config.php</b> حاوی رمز دیتابیس است — آن را با کسی به اشتراک نگذارید.</li>
      <li>پسورد مدیر را به‌صورت دوره‌ای عوض کنید.</li>
    </ul>
  </div>
</div>
