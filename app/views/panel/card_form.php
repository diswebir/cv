<?php
function fval($card, $key, $default = '') {
    $p = post($key);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key])) return $p;
    return $card ? ($card[$key] ?? $default) : $default;
}
function fsocial($socials, $k) {
    $p = post('social_' . $k);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['social_' . $k])) return $p;
    return isset($socials[$k]) ? $socials[$k] : '';
}
function is_checked_val($key, $on, $off = false) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') return post($key) === '1';
    return $off ? (int)$on === 0 : (int)$on === 1;
}
$templates = array(
    'classic' => 'کلاسیک', 'dark' => 'تیره', 'minimal' => 'مینیمال',
    'gradient' => 'گرادیانی', 'business' => 'بیزینسی', 'neon' => 'نئونی',
);
$dots = array('square' => 'مربعی', 'round' => 'گرد', 'circle' => 'دایره‌ای');
$qrUrl = base_url('qr/_.png');
$demoData = 'https://example.com';
?>
<div class="editor-wrap">
  <div class="editor-tabs" id="editorTabs" role="tablist" aria-label="بخش‌های ویرایشگر کارت">
    <button type="button" class="et on" role="tab" id="tab-info" aria-selected="true" aria-controls="panel-info" tabindex="0" data-tab="info">اطلاعات</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="contact">تماس</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="photos">تصاویر</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="social">شبکه‌ها</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="location">لوکیشن</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="custom">فیلدها</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="design">طراحی</button>
    <button type="button" class="et" role="tab" aria-selected="false" tabindex="0" data-tab="qr">کد QR</button>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="editor-form" id="cardForm">
    <?= csrf_field() ?>
    <?php if (!$card): ?><input type="hidden" name="code" value="<?= e($preCode) ?>"><?php endif; ?>

    <!-- ===== اطلاعات ===== -->
    <div class="etab" data-panel="info" role="tabpanel" aria-labelledby="tab-info" tabindex="0">
      <div class="etab-head"><h3>اطلاعات اصلی</h3><p>نام و مشخصات شغلی که روی کارت نمایش داده می‌شود.</p></div>
      <div class="form-grid">
        <div class="field span2"><label>نام و نام خانوادگی *</label><input type="text" name="full_name" value="<?= e(fval($card, 'full_name')) ?>" placeholder="مثلاً رامین احمدی" required></div>
        <div class="field"><label>سمت / عنوان شغلی</label><input type="text" name="job_title" value="<?= e(fval($card, 'job_title')) ?>" placeholder="مثلاً طراح محصول"></div>
        <div class="field"><label>شرکت / کسب‌وکار</label><input type="text" name="company" value="<?= e(fval($card, 'company')) ?>" placeholder="نام شرکت شما"></div>
        <div class="field span2"><label>درباره / توضیحات</label><textarea name="bio" rows="4" placeholder="چند خط درباره خودتان یا کسب‌وکارتان"><?= e(fval($card, 'bio')) ?></textarea></div>
      </div>
    </div>

    <!-- ===== تماس ===== -->
    <div class="etab" data-panel="contact" hidden>
      <div class="etab-head"><h3>اطلاعات تماس</h3><p>شماره‌ها و راه‌های ارتباطی که روی کارت به‌صورت دکمه نمایش داده می‌شوند.</p></div>
      <div class="form-grid">
        <div class="field"><label>موبایل</label><input type="text" name="phone" value="<?= e(fval($card, 'phone')) ?>" placeholder="0912 000 0000" dir="ltr"></div>
        <div class="field"><label>موبایل دوم</label><input type="text" name="phone2" value="<?= e(fval($card, 'phone2')) ?>" placeholder="تلفن ثابت یا شماره دوم" dir="ltr"></div>
        <div class="field"><label>ایمیل</label><input type="email" name="email" value="<?= e(fval($card, 'email')) ?>" placeholder="you@example.com" dir="ltr"></div>
        <div class="field"><label>وب‌سایت</label><input type="text" name="website" value="<?= e(fval($card, 'website')) ?>" placeholder="https://example.com" dir="ltr"></div>
        <div class="field span2"><label>آدرس</label><textarea name="address" rows="2" placeholder="آدرس کامل محل کار"><?= e(fval($card, 'address')) ?></textarea></div>
      </div>
    </div>

    <!-- ===== تصاویر ===== -->
    <div class="etab" data-panel="photos" hidden>
      <div class="etab-head"><h3>تصاویر کارت</h3><p>لوگو (برای مرکز QR) و تصویر کاور بالای کارت.</p></div>
      <div class="upload-grid">
        <div class="upload-box" data-for="logo" data-icon="star">
          <div class="ub-preview"><?php if ($card && $card['logo']): ?><img src="<?= e(upload_url($card['logo'])) ?>" alt=""><?php else: ?><span class="ub-ph-icon"><?= icon_svg('star', 30); ?></span><?php endif; ?></div>
          <div class="ub-label">لوگو (مرکز QR)</div>
          <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="ub-input" hidden>
          <input type="hidden" name="rm_logo" value="0" class="ub-rm">
          <div class="ub-btns">
            <button type="button" class="btn btn-sm btn-ghost ub-btn">انتخاب لوگو</button>
            <button type="button" class="btn btn-sm btn-ghost ub-remove danger-text <?= ($card && $card['logo']) ? '' : 'hidden' ?>">حذف</button>
          </div>
        </div>
        <div class="upload-box" data-for="cover" data-icon="image">
          <div class="ub-preview"><?php if ($card && $card['cover']): ?><img src="<?= e(upload_url($card['cover'])) ?>" alt=""><?php else: ?><span class="ub-ph-icon"><?= icon_svg('image', 30); ?></span><?php endif; ?></div>
          <div class="ub-label">تصویر کاور</div>
          <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="ub-input" hidden>
          <input type="hidden" name="rm_cover" value="0" class="ub-rm">
          <div class="ub-btns">
            <button type="button" class="btn btn-sm btn-ghost ub-btn">انتخاب کاور</button>
            <button type="button" class="btn btn-sm btn-ghost ub-remove danger-text <?= ($card && $card['cover']) ? '' : 'hidden' ?>">حذف</button>
          </div>
        </div>
      </div>
      <p class="form-hint">فرمت‌های مجاز: JPG، PNG و WebP — حداکثر ۵ مگابایت. اگر کاور انتخاب نکنید، رنگ‌های کارت به‌صورت گرادیان نمایش داده می‌شوند.</p>
    </div>

    <!-- ===== شبکه های اجتماعی ===== -->
    <div class="etab" data-panel="social" hidden>
      <div class="etab-head"><h3>شبکه‌های اجتماعی</h3><p>آدرس پروفایل یا شناسه (بدون @) هر شبکه را وارد کنید. فقط شبکه‌هایی که مقدار دارند نمایش داده می‌شوند.</p></div>
      <div class="form-grid">
        <?php
        $socialLabels = array(
            'instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'whatsapp' => 'واتساپ',
            'linkedin' => 'لینکدین', 'twitter' => 'ایکس / توییتر', 'youtube' => 'یوتیوب',
            'tiktok' => 'تیک‌تاک', 'facebook' => 'فیسبوک', 'github' => 'گیت‌هاب',
            'aparat' => 'آپارات', 'threads' => 'تردز', 'snapchat' => 'اسنپ‌چت',
            'pinterest' => 'پینترست', 'website' => 'وب‌سایت دوم',
        );
        foreach ($socialLabels as $k => $label): ?>
        <div class="field">
          <label><span class="soc-dot"><?= icon_svg($k, 15); ?></span> <?= $label ?></label>
          <input type="text" name="social_<?= e($k) ?>" value="<?= e(fsocial($socials, $k)) ?>" placeholder="<?= $k === 'whatsapp' ? 'شماره با کد کشور مثل 989120000000' : ($k === 'website' ? 'https://...' : 'آیدی یا آدرس') ?>" dir="ltr">
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ===== لوکیشن ===== -->
    <div class="etab" data-panel="location" hidden>
      <div class="etab-head"><h3>موقعیت مکانی</h3><p>نقشه در کارت نمایش داده می‌شود و بازدیدکننده با یک لمس مسیر را در اپلیکیشن نقشه باز می‌کند.</p></div>
      <div class="form-grid">
        <div class="field span2"><label>عنوان لوکیشن</label><input type="text" name="map_address" value="<?= e(fval($card, 'map_address')) ?>" placeholder="مثلاً دفتر مرکزی تهران، خیابان ولیعصر"></div>
        <div class="field"><label>عرض جغرافیایی (Latitude)</label><input type="text" name="map_lat" value="<?= e(fval($card, 'map_lat', '')) ?>" placeholder="35.6892" dir="ltr"></div>
        <div class="field"><label>طول جغرافیایی (Longitude)</label><input type="text" name="map_lng" value="<?= e(fval($card, 'map_lng', '')) ?>" placeholder="51.3890" dir="ltr"></div>
      </div>
      <p class="form-hint">راه سریع: موقعیت خود را در <a href="https://maps.google.com" target="_blank" rel="noopener">Google Maps</a> پیدا کنید، روی نقطه کلیک راست کنید و مختصات را کپی کنید؛ یا از طریق دکمه زیر لوکیشن فعلی مرورگر را بگیرید.</p>
      <button type="button" class="btn btn-sm btn-ghost" id="locBtn"><?= icon_svg('map-pin', 16); ?> گرفتن موقعیت فعلی</button>
    </div>

    <!-- ===== فیلدهای سفارشی ===== -->
    <div class="etab" data-panel="custom" hidden>
      <div class="etab-head"><h3>فیلدهای سفارشی</h3><p>مثلاً ساعات کاری، کد ملی شرکت، فکس، آدرس اینستاگرام دوم و هر چیز دیگری.</p></div>
      <div id="cfRows">
        <?php $cf = $_SERVER['REQUEST_METHOD'] === 'POST' ? array() : $fields;
        if ($cf): foreach ($cf as $i => $f): ?>
        <div class="cf-row">
          <input type="text" name="cf_label[]" placeholder="عنوان (مثلاً ساعات کاری)" value="<?= e($f['label']) ?>">
          <input type="text" name="cf_value[]" placeholder="مقدار" value="<?= e($f['value']) ?>">
          <button type="button" class="icon-btn icon-danger cf-remove" aria-label="حذف"><?= icon_svg('close', 16); ?></button>
        </div>
        <?php endforeach; endif; ?>
      </div>
      <button type="button" class="btn btn-sm btn-ghost" id="cfAdd"><?= icon_svg('plus', 16); ?> افزودن فیلد</button>
    </div>

    <!-- ===== طراحی ===== -->
    <div class="etab" data-panel="design" hidden>
      <div class="etab-head"><h3>قالب کارت</h3><p>قالب اصلی کارتی که بازدیدکنندگان می‌بینند.</p></div>
      <div class="theme-grid">
        <?php $sel = fval($card, 'template', 'classic'); foreach ($templates as $key => $label): ?>
        <label class="theme-item">
          <input type="radio" name="template" value="<?= e($key) ?>" <?= $sel === $key ? 'checked' : '' ?> class="hidden-input">
          <span class="tpl-ph tpl-<?= e($key) ?>"><span class="tp-cover"></span><span class="tp-line l1"></span><span class="tp-line l2"></span></span>
          <span class="theme-name"><?= e($label) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="etab-head" style="margin-top:22px"><h3>رنگ‌های اصلی کارت</h3><p>این دو رنگ در هدر، دکمه‌ها و گرادیان کارت استفاده می‌شوند.</p></div>
      <div class="color-row">
        <div class="color-pick"><label>رنگ اول</label><input type="color" name="color1" value="<?= e(fval($card, 'color1', '#4f46e5')) ?>"></div>
        <div class="color-pick"><label>رنگ دوم</label><input type="color" name="color2" value="<?= e(fval($card, 'color2', '#7c3aed')) ?>"></div>
      </div>
      <div class="etab-head" style="margin-top:22px"><h3>جایگاه لوگو</h3><p>محل نمایش لوگو در هدر کارت — وسط، راست یا چپ.</p></div>
      <div class="pos-row">
        <?php $posSel = fval($card, 'logo_pos', 'center'); ?>
        <label class="pos-item">
          <input type="radio" name="logo_pos" value="right" class="hidden-input" <?= $posSel === 'right' ? 'checked' : '' ?>>
          <span class="pos-thumb"><span class="pt-ico"></span><span class="pt-line"></span></span>
          <span class="pos-name">راست</span>
        </label>
        <label class="pos-item">
          <input type="radio" name="logo_pos" value="center" class="hidden-input" <?= $posSel === 'center' ? 'checked' : '' ?>>
          <span class="pos-thumb col"><span class="pt-ico"></span><span class="pt-line"></span></span>
          <span class="pos-name">وسط</span>
        </label>
        <label class="pos-item">
          <input type="radio" name="logo_pos" value="left" class="hidden-input" <?= $posSel === 'left' ? 'checked' : '' ?>>
          <span class="pos-thumb"><span class="pt-line"></span><span class="pt-ico"></span></span>
          <span class="pos-name">چپ</span>
        </label>
      </div>
      <div class="switch-line">
        <span class="switch-label">
          <b>وضعیت کارت</b>
          <small>کارت به‌صورت پیش‌فرض فعال است؛ لینک کوتاه و کد QR کار می‌کنند. برای غیرفعال کردن، سوییچ را خاموش کنید.</small>
        </span>
        <label class="switch" title="تغییر وضعیت کارت">
          <input type="checkbox" name="active" value="1" id="activeChk" <?= is_checked_val('active', fval($card, 'active', '1')) ? 'checked' : '' ?>>
          <span class="slider"></span>
        </label>
      </div>
    </div>

    <!-- ===== کد QR ===== -->
    <div class="etab" data-panel="qr" hidden>
      <div class="etab-head"><h3>کد QR کارت</h3><p>با این کد، لینک کوتاه کارت باز می‌شود. تم، شکل نقطه‌ها و لوگوی مرکز را خودتان انتخاب کنید.</p></div>
      <div class="qr-editor">
        <div class="qr-preview-side">
          <div class="qr-preview-box">
            <img id="qrPreview" src="" alt="پیش‌نمایش QR" width="220" height="220">
          </div>
          <div class="qr-dl-row">
            <a class="btn btn-sm btn-primary" id="qrDlPng" href="#" download>دانلود PNG</a>
            <a class="btn btn-sm btn-ghost" id="qrDlSvg" href="#" download>دانلود SVG</a>
          </div>
        </div>
        <div class="qr-options">
          <label class="f-label">تم رنگی</label>
          <div class="qr-theme-grid">
            <?php $qrSel = fval($card, 'qr_theme', 'classic'); foreach (VQR::themes_list() as $th): ?>
            <label class="qr-theme-item">
              <input type="radio" name="qr_theme" value="<?= e($th) ?>" <?= $qrSel === $th ? 'checked' : '' ?> class="hidden-input">
              <img src="<?= e($qrUrl) ?>?data=<?= rawurlencode($demoData) ?>&theme=<?= e($th) ?>&px=5" alt="<?= e($th) ?>" loading="lazy">
              <span><?= e($th) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <label class="f-label">شکل نقطه‌ها</label>
          <div class="dots-row">
            <?php $dotSel = fval($card, 'qr_dots', 'square'); foreach ($dots as $dk => $dl): ?>
            <label class="dot-item">
              <input type="radio" name="qr_dots" value="<?= e($dk) ?>" <?= $dotSel === $dk ? 'checked' : '' ?> class="hidden-input">
              <img src="<?= e($qrUrl) ?>?data=<?= rawurlencode($demoData) ?>&theme=classic&dots=<?= e($dk) ?>&px=5" alt="<?= e($dl) ?>" loading="lazy">
              <span><?= e($dl) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="check-line"><input type="checkbox" name="qr_logo" value="1" id="qrLogoChk" <?= is_checked_val('qr_logo', fval($card, 'qr_logo', '0')) ? 'checked' : '' ?> <?= $card && $card['logo'] ? '' : 'disabled' ?>> <label for="qrLogoChk">لوگو در مرکز QR (در صورت انتخاب لوگو)</label></div>
        </div>
      </div>
    </div>

    <div class="editor-save-bar">
      <button type="button" class="btn btn-lg btn-ghost" id="formPrevBtn" hidden>قبلی</button>
      <button type="button" class="btn btn-lg btn-primary" id="formNextBtn">بعدی</button>
      <span class="saved-hint">تغییرات ذخیره نشده دارید — برای اعمال، دکمه ذخیره را بزنید.</span>
      <button type="submit" class="btn btn-lg btn-primary" id="formSaveBtn"><?= $card ? 'ذخیره تغییرات' : 'ساخت کارت' ?></button>
      <a class="btn btn-lg btn-ghost" href="<?= e(base_url('panel')) ?>">انصراف</a>
    </div>
  </form>
</div>

<script>
window.VC_QR_BASE = <?= json_encode($qrUrl) ?>;
window.VC_CARD_URL = <?= json_encode(base_url($preCode)) ?>;
</script>
<script src="<?= e(asset('js/editor.js')) ?>"></script>
