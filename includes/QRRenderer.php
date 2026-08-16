<?php
/**
 * QR code renderer with multiple themes / dot styles / centered logo.
 * Uses the MIT-licensed kazuhikoarase qrcode-generator (single file).
 */

require_once __DIR__ . '/qrcode/qrcode.php';

class VQR {

    public static $themes = array(
        'classic'   => array('fg' => '#1a1a2e', 'bg' => '#ffffff', 'grad' => null),
        'dark'      => array('fg' => '#e2e8f0', 'bg' => '#0f172a', 'grad' => null),
        'blue'      => array('fg' => '#1d4ed8', 'bg' => '#ffffff', 'grad' => null),
        'green'     => array('fg' => '#15803d', 'bg' => '#ffffff', 'grad' => null),
        'purple'    => array('fg' => '#7c3aed', 'bg' => '#ffffff', 'grad' => null),
        'red'       => array('fg' => '#dc2626', 'bg' => '#ffffff', 'grad' => null),
        'orange'    => array('fg' => '#ea580c', 'bg' => '#ffffff', 'grad' => null),
        'teal'      => array('fg' => '#0f766e', 'bg' => '#ffffff', 'grad' => null),
        'pink'      => array('fg' => '#be185d', 'bg' => '#ffffff', 'grad' => null),
        'navy'      => array('fg' => '#1e3a8a', 'bg' => '#f8fafc', 'grad' => null),
        'gold'      => array('fg' => '#92600a', 'bg' => '#fffdf5', 'grad' => null),
        'mint'      => array('fg' => '#065f46', 'bg' => '#ecfdf5', 'grad' => null),
        'slate'     => array('fg' => '#334155', 'bg' => '#f8fafc', 'grad' => null),
        'gradient1' => array('grad' => array('#3b82f6', '#8b5cf6'), 'bg' => '#ffffff'),
        'gradient2' => array('grad' => array('#10b981', '#06b6d4'), 'bg' => '#ffffff'),
        'gradient3' => array('grad' => array('#f472b6', '#fb923c'), 'bg' => '#ffffff'),
        'gradient4' => array('grad' => array('#f43f5e', '#a855f7'), 'bg' => '#ffffff'),
        'gradient5' => array('grad' => array('#2563eb', '#0f172a'), 'bg' => '#ffffff'),
        'gradient6' => array('grad' => array('#ef4444', '#f59e0b'), 'bg' => '#ffffff'),
    );

    public static $dots = array('square', 'round', 'circle');

    private static function ecc_level($ecc) {
        $levels = array(
            'L' => QR_ERROR_CORRECT_LEVEL_L,
            'M' => QR_ERROR_CORRECT_LEVEL_M,
            'Q' => QR_ERROR_CORRECT_LEVEL_Q,
            'H' => QR_ERROR_CORRECT_LEVEL_H,
        );
        return isset($levels[$ecc]) ? $levels[$ecc] : QR_ERROR_CORRECT_LEVEL_H;
    }

    public static function get_matrix($text, $ecc = 'H') {
        $qr = QRCode::getMinimumQRCode((string)$text, self::ecc_level($ecc));
        $count = $qr->getModuleCount();
        $dark = array();
        for ($r = 0; $r < $count; $r++) {
            $row = array();
            for ($c = 0; $c < $count; $c++) $row[] = (bool)$qr->isDark($r, $c);
            $dark[] = $row;
        }
        return array('size' => $count, 'dark' => $dark);
    }

    public static function hex_rgb($hex) {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        $n = hexdec($hex);
        return array('r' => ($n >> 16) & 255, 'g' => ($n >> 8) & 255, 'b' => $n & 255);
    }

    public static function theme_colors($theme) {
        return isset(self::$themes[$theme]) ? self::$themes[$theme] : self::$themes['classic'];
    }

    public static function validate_opts($opts) {
        $theme = isset($opts['theme']) ? (string)$opts['theme'] : 'classic';
        if (!isset(self::$themes[$theme])) $theme = 'classic';
        $dots = isset($opts['dots']) ? (string)$opts['dots'] : 'square';
        if (!in_array($dots, self::$dots, true)) $dots = 'square';
        return array(
            'theme' => $theme,
            'dots' => $dots,
            'px' => isset($opts['px']) ? max(1, (int)$opts['px']) : 10,
            'margin' => isset($opts['margin']) ? max(0, (int)$opts['margin']) : 3,
            'ecc' => isset($opts['ecc']) ? (string)$opts['ecc'] : 'H',
            'logo' => isset($opts['logo']) ? (string)$opts['logo'] : '',
        );
    }

    public static function render_png($text, $opts = array()) {
        $o = self::validate_opts($opts);
        $t = self::theme_colors($o['theme']);
        $data = self::get_matrix($text, $o['ecc']);
        $n = $data['size'];
        $dark = $data['dark'];
        $px = $o['px'];
        $margin = $o['margin'];
        $side = ($n + $margin * 2) * $px;

        $img = imagecreatetruecolor($side, $side);
        $bg = self::hex_rgb(isset($t['bg']) ? $t['bg'] : '#ffffff');
        imagealphablending($img, false);
        $bgColor = imagecolorallocate($img, $bg['r'], $bg['g'], $bg['b']);
        imagefilledrectangle($img, 0, 0, $side, $side, $bgColor);
        imagealphablending($img, true);

        $fg1 = self::hex_rgb(isset($t['fg']) ? $t['fg'] : '#1a1a2e');
        $fg2 = null;
        if (!empty($t['grad'])) $fg2 = self::hex_rgb($t['grad'][1]);

        // Precompute gradient colors once per (r+c) sum
        $lut = null;
        if ($fg2 !== null) {
            $lut = array();
            for ($s = 0; $s <= 2 * ($n - 1); $s++) {
                $tt = $s / (2 * max(1, $n - 1));
                $cr = (int)($fg1['r'] + ($fg2['r'] - $fg1['r']) * $tt);
                $cg = (int)($fg1['g'] + ($fg2['g'] - $fg1['g']) * $tt);
                $cb = (int)($fg1['b'] + ($fg2['b'] - $fg1['b']) * $tt);
                $lut[$s] = imagecolorallocate($img, $cr, $cg, $cb);
            }
        } else {
            $c1 = imagecolorallocate($img, $fg1['r'], $fg1['g'], $fg1['b']);
        }

        $half = (int)($px / 2);
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                if (!$dark[$r][$c]) continue;
                $x = $margin * $px + $c * $px;
                $y = $margin * $px + $r * $px;
                $col = $lut !== null ? $lut[$r + $c] : $c1;
                if ($o['dots'] === 'circle') {
                    imagefilledellipse($img, $x + $half, $y + $half, (int)($px * 0.94), (int)($px * 0.94), $col);
                } elseif ($o['dots'] === 'round') {
                    self::rounded_rect($img, $x, $y, $x + $px - 1, $y + $px - 1, (int)($px * 0.34), $col);
                } else {
                    imagefilledrectangle($img, $x, $y, $x + $px - 1, $y + $px - 1, $col);
                }
            }
        }

        $logo = self::logo_path($o['logo']);
        if ($logo !== null) {
            $src = @imagecreatefromstring(@file_get_contents($logo));
            if ($src !== false) {
                $logoArea = (int)($n * $px * 0.26);
                $pad = (int)($px * 1.8);
                $inner = $logoArea - $pad * 2;
                if ($inner > 8) {
                    $cx = (int)(($side - $logoArea) / 2);
                    $cy = (int)(($side - $logoArea) / 2);
                    $white = imagecolorallocate($img, 255, 255, 255);
                    self::rounded_rect($img, $cx - 2, $cy - 2, $cx + $logoArea + 1, $cy + $logoArea + 1, (int)($px * 1.2), $white);
                    $lw = imagesx($src);
                    $lh = imagesy($src);
                    $scale = $lw > 0 ? $inner / $lw : 1;
                    $nh = (int)($lh * $scale);
                    $dstX = $cx + $pad;
                    $dstY = $cy + (int)(($inner - $nh) / 2);
                    imagecopyresampled($img, $src, $dstX, $dstY, 0, 0, $inner, $nh, $lw, $lh);
                }
                imagedestroy($src);
            }
        }

        return $img;
    }

    public static function rounded_rect($img, $x1, $y1, $x2, $y2, $radius, $color) {
        $w = $x2 - $x1;
        $h = $y2 - $y1;
        $radius = max(0, min($radius, (int)($w / 2), (int)($h / 2)));
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    public static function render_png_bytes($text, $opts = array()) {
        if (function_exists('imagepng')) {
            $img = self::render_png($text, $opts);
            ob_start();
            imagepng($img);
            $bytes = ob_get_clean();
            imagedestroy($img);
            return $bytes;
        }
        return self::render_png_pure($text, $opts);
    }

    private static function png_chunk($type, $data) {
        $crc = crc32($type . $data);
        if ($crc < 0) $crc += 4294967296;
        return pack('N', strlen($data)) . $type . $data . pack('N', $crc);
    }

    public static function render_png_pure($text, $opts = array()) {
        $o = self::validate_opts($opts);
        $t = self::theme_colors($o['theme']);
        $data = self::get_matrix($text, $o['ecc']);
        $n = $data['size'];
        $dark = $data['dark'];
        $px = $o['px'];
        $margin = $o['margin'];
        $side = ($n + $margin * 2) * $px;

        $bg = self::hex_rgb(isset($t['bg']) ? $t['bg'] : '#ffffff');
        $fg1 = self::hex_rgb(isset($t['fg']) ? $t['fg'] : '#1a1a2e');
        $fg2 = !empty($t['grad']) ? self::hex_rgb($t['grad'][1]) : null;

        $bgRow = str_repeat(chr($bg['r']) . chr($bg['g']) . chr($bg['b']), $side);
        $rows = array_fill(0, $side, "\x00" . $bgRow);

        $half = $px / 2;
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                if (!$dark[$r][$c]) continue;
                $x0 = $margin * $px + $c * $px;
                $y0 = $margin * $px + $r * $px;
                if ($fg2 !== null) {
                    $tt = ($r + $c) / (2 * max(1, $n - 1));
                    $col = array(
                        'r' => (int)($fg1['r'] + ($fg2['r'] - $fg1['r']) * $tt),
                        'g' => (int)($fg1['g'] + ($fg2['g'] - $fg1['g']) * $tt),
                        'b' => (int)($fg1['b'] + ($fg2['b'] - $fg1['b']) * $tt),
                    );
                } else {
                    $col = $fg1;
                }
                $pix = chr($col['r']) . chr($col['g']) . chr($col['b']);
                if ($o['dots'] === 'circle') {
                    $rad = $px * 0.47;
                    $rad2 = $rad * $rad;
                    $ccx = $x0 + $half;
                    $ccy = $y0 + $half;
                    for ($y = $y0; $y < $y0 + $px; $y++) {
                        $dy = $y + 0.5 - $ccy;
                        for ($x = $x0; $x < $x0 + $px; $x++) {
                            $dx = $x + 0.5 - $ccx;
                            if ($dx * $dx + $dy * $dy <= $rad2) {
                                $off = 1 + $x * 3;
                                $rows[$y][$off] = $pix[0];
                                $rows[$y][$off + 1] = $pix[1];
                                $rows[$y][$off + 2] = $pix[2];
                            }
                        }
                    }
                } elseif ($o['dots'] === 'round') {
                    $rad = (int)($px * 0.34);
                    $rad2 = $rad * $rad;
                    $e1 = $x0 + $rad - 0.5;
                    $e2 = $x0 + $px - 1 - $rad + 0.5;
                    $f1 = $y0 + $rad - 0.5;
                    $f2 = $y0 + $px - 1 - $rad + 0.5;
                    for ($y = $y0; $y < $y0 + $px; $y++) {
                        $cy = $y + 0.5;
                        for ($x = $x0; $x < $x0 + $px; $x++) {
                            $cx = $x + 0.5;
                            $inside = $cx >= $e1 && $cx <= $e2 && $cy >= $f1 && $cy <= $f2;
                            if (!$inside) {
                                $ncx = $cx < $e1 ? $e1 : ($cx > $e2 ? $e2 : $cx);
                                $ncy = $cy < $f1 ? $f1 : ($cy > $f2 ? $f2 : $cy);
                                $dx = $cx - $ncx;
                                $dy = $cy - $ncy;
                                if ($dx * $dx + $dy * $dy > $rad2) continue;
                            }
                            $off = 1 + $x * 3;
                            $rows[$y][$off] = $pix[0];
                            $rows[$y][$off + 1] = $pix[1];
                            $rows[$y][$off + 2] = $pix[2];
                        }
                    }
                } else {
                    $block = str_repeat($pix, $px);
                    for ($y = $y0; $y < $y0 + $px; $y++) {
                        $rows[$y] = substr($rows[$y], 0, 1 + $x0 * 3) . $block . substr($rows[$y], 1 + ($x0 + $px) * 3);
                    }
                }
            }
        }

        $raw = implode('', $rows);
        $png = "\x89PNG\r\n\x1a\n";
        $png .= self::png_chunk('IHDR', pack('NNCCCCC', $side, $side, 8, 2, 0, 0, 0));
        $png .= self::png_chunk('IDAT', gzcompress($raw, 9));
        $png .= self::png_chunk('IEND', '');
        return $png;
    }

    public static function render_svg($text, $opts = array()) {
        $o = self::validate_opts($opts);
        $t = self::theme_colors($o['theme']);
        $data = self::get_matrix($text, $o['ecc']);
        $n = $data['size'];
        $dark = $data['dark'];
        $unit = 10;
        $margin = $o['margin'];
        $side = ($n + $margin * 2) * $unit;
        $bg = isset($t['bg']) ? $t['bg'] : '#ffffff';
        $fg1 = isset($t['fg']) ? $t['fg'] : '#1a1a2e';
        $grad = !empty($t['grad']) ? $t['grad'] : null;

        $s = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $s .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $side . '" height="' . $side . '" viewBox="0 0 ' . $side . ' ' . $side . '">' . "\n";
        $s .= '  <rect width="100%" height="100%" rx="' . ($margin * $unit * 0.3) . '" fill="' . e($bg) . '"/>' . "\n";
        if ($grad !== null) {
            $s .= '  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">' . "\n";
            $s .= '    <stop offset="0%" stop-color="' . e($grad[0]) . '"/>' . "\n";
            $s .= '    <stop offset="100%" stop-color="' . e($grad[1]) . '"/>' . "\n";
            $s .= '  </linearGradient></defs>' . "\n";
            $fill = 'url(#g)';
        } else {
            $fill = $fg1;
        }
        if ($o['dots'] === 'circle') {
            $r = $unit * 0.47;
            for ($r2 = 0; $r2 < $n; $r2++) {
                for ($c2 = 0; $c2 < $n; $c2++) {
                    if (!$dark[$r2][$c2]) continue;
                    $s .= '    <circle cx="' . ($margin * $unit + $c2 * $unit + $unit / 2) . '" cy="' . ($margin * $unit + $r2 * $unit + $unit / 2) . '" r="' . $r . '" fill="' . $fill . '"/>' . "\n";
                }
            }
        } elseif ($o['dots'] === 'round') {
            $rx = $unit * 0.34;
            for ($r2 = 0; $r2 < $n; $r2++) {
                for ($c2 = 0; $c2 < $n; $c2++) {
                    if (!$dark[$r2][$c2]) continue;
                    $s .= '    <rect x="' . ($margin * $unit + $c2 * $unit) . '" y="' . ($margin * $unit + $r2 * $unit) . '" width="' . $unit . '" height="' . $unit . '" rx="' . $rx . '" fill="' . $fill . '"/>' . "\n";
                }
            }
        } else {
            for ($r2 = 0; $r2 < $n; $r2++) {
                for ($c2 = 0; $c2 < $n; $c2++) {
                    if (!$dark[$r2][$c2]) continue;
                    $s .= '    <rect x="' . ($margin * $unit + $c2 * $unit) . '" y="' . ($margin * $unit + $r2 * $unit) . '" width="' . $unit . '" height="' . $unit . '" fill="' . $fill . '"/>' . "\n";
                }
            }
        }
        $s .= '</svg>';
        return $s;
    }

    private static function logo_path($rel) {
        if ($rel === '' || !is_string($rel)) return null;
        $p = VC_ROOT . '/' . ltrim($rel, '/');
        return is_file($p) ? $p : null;
    }

    public static function themes_list() {
        return array_keys(self::$themes);
    }
}
