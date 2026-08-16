<?php
/**
 * Persian (Jalali) calendar conversions — pure PHP port of the MIT-licensed
 * jalaali-js library (Borkowski algorithm):
 * https://github.com/jalaali/jalaali-js
 *
 * Provides fa_date()/fa_date_long()/fa_datetime() used throughout the app.
 */

if (!defined('J_BREAKS')) {
    define('J_BREAKS', array(-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178));
    define('J_MAX_YEAR', 3177);
}

if (!function_exists('j_div')) {
    function j_div($a, $b) { return intdiv((int)$a, (int)$b); }
    function j_mod($a, $b) { return (int)$a - intdiv((int)$a, (int)$b) * (int)$b; }

    function j_cal_core($jy) {
        $breaks = J_BREAKS;
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jm = 0;
        $jump = 0;
        $count = count($breaks);
        for ($i = 1; $i < $count; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) break;
            $leapJ = $leapJ + j_div($jump, 33) * 8 + j_div(j_mod($jump, 33), 4);
            $jp = $jm;
        }
        $n = $jy - $jp;
        $leapJ = $leapJ + j_div($n, 33) * 8 + j_div(j_mod($n, 33) + 3, 4);
        if (j_mod($jump, 33) === 4 && $jump - $n === 4) $leapJ += 1;
        $leapG = j_div($gy, 4) - j_div((j_div($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;
        $leap = j_leap_from_cycle($jump, $n);
        return array('gy' => $gy, 'march' => $march, 'leap' => $leap);
    }

    function j_leap_from_cycle($jump, $n) {
        $adjusted = $n;
        if ($jump - $n < 6) $adjusted = $n - $jump + j_div($jump + 4, 33) * 33;
        $leap = j_mod(j_mod($adjusted + 1, 33) - 1, 4);
        if ($leap === -1) $leap = 4;
        return $leap;
    }

    function j_g2d($gy, $gm, $gd) {
        $d = j_div(($gy + j_div($gm - 8, 6) + 100100) * 1461, 4)
           + j_div(153 * j_mod($gm + 9, 12) + 2, 5)
           + $gd - 34840408;
        $d = $d - j_div(j_div($gy + 100100 + j_div($gm - 8, 6), 100) * 3, 4) + 752;
        return $d;
    }

    function j_d2g($jdn) {
        $j = 4 * $jdn + 139361631;
        $j = $j + j_div(j_div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = j_div(j_mod($j, 1461), 4) * 5 + 308;
        $gd = j_div(j_mod($i, 153), 5) + 1;
        $gm = j_mod(j_div($i, 153), 12) + 1;
        $gy = j_div($j, 1461) - 100100 + j_div(8 - $gm, 6);
        return array($gy, $gm, $gd);
    }

    function j_j2d($jy, $jm, $jd) {
        $r = j_cal_core($jy);
        return j_g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - j_div($jm, 7) * ($jm - 7) + $jd - 1;
    }

    function j_d2j($jdn) {
        list($gy, $gm, $gd) = j_d2g($jdn);
        $jy = min($gy - 621, J_MAX_YEAR);
        $r = j_cal_core($jy);
        $jdn1f = j_g2d($r['gy'], 3, $r['march']);
        $k = $jdn - $jdn1f;
        if ($k >= 0) {
            if ($k <= 185) return array($jy, 1 + j_div($k, 31), j_mod($k, 31) + 1);
            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) $k += 1;
        }
        return array($jy, 7 + j_div($k, 30), j_mod($k, 30) + 1);
    }

    function gregorian_to_jalali($gy, $gm, $gd) {
        return j_d2j(j_g2d($gy, $gm, $gd));
    }
}

if (!isset($GLOBALS['__J_MONTHS'])) {
    $GLOBALS['__J_MONTHS'] = array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
}

function j_fa_month($jm) {
    $m = $GLOBALS['__J_MONTHS'];
    return $m[max(0, min(11, (int)$jm - 1))];
}

function j_parse_ts($ts) {
    if ($ts === null) $ts = time();
    return gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
}

function fa_date($ts = null) {
    list($jy, $jm, $jd) = j_parse_ts($ts);
    return fa_num($jy . '/' . str_pad((string)$jm, 2, '0', STR_PAD_LEFT) . '/' . str_pad((string)$jd, 2, '0', STR_PAD_LEFT));
}

function fa_date_long($ts = null) {
    list($jy, $jm, $jd) = j_parse_ts($ts);
    return fa_num($jd) . ' ' . j_fa_month($jm) . ' ' . fa_num($jy);
}

function fa_datetime($ts = null) {
    if ($ts === null) $ts = time();
    return fa_date_long($ts) . ' - ' . fa_num(date('H:i', $ts));
}
