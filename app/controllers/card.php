<?php

function public_card($code) {
    $card = get_card_by_code($code);
    if (!$card || (int)$card['active'] !== 1) not_found();
    log_visit((int)$card['id'], client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '');
    $socials = card_socials($card);
    $fields = card_custom_fields($card);
    $tpl = 'classic';
    if (is_file(VC_ROOT . '/app/templates/cards/' . $card['template'] . '.php')) $tpl = $card['template'];
    require VC_ROOT . '/app/templates/cards/_functions.php';
    require VC_ROOT . '/app/templates/cards/' . $tpl . '.php';
    exit;
}

function download_vcf($code) {
    $card = get_card_by_code($code);
    if (!$card) not_found();
    send_vcf($card);
}

function qr_image($segments) {
    $code = '';
    if (isset($segments[1])) {
        $file = $segments[1];
        if (substr($file, -4) === '.png') $code = substr($file, 0, -4);
        elseif (substr($file, -4) === '.svg') $code = substr($file, 0, -4);
        elseif (($dot = strpos($file, '.')) !== false) $code = substr($file, 0, $dot);
        else $code = $file;
    }
    $themeFromPath = null;
    if (isset($segments[2]) && preg_match('/^(.+)\.(png|svg)$/', $segments[2], $m)) $themeFromPath = $m[1];

    $card = $code !== '' ? get_card_by_code($code) : null;

    if ($card) {
        $text = card_public_url($card);
        $opts = array(
            'theme' => $card['qr_theme'] ?: 'classic',
            'dots' => $card['qr_dots'] ?: 'square',
        );
        if ($card['qr_logo'] && $card['logo'] !== '') $opts['logo'] = $card['logo'];
    } else {
        $text = get('data', '');
        if ($text === '' || strlen($text) > 500) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            exit('QR not found');
        }
        $opts = array();
    }

    $opts['theme'] = get('theme', $themeFromPath !== null ? $themeFromPath : (isset($opts['theme']) ? $opts['theme'] : 'classic'));
    if (get('dots') !== '') $opts['dots'] = get('dots');
    if (get('logo') === '0') unset($opts['logo']);
    if (get('logo') === '1' && $card && $card['logo'] !== '') $opts['logo'] = $card['logo'];
    if (get('ecc') !== '') $opts['ecc'] = get('ecc');
    if (get('px') !== '' && (int)get('px') >= 2 && (int)get('px') <= 40) $opts['px'] = (int)get('px');

    $fmt = 'png';
    if (get('fmt') === 'svg' || (isset($segments[1]) && substr($segments[1], -4) === '.svg') || (isset($segments[2]) && substr($segments[2], -4) === '.svg')) $fmt = 'svg';
    $download = get('download') === '1';

    header('Cache-Control: public, max-age=86400');
    if ($fmt === 'svg') {
        header('Content-Type: image/svg+xml; charset=utf-8');
        if ($download) header('Content-Disposition: attachment; filename="qr-' . ($code !== '' ? $code : 'card') . '.svg"');
        echo VQR::render_svg($text, $opts);
        exit;
    }

    if (!function_exists('gzcompress')) {
        http_response_code(500);
        exit('PNG compression is not available on this server.');
    }
    $png = VQR::render_png_bytes($text, $opts);
    header('Content-Type: image/png');
    if ($download) header('Content-Disposition: attachment; filename="qr-' . ($code !== '' ? $code : 'card') . '.png"');
    echo $png;
    exit;
}
