<?php

function config($k, $default = null) {
    $c = $GLOBALS['__cfg'] ?? array();
    return array_key_exists($k, $c) ? $c[$k] : $default;
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function base_url($path = '') {
    static $b = null;
    if ($b === null) $b = rtrim((string)config('base_url', ''), '/');
    if ($path === '') return $b;
    if (pretty_urls_enabled()) return $b . '/' . ltrim($path, '/');
    $q = '';
    if (strpos($path, '?') !== false) {
        list($path, $q) = explode('?', $path, 2);
    }
    $u = $b . '/index.php?u=' . ltrim($path, '/');
    if ($q !== '') $u .= '&' . $q;
    return $u;
}

function pretty_urls_enabled() {
    $s = config('pretty_urls', '');
    if ($s !== '' && $s !== null) return $s !== '0';
    return empty($_GET['u']);
}

function url($path = '') { return base_url($path); }

function asset($path) { return base_url_static('assets/' . ltrim($path, '/')); }

function upload_url($path) { return base_url_static(ltrim((string)$path, '/')); }

function base_url_static($path = '') {
    static $b = null;
    if ($b === null) $b = rtrim((string)config('base_url', ''), '/');
    return $b . '/' . ltrim((string)$path, '/');
}

function redirect($path) {
    header('Location: ' . base_url($path));
    exit;
}

function redirect_raw($url) {
    header('Location: ' . $url);
    exit;
}

function path_prefix() {
    static $p = null;
    if ($p !== null) return $p;
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    $p = rtrim(str_replace('\\', '/', dirname($sn)), '/');
    if ($p === '.' || $p === '/') $p = '';
    return $p;
}

function route_path() {
    $u = $_SERVER['REQUEST_URI'] ?? '/';
    $q = strpos($u, '?');
    if ($q !== false) $u = substr($u, 0, $q);
    $u = rawurldecode($u);
    $prefix = path_prefix();
    if ($prefix !== '' && strpos($u, $prefix) === 0) $u = substr($u, strlen($prefix));
    return trim($u, '/');
}

/**
 * Returns the list of trusted reverse-proxy IPs that are allowed to set
 * forwarding headers like X-Forwarded-Proto / X-Forwarded-For. By default
 * none are trusted; configure via the 'trusted_proxies' setting (comma list).
 */
function trusted_proxies() {
    static $list = null;
    if ($list === null) {
        $raw = (string)config('trusted_proxies', '');
        $list = array();
        foreach (explode(',', $raw) as $ip) {
            $ip = trim($ip);
            if ($ip !== '') $list[] = $ip;
        }
    }
    return $list;
}

function request_is_behind_trusted_proxy() {
    $proxies = trusted_proxies();
    if (!$proxies) return false;
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($remote, $proxies, true);
}

function is_https() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    // Only trust forwarded headers when the request comes from a configured proxy.
    if (request_is_behind_trusted_proxy()) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && stripos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0) return true;
    }
    return false;
}

function random_code($len = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $c = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $len; $i++) $c .= $chars[random_int(0, $max)];
    return $c;
}

function fa_num($s) {
    $map = array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹');
    return strtr((string)$s, $map);
}

function digits_to_latin($s) {
    return strtr((string)$s, array(
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ));
}

function fa_num_format($n) {
    return fa_num(number_format((float)$n, 0, '.', ','));
}

function post($k, $d = '') { return isset($_POST[$k]) ? $_POST[$k] : $d; }
function get($k, $d = '') { return isset($_GET[$k]) ? $_GET[$k] : $d; }

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function http_error_page($code, $message) {
    http_response_code((int)$code);
    $icon = (int)$code === 429 ? '⏳' : ((int)$code === 403 ? '🔒' : '⚠️');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>خطای ' . (int)$code . '</title></head>'
        . '<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f1f5f9;font-family:Vazirmatn,Tahoma,sans-serif;text-align:center;padding:1rem">'
        . '<div style="background:#fff;padding:2.5rem 3rem;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.08);max-width:440px">'
        . '<div style="font-size:2.4rem">' . $icon . '</div>'
        . '<h1 style="margin:.6rem 0 .4rem;font-size:1.3rem;color:#0f172a">خطای ' . (int)$code . '</h1>'
        . '<p style="margin:0;color:#64748b;line-height:1.9">' . e($message) . '</p>'
        . '<p style="margin:1.2rem 0 0"><a href="javascript:history.back()" style="color:#2563eb;text-decoration:none">بازگشت به صفحه قبل</a></p>'
        . '</div></body></html>';
    exit;
}

function csrf_check() {
    $t = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!hash_equals(csrf_token(), $t)) {
        // UX: instead of a hard error page, show a flash message and send the
        // user back to the form so their entered data can be re-rendered.
        flash('برای امنیت، لطفاً دوباره روی دکمه بزنید.', 'error');
        $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if ($back !== '' && strpos($back, '://') !== false) {
            header('Location: ' . $back);
        } else {
            http_error_page(419, 'جلسه شما منقضی شده است. لطفاً صفحه را دوباره بارگذاری کنید.');
        }
        exit;
    }
}

function flash($msg, $type = 'success') {
    $_SESSION['vc_flash'] = array('m' => (string)$msg, 't' => $type);
}

function flash_render() {
    if (empty($_SESSION['vc_flash'])) return '';
    $f = $_SESSION['vc_flash'];
    unset($_SESSION['vc_flash']);
    return '<div class="alert alert-' . e($f['t']) . '" role="alert">' . e($f['m']) . '</div>';
}

function upload_file($field, $options = array()) {
    if (empty($_FILES[$field])) return array('ok' => false, 'error' => '');
    $f = $_FILES[$field];
    $upErr = (int)$f['error'];
    if ($upErr === UPLOAD_ERR_NO_FILE) return array('ok' => false, 'error' => '');
    if ($upErr !== UPLOAD_ERR_OK) return array('ok' => false, 'error' => 'بارگذاری فایل ناموفق بود.');
    $maxBytes = isset($options['max']) ? (int)$options['max'] : 5242880;
    if ((int)$f['size'] > $maxBytes) return array('ok' => false, 'error' => 'حجم فایل بیشتر از حد مجاز است.');
    $allowed = isset($options['allowed']) ? $options['allowed'] : array('jpg', 'jpeg', 'png', 'webp');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return array('ok' => false, 'error' => 'فرمت فایل مجاز نیست.');
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = @finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        $okMimes = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
        if (isset($okMimes[$ext]) && $mime !== $okMimes[$ext]) return array('ok' => false, 'error' => 'محتوای فایل نامعتبر است.');
    } elseif (function_exists('getimagesize')) {
        $info = @getimagesize($f['tmp_name']);
        $okTypes = array('jpg' => 2, 'jpeg' => 2, 'png' => 3, 'webp' => 18);
        if (!$info || !isset($okTypes[$ext]) || $info[2] !== $okTypes[$ext]) return array('ok' => false, 'error' => 'محتوای فایل نامعتبر است.');
    }
    $dir = VC_UPLOAD_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return array('ok' => false, 'error' => 'ذخیره فایل ناموفق بود.');
    @chmod($dir . '/' . $name, 0644);
    return array('ok' => true, 'path' => 'uploads/' . $name);
}

function delete_upload($relPath) {
    if (!$relPath || !is_string($relPath)) return;
    $p = VC_ROOT . '/' . ltrim($relPath, '/');
    if (strpos($relPath, 'uploads/') === 0 && is_file($p)) @unlink($p);
}

function client_ip() {
    // Default to the TCP-level remote address, which clients cannot forge.
    $remote = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    if (!request_is_behind_trusted_proxy()) return $remote;
    // Behind a trusted proxy: CF-Connecting-IP takes precedence, otherwise the
    // leftmost X-Forwarded-For entry. We still validate it is an IP-like string.
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 45);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($parts as $p) {
            $p = trim($p);
            if (filter_var($p, FILTER_VALIDATE_IP)) return substr($p, 0, 45);
        }
    }
    return $remote;
}

/**
 * File-based rate limiter that survives session resets. Each bucket is keyed
 * by a name (e.g. 'login') plus an identifier (e.g. ip or email hash) and
 * counts attempts within a sliding window. Returns true when the limit is hit.
 *
 * @param string $bucket  Logical name of the limiter (a-zA-Z0-9_).
 * @param string $id       Identifier (ip, email, ...) — hashed internally.
 * @param int    $max     Max number of hits within the window.
 * @param int    $window  Window in seconds.
 * @return bool           True if the caller should be blocked.
 */
/**
 * Read whether a rate-limit bucket is currently blocked WITHOUT incrementing it.
 * Use this for the initial "is the user blocked?" check, and call
 * rate_limit_hit() only when recording an actual failed attempt.
 */
function rate_limit_blocked($bucket, $id, $max = 5, $window = 900) {
    $bucket = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$bucket);
    $hash = hash('sha256', $bucket . '|' . (string)$id);
    $file = VC_UPLOAD_DIR . '/.ratelimit/' . substr($hash, 0, 32) . '.json';
    if (!is_file($file)) return false;
    $decoded = json_decode(@file_get_contents($file), true);
    if (!is_array($decoded) || !isset($decoded['count'], $decoded['time'])) return false;
    if ((time() - (int)$decoded['time']) >= $window) return false;
    return (int)$decoded['count'] >= $max;
}

function rate_limit_hit($bucket, $id, $max = 5, $window = 900) {
    $dir = VC_UPLOAD_DIR . '/.ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $bucket = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$bucket);
    $hash = hash('sha256', $bucket . '|' . (string)$id);
    $file = $dir . '/' . substr($hash, 0, 32) . '.json';
    $now = time();
    $data = array();
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['count'], $decoded['time'])) $data = $decoded;
    }
    if (!$data || !isset($data['time']) || ($now - (int)$data['time']) >= $window) {
        $data = array('count' => 1, 'time' => $now);
    } else {
        $data['count'] = (int)$data['count'] + 1;
    }
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return $data['count'] > $max;
}

/** Reset a rate-limit bucket (e.g. after a successful login). */
function rate_limit_reset($bucket, $id) {
    $bucket = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$bucket);
    $hash = hash('sha256', $bucket . '|' . (string)$id);
    $file = VC_UPLOAD_DIR . '/.ratelimit/' . substr($hash, 0, 32) . '.json';
    if (is_file($file)) @unlink($file);
}

function clean_text($s, $max = 0) {
    $s = trim((string)$s);
    if ($max > 0 && mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
    return $s;
}

function page_title($t) {
    $GLOBALS['__page_title'] = $t;
}

function get_page_title() {
    $app = (string)get_setting('app_name', 'کارت ویزیت من');
    if (!empty($GLOBALS['__page_title'])) return $GLOBALS['__page_title'] . ' | ' . $app;
    return $app;
}

function icon_svg($name, $size = 20) {
    static $paths = null;
    if ($name === 'website') $name = 'globe';
    if ($paths === null) $paths = array(
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 9.5h18M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'dashboard' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/>',
        'cards' => '<path d="M8 6.5h10.5a2 2 0 012 2V18a2 2 0 01-2 2H8a2 2 0 01-2-2V8.5a2 2 0 012-2z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M6 4.5h11M4.5 15V6.5A2.5 2.5 0 017 4h11.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'user' => '<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20.5c1.3-3.5 4.2-5.2 7.5-5.2s6.2 1.7 7.5 5.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'users' => '<circle cx="9" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 19.5c1.1-3 3.5-4.5 6-4.5s4.9 1.5 6 4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M15.5 4.6a3.5 3.5 0 010 6.8M16.5 15.3c1.9.7 3.3 2.2 4 4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'settings' => '<circle cx="12" cy="12" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 00.34 1.88l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V21a2 2 0 01-4 0v-.09A1.7 1.7 0 009 19.34a1.7 1.7 0 00-1.88.34l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15 1.7 1.7 0 003.04 14H3a2 2 0 010-4h.09A1.7 1.7 0 004.6 9a1.7 1.7 0 00-.34-1.88l-.06-.06a2 2 0 012.83-2.83l.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001.03-1.56V3a2 2 0 014 0v.09A1.7 1.7 0 0015 4.6a1.7 1.7 0 001.88-.34l.06-.06a2 2 0 012.83 2.83l-.06.06A1.7 1.7 0 0019.4 9a1.7 1.7 0 001.56 1.03H21a2 2 0 010 4h-.09A1.7 1.7 0 0019.4 15z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        'logout' => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'login' => '<path d="M15 21h4a2 2 0 002-2V5a2 2 0 00-2-2h-4M11 17l5-5-5-5M16 12H3" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'link' => '<path d="M10 13.5a5 5 0 007.07.07l2.36-2.36a5 5 0 00-7.07-7.07L11.3 5.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 10.5a5 5 0 00-7.07-.07L4.57 12.8a5 5 0 007.07 7.07l1.06-1.06" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'share' => '<circle cx="18" cy="5.5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="6" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="18" cy="18.5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.2 10.9l7.6-4.3M8.2 13.1l7.6 4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'download' => '<path d="M12 3v12m0 0l-4.5-4.5M12 15l4.5-4.5M4 19h16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'qr' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3.5" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="3.5" y="13.5" width="7" height="7" rx="1" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M13.5 13.5h2v2h-2zM16.5 13.5h2v2h-2zM13.5 16.5h2v2h-2zM16.5 16.5h2v2h-2zM13.5 19.5h4M19.5 13.5v4" fill="currentColor"/>',
        'phone' => '<path d="M5 4h4l1.5 4-2 1.5a11 11 0 006 6l1.5-2 4 1.5v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'whatsapp' => '<path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.7-1.2A9 9 0 1012 3z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9.2 8.2c.3 0 .6.4.7.8.1.3.7 1.8.8 1.9.1.2.1.3 0 .5-.5 1-1.1 1-1 1.4.3.9 1.6 2.6 3 3.4.6.4 1 .4 1.3.3.3-.2.6-.4.7-1 .1-.5.1-1 0-1.1-.1-.1-.3-.2-.6-.4l-1-.5c-.2-.1-.4-.1-.5.2l-.4.6c-.1.2-.3.2-.5.1-.7-.4-1.5-1-2-1.8-.2-.3 0-.4.1-.6l.5-.6c.1-.2.1-.3 0-.5-.3-.7-.6-1.4-.9-2-.1-.3-.3-.4-.5-.4h-.5c-.3 0-.6.1-.8.4-.2.2-.7.7-.7 1.7 0 1 .7 1.9.8 2" fill="currentColor"/>',
        'telegram' => '<path d="M21 4.5L3 11.2c-.9.4-.9 1.4-.1 1.7l4.3 1.4 1.6 5c.2.7 1 1 1.6.6l2.3-1.7 4 3c.7.5 1.6.2 1.8-.6l2.7-14.6c.2-1.2-.8-2-1.9-1.5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8.8 13.3l9.9-6.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/>',
        'email' => '<rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'globe' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 12h17M12 3.5c2.5 2.3 3.8 5.3 3.8 8.5s-1.3 6.2-3.8 8.5c-2.5-2.3-3.8-5.3-3.8-8.5S9.5 5.8 12 3.5z" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        'map-pin' => '<path d="M12 21s7-5.3 7-11a7 7 0 10-14 0c0 5.7 7 11 7 11z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8 11v5.5M8 8.2v.1M12 16.5V11m4 5.5V13a2.2 2.2 0 00-4.4 0v3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'twitter' => '<path d="M4 4.5c4.2 3 6.6 5.8 7 9.2.4-3.4 2.8-6.2 7-9.2-.8 4.6-2.3 7.7-5.5 10 2 1 4.3 1.6 7.5 2.2C15.5 18.4 12 18.8 9.5 18c-3 2.6-7 2.5-7 2.5s1.7-3.2 2.6-4.9C3.4 13.2 2.4 9.4 4 4.5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'youtube' => '<rect x="3" y="6" width="18" height="12.5" rx="3.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M10.5 9.5l4.5 2.5-4.5 2.5z" fill="currentColor"/>',
        'tiktok' => '<path d="M14.5 3c.3 2.2 1.6 3.7 4 4v3.2c-1.5 0-2.8-.5-4-1.3V16a5.5 5.5 0 11-5.5-5.5c.4 0 .8 0 1.1.2v3.3a2.3 2.3 0 101.6 2.2V3h2.8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'facebook' => '<path d="M15 3h-2.5A4.5 4.5 0 008 7.5V10H5.5v3.5H8V21h3.5v-7.5H14l.5-3.5h-3V7.8A1.3 1.3 0 0112.8 6.5H15V3z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'github' => '<path d="M9 19c-4 1.2-4-2.2-6-2.6M9 19v-3.4c0-1 .1-1.6-.5-2.2 1.6-.2 3.3.8 3.5 2.8M9 19c1.8 1.2 4 0 5-1.4M15 17.6V21M14.5 3.4c1 .9 1.5 2.1 1.6 3.4.8.6 1.3 1.5 1.6 2.6.5-.2 1-.4 1.3-.9M6.5 5.2c.2 1 .7 1.8 1.5 2.4M8 7.6c1.3-.4 2.6-.6 4-.6s2.7.2 4 .6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        'aparat' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.2l4.5 2.8-4.5 2.8z" fill="currentColor"/>',
        'threads' => '<path d="M12 3c-4.5 0-7.5 2.4-7.5 6 0 5 6.3 6.5 8.6 6.5 1.3 0 3-.3 3-1.7 0-1.3-1.2-1.7-2.5-1.7-2 0-3.6 1.2-4 3.4-.4 2.3 1 3.5 2.7 3.5 2 0 3.3-1.6 4.2-4.1.8-2.2 1.2-5.6.4-8C15.9 4.5 14 3 12 3z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9.5 12.5c.6-2 2-3 4-3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'snapchat' => '<path d="M12 3c2.8 0 5 2.2 5 5.5 0 .8-.1 1.5-.2 2.2 1 .4 1.7 1.2 1.7 2.2 0 1-.7 1.8-1.6 2.1-.1 1.4-1 2.2-2.4 2.6-.6.2-.8.7-.5 1.2 0 0 1.3.5 3.3 1.6l-.3.8c-2-.5-3.4-.3-3.4-.3-.5 1.6-2.1 2.9-4.1 2.9-2.1 0-3.6-1.3-4.1-2.9 0 0-1.4-.2-3.4.3l-.3-.8c2-1.1 3.3-1.6 3.3-1.6.3-.5.1-1-.5-1.2-1.4-.4-2.3-1.2-2.4-2.6-.9-.3-1.6-1.1-1.6-2.1 0-1 .7-1.8 1.7-2.2-.1-.7-.2-1.4-.2-2.2 0-3.3 2.2-5.5 5-5.5h1z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'pinterest' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10.5 15.5l1.2-4.8M11.2 11.5c-.2-1.3.5-2.4 1.8-2.4 1 0 1.8.8 1.8 1.9 0 1.3-.7 2.7-1.8 2.7-.6 0-1-.4-.8-1" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'edit' => '<path d="M4 20h4l11-11a2.1 2.1 0 00-3-3L5 17v3zM13.5 6.5l3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'trash' => '<path d="M4 7h16M9 7V5a1.5 1.5 0 011.5-1.5h3A1.5 1.5 0 0115 5v2m3.5 0l-.8 12a2 2 0 01-2 1.9H8.3a2 2 0 01-2-1.9L5.5 7M10 11v5.5M14 11v5.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.8" fill="none" stroke="currentColor" stroke-width="1.8"/>',
        'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'search' => '<circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="M20 20l-3.8-3.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>',
        'menu' => '<path d="M4 6.5h16M4 12h16M4 17.5h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'arrow-right' => '<path d="M4 12h16m-6-6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5 15V6a2 2 0 012-2h9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'check' => '<path d="M4.5 12.5l5 5 10-11" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3.5 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'camera' => '<path d="M4 8h2.5l1.6-2.2A1.5 1.5 0 019.4 5h5.2a1.5 1.5 0 011.3.8L17.5 8H20a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2v-9a2 2 0 012-2z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="13.5" r="3.4" fill="none" stroke="currentColor" stroke-width="1.7"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="9.5" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10.5V8a4 4 0 018 0v2.5" fill="none" stroke="currentColor" stroke-width="1.8"/>',
        'star' => '<path d="M12 3.5l2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 17l-5.3 2.6 1-5.8-4.2-4.1 5.9-.9z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'info' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 11v5M12 7.8v.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'alert' => '<path d="M12 3.5L21.5 20H2.5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v4.5M12 17.5v.1" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>',
        'refresh' => '<path d="M20 12a8 8 0 11-2.3-5.6M20 4v4h-4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'send' => '<path d="M21 3L10.5 13.5M21 3l-7 18-3.5-7.5L3 10z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'map' => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14m6-12v14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 9.5h17M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'bag' => '<path d="M5 8h14l1 12.5H4zM8.5 8a3.5 3.5 0 017 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'heart' => '<path d="M12 20s-7-4.5-9.5-9A5.2 5.2 0 0112 6.2 5.2 5.2 0 0121.5 11c-2.5 4.5-9.5 9-9.5 9z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'contact' => '<rect x="4" y="5" width="16" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 9.5h8M8 13h5M8 16h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'upload' => '<path d="M12 15V3m0 0L7.5 7.5M12 3l4.5 4.5M4 15v4a2 2 0 002 2h12a2 2 0 002-2v-4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3l7.5 3v5.5c0 4.5-3 8.2-7.5 9.5-4.5-1.3-7.5-5-7.5-9.5V6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12l2 2 4-4.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'check-circle' => '<circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 12l2.5 2.5 4.5-5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>',
        'image' => '<rect x="3.5" y="4" width="17" height="16" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="9" cy="9.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 17l4.5-4 3 2.7 3.5-3.5 4 3.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
    );
    }
    if (!isset($paths[$name])) $name = 'info';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' . $paths[$name] . '</svg>';
}

// Fallbacks in case the mbstring extension is missing (single-byte, degraded)
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen((string)$s); }
    function mb_substr($s, $start, $len = null) {
        if ($len === null) return substr((string)$s, $start);
        return substr((string)$s, $start, $len);
    }
    function mb_internal_encoding($enc = '') { return $enc === '' ? 'UTF-8' : true; }
}
