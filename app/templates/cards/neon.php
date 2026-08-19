<?php
// Template: نئونی
$name = $card['full_name'] ?: 'کارت ویزیت';
$logoPos = card_logo_pos($card);
card_head($name, $card);
?>
<main class="card-shell tpl-neon" id="cardMain">
  <div class="neon-bg"></div>
  <header class="card-header neon-head">
    <div class="card-cover"></div>
    <div class="neon-logo logo-<?= $logoPos ?>">
      <?= c_logo($card) ?>
    </div>
    <h1 class="name neon-name"><?= e($card['full_name']) ?></h1>
    <?php if ($card['job_title']): ?><p class="role"><?= e($card['job_title']) ?></p><?php endif; ?>
    <?php if ($card['company']): ?><p class="company"><?= e($card['company']) ?></p><?php endif; ?>
  </header>

  <div class="card-body">
    <?= c_save_btn($card) ?>
    <?php c_actions($card, $socials); ?>
    <?php c_bio($card); ?>
    <div class="glass">
      <?php c_info_rows($card); ?>
    </div>
    <?php c_socials($socials); ?>
    <div class="glass">
      <?php c_map($card); ?>
      <?php c_custom_fields($fields); ?>
    </div>
    <?php c_qr_block($card); ?>
    <?php c_share($card); ?>
  </div>
  <?php c_footer(); ?>
</main>
<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/card.js')) ?>"></script>
</body>
</html>

