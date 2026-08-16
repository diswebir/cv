<?php
// Template: بیزینسی
$name = $card['full_name'] ?: 'کارت ویزیت';
$logoPos = card_logo_pos($card);
card_head($name, $card);
?>
<main class="card-shell tpl-business">
  <header class="card-header business-head">
    <div class="card-cover"></div>
    <span class="biz-corner c-tl"></span><span class="biz-corner c-tr"></span><span class="biz-corner c-bl"></span><span class="biz-corner c-br"></span>
    <div class="biz-id logo-<?= $logoPos ?>">
      <?= c_logo($card) ?>
      <div class="id-text">
        <h1 class="name"><?= e($card['full_name']) ?></h1>
        <?php if ($card['job_title']): ?><p class="role"><?= e($card['job_title']) ?></p><?php endif; ?>
        <?php if ($card['company']): ?><p class="company"><?= e($card['company']) ?></p><?php endif; ?>
      </div>
    </div>
    <span class="minimal-line"></span>
  </header>

  <div class="card-body">
    <?= c_save_btn($card) ?>
    <?php c_actions($card, $socials); ?>
    <?php c_bio($card); ?>
    <div class="float-card">
      <?php c_info_rows($card); ?>
    </div>
    <?php c_socials($socials); ?>
    <?php c_map($card); ?>
    <?php c_custom_fields($fields); ?>
    <?php c_qr_block($card); ?>
    <?php c_share($card); ?>
  </div>
  <?php c_footer(); ?>
</main>
<script src="<?= e(asset('js/card.js')) ?>"></script>
</body>
</html>
