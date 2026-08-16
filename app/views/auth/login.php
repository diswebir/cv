<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <span class="auth-icon"><?= icon_svg('login', 26); ?></span>
      <h2>ورود به حساب</h2>
      <p>خوش آمدید! برای ادامه وارد شوید</p>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="auth-form" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">ایمیل</label>
        <input type="email" id="email" name="email" value="<?= e($email ?? '') ?>" required placeholder="you@example.com" dir="ltr">
      </div>
      <div class="field">
        <label for="password">رمز عبور</label>
        <div class="pass-wrap">
          <input type="password" id="password" name="password" required placeholder="••••••••" dir="ltr">
          <button type="button" class="pass-toggle" data-target="password" aria-label="نمایش رمز"><?= icon_svg('eye', 18); ?></button>
        </div>
      </div>
      <div class="remember-line">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">مرا به خاطر بسپار (۳۰ روز)</label>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">ورود</button>
    </form>
    <p class="auth-alt">حساب ندارید؟ <a href="<?= e(base_url('register')) ?>">ثبت‌نام کنید</a></p>
  </div>
</div>
