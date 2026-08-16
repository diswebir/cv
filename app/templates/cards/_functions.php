<?php
/**
 * Shared helpers for public card templates (app/templates/cards/*.php)
 */

/**
 * Validate a hex color (#RRGGBB). Returns the safe default if the value does
 * not match, so it can be embedded into CSS attributes without escaping risk
 * even when the DB row was tampered with outside the form handler.
 */
function safe_hex_color($value, $default = '#4f46e5') {
    $v = (string)$value;
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
}

function hex_rgb($hex) {
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return array(79, 70, 229);
    return array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
}

function mix_hex($a, $ratio, $b) {
    $ra = hex_rgb($a);
    $rb = hex_rgb($b);
    $r = (int)round($ra[0] * $ratio + $rb[0] * (1 - $ratio));
    $g = (int)round($ra[1] * $ratio + $rb[1] * (1 - $ratio));
    $bl = (int)round($ra[2] * $ratio + $rb[2] * (1 - $ratio));
    return sprintf('#%02x%02x%02x', min(255, $r), min(255, $g), min(255, $bl));
}

function rgba_hex($hex, $alpha) {
    $c = hex_rgb($hex);
    return 'rgba(' . $c[0] . ',' . $c[1] . ',' . $c[2] . ',' . $alpha . ')';
}

// رنگ‌های مشتق‌شده از رنگ‌های کارت به‌صورت مقادیر ثابت خروجی می‌شوند تا
// روی همه‌ی مرورگرها (بدون نیاز به color-mix) یکسان کار کنند.
function card_theme_style($c1, $c2) {
    return '.card-page{'
        . '--c1:' . $c1 . ';'
        . '--c2:' . $c2 . ';'
        . '--c1-soft:' . mix_hex($c1, .10, '#ffffff') . ';'
        . '--c2-soft:' . mix_hex($c2, .10, '#ffffff') . ';'
        . '--c1-light:' . mix_hex($c1, .55, '#ffffff') . ';'
        . '--c2-light:' . mix_hex($c2, .55, '#ffffff') . ';'
        . '--c1-bright:' . mix_hex($c1, .70, '#ffffff') . ';'
        . '--c1-dim:' . mix_hex($c1, .26, '#151d33') . ';'
        . '--c1-cream:' . mix_hex($c1, .30, '#e7d3a0') . ';'
        . '--c1-deep:' . mix_hex($c1, .82, '#000000') . ';'
        . '--c1-faint:' . rgba_hex($c1, .12) . ';'
        . '--c2-faint:' . rgba_hex($c2, .12) . ';'
        . '--c1-glow:' . rgba_hex($c1, .40) . ';'
        . '--c2-glow:' . rgba_hex($c2, .40) . ';'
        . '--c1-haze:' . rgba_hex($c1, .08) . ';'
        . '--grad:linear-gradient(135deg,' . $c1 . ',' . $c2 . ');'
        . '}';
}

function card_head($title, $card) {
    $c1 = safe_hex_color($card['color1'] ?? '', '#4f46e5');
    $c2 = safe_hex_color($card['color2'] ?? '', '#7c3aed');
    $cover = $card['cover'] ? upload_url($card['cover']) : '';
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="fa" dir="rtl">' . "\n";
    echo '<head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="format-detection" content="telephone=no">';
    echo '<title>' . e($title) . '</title>';
    echo '<meta name="theme-color" content="' . e($c1) . '">';
    echo '<link rel="icon" type="image/svg+xml" href="' . e(asset('img/favicon.svg')) . '">';
    echo '<link rel="stylesheet" href="' . e(asset('css/card.css')) . '">';
    $tpl = isset($card['template']) && preg_match('/^[a-z0-9-]{1,40}$/', (string)$card['template']) ? $card['template'] : 'classic';
    echo '<style>' . card_theme_style($c1, $c2);
    if ($cover !== '') {
        echo '.card-page .card-cover{background-image:linear-gradient(180deg,rgba(10,10,30,.32),rgba(10,10,30,.03) 45%,var(--c1-glow)),url("' . e($cover) . '");background-size:cover;background-position:center;}';
    }
    echo '</style>';
    echo '</head><body class="card-page tpl-' . $tpl . '">';
}

function card_initials($name) {
    $n = trim((string)$name);
    if ($n === '') return '؟';
    $first = mb_substr($n, 0, 1);
    $parts = preg_split('/\s+/', $n);
    if (count($parts) > 1) $first = mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1);
    return $first;
}

// جایگاه لوگو: 'center' | 'right' | 'left'
function card_logo_pos($card) {
    $p = isset($card['logo_pos']) ? $card['logo_pos'] : 'center';
    return in_array($p, array('center', 'left', 'right'), true) ? $p : 'center';
}

// لوگو در همان جایگاه عکس پروفایل؛ اگر لوگویی نباشد حروف اول نام نمایش داده می‌شود.
function c_logo($card, $size = '') {
    if (!empty($card['logo'])) {
        return '<img class="card-logo ' . $size . '" src="' . e(upload_url($card['logo'])) . '" alt="' . e($card['full_name']) . '" loading="lazy">';
    }
    return '<span class="card-logo card-logo-ph ' . $size . '">' . e(card_initials($card['full_name'])) . '</span>';
}

function c_save_btn($card) {
    $vcfUrl = base_url('vcf/' . $card['code']);
    return '<a class="save-btn" href="' . e($vcfUrl) . '">' . icon_svg('contact', 18) . '<span>افزودن به مخاطبین</span></a>';
}

function c_actions($card, $socials) {
    $btns = array();
    $tel1 = 'tel:' . preg_replace('/[^0-9+]/', '', digits_to_latin($card['phone']));
    if ($card['phone']) {
        $btns[] = array('href' => $tel1, 'icon' => 'phone', 'label' => 'تماس');
    }
    if ($card['phone']) {
        $btns[] = array('href' => whatsapp_link($card['phone']), 'icon' => 'whatsapp', 'label' => 'واتساپ');
    }
    if (!empty($socials['telegram'])) {
        $btns[] = array('href' => social_detect('telegram', $socials['telegram']), 'icon' => 'telegram', 'label' => 'تلگرام');
    }
    if ($card['email']) {
        $btns[] = array('href' => 'mailto:' . $card['email'], 'icon' => 'email', 'label' => 'ایمیل');
    }
    if (!$btns) return '';
    echo '<div class="act-row">';
    foreach ($btns as $b) {
        echo '<a class="act-btn" href="' . e($b['href']) . '" target="_blank" rel="noopener">' . icon_svg($b['icon'], 19) . '<span>' . e($b['label']) . '</span></a>';
    }
    echo '</div>';
}

function c_info_rows($card) {
    $rows = array();
    if ($card['phone']) $rows[] = array('icon' => 'phone', 'label' => 'موبایل', 'value' => $card['phone'], 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', digits_to_latin($card['phone'])));
    if ($card['phone2']) $rows[] = array('icon' => 'phone', 'label' => 'تلفن دوم', 'value' => $card['phone2'], 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', digits_to_latin($card['phone2'])));
    if ($card['email']) $rows[] = array('icon' => 'email', 'label' => 'ایمیل', 'value' => $card['email'], 'href' => 'mailto:' . $card['email']);
    if ($card['website']) $rows[] = array('icon' => 'globe', 'label' => 'وب‌سایت', 'value' => preg_replace('#^https?://#', '', $card['website']), 'href' => normalize_url($card['website']));
    if ($card['address']) $rows[] = array('icon' => 'map-pin', 'label' => 'آدرس', 'value' => $card['address'], 'href' => '');
    if (!$rows) return;
    echo '<ul class="info-list">';
    foreach ($rows as $r) {
        $href = $r['href'] !== '' ? ' href="' . e($r['href']) . '"' : '';
        $target = strpos($r['href'], 'mailto:') === 0 ? '' : ' target="_blank" rel="noopener"';
        echo '<li><a' . $href . $target . '><span class="il-icon">' . icon_svg($r['icon'], 18) . '</span><span class="il-body"><span class="il-label">' . e($r['label']) . '</span><span class="il-value" dir="auto">' . e($r['value']) . '</span></span>' . ($href ? '<span class="il-arrow">' . icon_svg('chevron-left', 16) . '</span>' : '') . '</a></li>';
    }
    echo '</ul>';
}

function c_socials($socials) {
    $ordered = array('instagram', 'telegram', 'whatsapp', 'linkedin', 'twitter', 'youtube', 'tiktok', 'facebook', 'github', 'aparat', 'threads', 'snapchat', 'pinterest', 'website');
    echo '<div class="socials-row">';
    foreach ($ordered as $k) {
        if (empty($socials[$k])) continue;
        $href = social_detect($k, $socials[$k]);
        if ($href === '') continue;
        echo '<a class="soc-btn soc-' . e($k) . '" href="' . e($href) . '" target="_blank" rel="noopener" aria-label="' . e($k) . '">' . icon_svg($k, 19) . '</a>';
    }
    echo '</div>';
}

function c_bio($card) {
    if ($card['bio'] === '') return;
    echo '<section class="card-block bio-block"><h3 class="blk-title">درباره</h3><p class="bio-text">' . nl2br(e($card['bio'])) . '</p></section>';
}

function c_map($card) {
    if (!$card['map_lat'] || !$card['map_lng']) return;
    $lat = (float)$card['map_lat'];
    $lng = (float)$card['map_lng'];
    $embed = 'https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&z=16&output=embed';
    $open = 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    echo '<section class="card-block map-block">';
    echo '<h3 class="blk-title">' . icon_svg('map-pin', 17) . ($card['map_address'] ? e($card['map_address']) : 'موقعیت مکانی') . '</h3>';
    echo '<div class="map-frame"><iframe src="' . e($embed) . '" loading="lazy" title="نقشه"></iframe></div>';
    echo '<a class="map-open" href="' . e($open) . '" target="_blank" rel="noopener">' . icon_svg('map', 16) . ' باز کردن مسیر در نقشه</a>';
    echo '</section>';
}

function c_custom_fields($fields) {
    if (!$fields) return;
    echo '<section class="card-block custom-block"><h3 class="blk-title">اطلاعات تکمیلی</h3><ul class="info-list">';
    foreach ($fields as $f) {
        echo '<li><span class="il-icon">' . icon_svg('star', 17) . '</span><span class="il-body"><span class="il-label">' . e($f['label']) . '</span><span class="il-value" dir="auto">' . e($f['value']) . '</span></span></li>';
    }
    echo '</ul></section>';
}

function c_qr_block($card) {
    $qr = card_qr_url($card, 'px=13');
    echo '<section class="card-block qr-block">';
    echo '<h3 class="blk-title">اسکن کنید تا کارت من را ببینید</h3>';
    echo '<div class="qr-inner"><img src="' . e($qr) . '" alt="کد QR کارت" loading="lazy"></div>';
    echo '<a class="qr-link" href="' . e(card_public_url($card)) . '" dir="ltr">' . e(card_public_url($card)) . '</a>';
    echo '</section>';
}

function c_share($card) {
    $url = card_public_url($card);
    $text = 'کارت ویزیت ' . ($card['full_name'] ?: '') . ' — ' . $url;
    $wa = 'https://wa.me/?text=' . rawurlencode($text);
    $tg = 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode('کارت ویزیت ' . ($card['full_name'] ?: ''));
    echo '<section class="share-block">';
    echo '<span class="share-label">اشتراک‌گذاری کارت</span>';
    echo '<div class="share-row">';
    echo '<a class="share-btn" href="' . e($wa) . '" target="_blank" rel="noopener" aria-label="واتساپ">' . icon_svg('whatsapp', 18) . '</a>';
    echo '<a class="share-btn" href="' . e($tg) . '" target="_blank" rel="noopener" aria-label="تلگرام">' . icon_svg('telegram', 18) . '</a>';
    echo '<button type="button" class="share-btn js-share" data-url="' . e($url) . '" aria-label="اشتراک‌گذاری">' . icon_svg('share', 18) . '</button>';
    echo '<button type="button" class="share-btn js-copy" data-copy="' . e($url) . '" aria-label="کپی لینک">' . icon_svg('copy', 18) . '</button>';
    echo '</div></section>';
}

function c_footer() {
    $app = (string)get_setting('app_name', 'کارت ویزیت من');
    echo '<footer class="card-foot"><a href="' . e(base_url('')) . '">ساخته شده با ' . e($app) . '</a></footer>';
}
