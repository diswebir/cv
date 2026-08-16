<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <span class="auth-icon"><?= icon_svg('user', 26); ?></span>
      <h2>ساخت حساب کاربری</h2>
      <p>در چند ثانیه کارت ویزیت خود را بسازید</p>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="auth-form" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field">
        <label for="name">نام و نام خانوادگی</label>
        <input type="text" id="name" name="name" value="<?= e($data['name'] ?? '') ?>" required placeholder="مثلاً رامین احمدی">
      </div>
      <div class="field">
        <label for="email">ایمیل</label>
        <input type="email" id="email" name="email" value="<?= e($data['email'] ?? '') ?>" required placeholder="you@example.com" dir="ltr">
      </div>
      <div class="field">
        <label for="password">رمز عبور (حداقل ۶ کاراکتر)</label>
        <div class="pass-wrap">
          <input type="password" id="password" name="password" required placeholder="••••••••" dir="ltr"
            data-strength data-strength-bar="#strengthBar" data-strength-text="#strengthText">
          <button type="button" class="pass-toggle" data-target="password" aria-label="نمایش رمز"><?= icon_svg('eye', 18); ?></button>
        </div>
        <div class="pass-strength"><div class="pass-strength-bar" id="strengthBar"></div></div>
        <div class="pass-strength-text" id="strengthText"></div>
      </div>
      <div class="field">
        <label for="password2">تکرار رمز عبور</label>
        <div class="pass-wrap">
          <input type="password" id="password2" name="password2" required placeholder="••••••••" dir="ltr">
          <button type="button" class="pass-toggle" data-target="password2" aria-label="نمایش رمز"><?= icon_svg('eye', 18); ?></button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">ساخت حساب</button>
    </form>
    <p class="auth-alt">قبلاً ثبت‌نام کرده‌اید؟ <a href="<?= e(base_url('login')) ?>">وارد شوید</a></p>
  </div>
</div>
