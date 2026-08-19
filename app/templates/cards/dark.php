<?php
// Template: تیره
$name = $card['full_name'] ?: 'کارت ویزیت';
$logoPos = card_logo_pos($card);
card_head($name, $card);
?>
<main class="card-shell tpl-dark" id="cardMain">
  <header class="card-header">
    <div class="card-cover"></div>
    <div class="card-id logo-<?= $logoPos ?>">
      <?= c_logo($card) ?>
      <div class="id-text">
        <h1 class="name"><?= e($card['full_name']) ?></h1>
        <?php if ($card['job_title']): ?><p class="role"><?= e($card['job_title']) ?></p><?php endif; ?>
        <?php if ($card['company']): ?><p class="company"><?= icon_svg('bag', 14); ?> <?= e($card['company']) ?></p><?php endif; ?>
      </div>
    </div>
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

