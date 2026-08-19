<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <span class="auth-icon"><?= icon_svg('user', 26); ?></span>
      <h2>ساخت حساب کاربری</h2>
      <p>در چند ثانیه کارت ویزیت خود را بسازید</p>
    </div>
    <?php if (!empty($error)): ?>
    <div class="alert alert-error" role="alert" aria-live="polite"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" class="auth-form" autocomplete="on" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="name">نام و نام خانوادگی</label>
        <input type="text" id="name" name="name" value="<?= e($data['name'] ?? '') ?>" required placeholder="مثلاً رامین احمدی" aria-describedby="name-error" <?= $nameError !== '' ? 'aria-invalid="true"' : '' ?>>
        <?php if (!empty($nameError)): ?><span id="name-error" class="field-error" role="alert" aria-live="polite"><?= e($nameError) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label for="email">ایمیل</label>
        <input type="email" id="email" name="email" value="<?= e($data['email'] ?? '') ?>" required placeholder="you@example.com" dir="ltr" aria-describedby="email-error" <?= $emailError !== '' ? 'aria-invalid="true"' : '' ?>>
        <?php if (!empty($emailError)): ?><span id="email-error" class="field-error" role="alert" aria-live="polite"><?= e($emailError) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label for="password">رمز عبور (حداقل ۸ کاراکتر)</label>
        <div class="pass-wrap">
          <input type="password" id="password" name="password" required placeholder="••••••••" dir="ltr"
            data-strength data-strength-bar="#strengthBar" data-strength-text="#strengthText" aria-describedby="password-error" <?= $passwordError !== '' ? 'aria-invalid="true"' : '' ?>>
          <button type="button" class="pass-toggle" data-target="password" aria-label="نمایش رمز"><?= icon_svg('eye', 18); ?></button>
        </div>
        <div class="pass-strength"><div class="pass-strength-bar" id="strengthBar"></div></div>
        <div class="pass-strength-text" id="strengthText"></div>
        <?php if (!empty($passwordError)): ?><span id="password-error" class="field-error" role="alert" aria-live="polite"><?= e($passwordError) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label for="password2">تکرار رمز عبور</label>
        <div class="pass-wrap">
          <input type="password" id="password2" name="password2" required placeholder="••••••••" dir="ltr" aria-describedby="password2-error" <?= $password2Error !== '' ? 'aria-invalid="true"' : '' ?>>
          <button type="button" class="pass-toggle" data-target="password2" aria-label="نمایش رمز"><?= icon_svg('eye', 18); ?></button>
        </div>
        <?php if (!empty($password2Error)): ?><span id="password2-error" class="field-error" role="alert" aria-live="polite"><?= e($password2Error) ?></span><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">ساخت حساب</button>
    </form>
    <p class="auth-alt">قبلاً ثبت‌نام کرده‌اید؟ <a href="<?= e(base_url('login')) ?>">وارد شوید</a></p>
  </div>
</div>
