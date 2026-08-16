<?php

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function get_setting($k, $d = '') {
    if (!isset($GLOBALS['__set_cache']) || !is_array($GLOBALS['__set_cache'])) {
        $GLOBALS['__set_cache'] = array();
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) {
            $GLOBALS['__set_cache'][$r['skey']] = $r['svalue'];
        }
    }
    return array_key_exists($k, $GLOBALS['__set_cache']) ? $GLOBALS['__set_cache'][$k] : $d;
}

function set_setting($k, $v) {
    $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $st->execute(array($k, (string)$v));
    if (!isset($GLOBALS['__set_cache']) || !is_array($GLOBALS['__set_cache'])) $GLOBALS['__set_cache'] = array();
    $GLOBALS['__set_cache'][$k] = (string)$v;
}

// ---------------------------------------------------------------------------
// Users
// ---------------------------------------------------------------------------
function get_user($id) {
    $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute(array((int)$id));
    return $st->fetch() ?: null;
}

function get_user_by_email($email) {
    $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute(array(strtolower(trim((string)$email))));
    return $st->fetch() ?: null;
}

function create_user($name, $email, $password, $role = 'user') {
    $st = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    $st->execute(array($name, strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT), $role));
    return (int)db()->lastInsertId();
}

function update_user($id, $fields) {
    if (empty($fields)) return;
    $sets = array();
    $vals = array();
    foreach ($fields as $k => $v) {
        if (in_array($k, array('name', 'email', 'role', 'status', 'password'), true)) {
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
    }
    if (empty($sets)) return;
    $vals[] = (int)$id;
    $st = db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $st->execute($vals);
}

function delete_user($id) {
    $cards = get_user_cards((int)$id);
    foreach ($cards as $c) delete_card($c['id']);
    $st = db()->prepare('DELETE FROM users WHERE id = ?');
    $st->execute(array((int)$id));
}

function list_users($search = '', $page = 1, $per = 20) {
    $where = '';
    $vals = array();
    if ($search !== '') {
        $where = 'WHERE name LIKE ? OR email LIKE ?';
        $vals[] = '%' . $search . '%';
        $vals[] = '%' . $search . '%';
    }
    $off = max(0, ((int)$page - 1) * (int)$per);
    $st = db()->prepare('SELECT * FROM users ' . $where . ' ORDER BY id DESC LIMIT ' . (int)$per . ' OFFSET ' . $off);
    $st->execute($vals);
    $rows = $st->fetchAll();
    $st2 = db()->prepare('SELECT COUNT(*) AS c FROM users ' . $where);
    $st2->execute($vals);
    return array('rows' => $rows, 'total' => (int)$st2->fetch()['c']);
}

function count_users() {
    return (int)db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
}

// ---------------------------------------------------------------------------
// Cards
// ---------------------------------------------------------------------------
function card_defaults() {
    return array(
        'code' => '', 'full_name' => '', 'job_title' => '', 'company' => '',
        'phone' => '', 'phone2' => '', 'email' => '', 'website' => '', 'address' => '',
        'bio' => '', 'logo' => '', 'cover' => '',
        'template' => 'classic', 'color1' => '#4f46e5', 'color2' => '#7c3aed',
        'qr_theme' => 'classic', 'qr_dots' => 'square', 'qr_logo' => '0', 'logo_pos' => 'center',
        'socials' => '{}', 'custom_fields' => '[]',
        'map_address' => '', 'map_lat' => null, 'map_lng' => null,
        'active' => 1,
    );
}

function card_code_length() {
    $len = (int)get_setting('code_length', 6);
    if ($len < 4 || $len > 12) $len = 6;
    return $len;
}

function card_code_min() { return 4; }
function card_code_max() { return 16; }

function is_reserved_code($code) {
    static $reserved = null;
    if ($reserved === null) {
        $reserved = array('login', 'register', 'logout', 'panel', 'admin', 'qr', 'vcf', 'c',
            'install', 'index', 'assets', 'uploads', 'includes', 'app', 'api');
    }
    return in_array(strtolower((string)$code), $reserved, true);
}

function is_valid_card_code($code) {
    $code = (string)$code;
    if ($code === '') return false;
    if (is_reserved_code($code)) return false;
    return preg_match('/^[A-Za-z0-9]{' . card_code_min() . ',' . card_code_max() . '}$/', $code) === 1;
}

function unique_card_code() {
    $len = card_code_length();
    for ($i = 0; $i < 20; $i++) {
        $code = random_code($len);
        if (!is_reserved_code($code) && !get_card_by_code($code)) return $code;
    }
    return random_code($len + 2);
}

// اگر نصب قبلی است و ستون logo_pos هنوز ساخته نشده، آن را اضافه می‌کند.
function db_migrate() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = db()->query('SHOW COLUMNS FROM cards')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('logo_pos', $cols, true)) {
            db()->exec("ALTER TABLE cards ADD COLUMN logo_pos VARCHAR(12) NOT NULL DEFAULT 'center' AFTER qr_logo");
        }
    } catch (Exception $e) {
        // جدول وجود ندارد یا دسترسی نیست؛ بی‌صدا رد شو.
    }
}

function create_card($user_id, $data = array()) {
    db_migrate();
    $d = array_merge(card_defaults(), $data);
    $code = $d['code'] !== '' ? $d['code'] : unique_card_code();
    $st = db()->prepare(
        'INSERT INTO cards (user_id, code, full_name, job_title, company, phone, phone2, email, website, address, bio,
         logo, cover, template, color1, color2, qr_theme, qr_dots, qr_logo, logo_pos, socials, custom_fields,
         map_address, map_lat, map_lng, active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute(array(
        (int)$user_id, $code,
        $d['full_name'], $d['job_title'], $d['company'], $d['phone'], $d['phone2'], $d['email'], $d['website'],
        $d['address'], $d['bio'], $d['logo'], $d['cover'],
        $d['template'], $d['color1'], $d['color2'], $d['qr_theme'], $d['qr_dots'], $d['qr_logo'], $d['logo_pos'],
        is_string($d['socials']) ? $d['socials'] : json_encode($d['socials'], JSON_UNESCAPED_UNICODE),
        is_string($d['custom_fields']) ? $d['custom_fields'] : json_encode($d['custom_fields'], JSON_UNESCAPED_UNICODE),
        $d['map_address'], $d['map_lat'], $d['map_lng'], (int)$d['active'],
    ));
    return (int)db()->lastInsertId();
}

function get_card($id) {
    $st = db()->prepare('SELECT * FROM cards WHERE id = ? LIMIT 1');
    $st->execute(array((int)$id));
    return $st->fetch() ?: null;
}

function get_card_by_code($code) {
    $st = db()->prepare('SELECT * FROM cards WHERE code = ? LIMIT 1');
    $st->execute(array($code));
    return $st->fetch() ?: null;
}

function get_user_cards($user_id) {
    $st = db()->prepare('SELECT * FROM cards WHERE user_id = ? ORDER BY id DESC');
    $st->execute(array((int)$user_id));
    return $st->fetchAll();
}

function update_card($id, $data) {
    db_migrate();
    $allowed = array(
        'code', 'full_name', 'job_title', 'company', 'phone', 'phone2', 'email', 'website', 'address', 'bio',
        'logo', 'cover', 'template', 'color1', 'color2', 'qr_theme', 'qr_dots', 'qr_logo', 'logo_pos',
        'socials', 'custom_fields', 'map_address', 'map_lat', 'map_lng', 'active',
    );
    $sets = array();
    $vals = array();
    foreach ($data as $k => $v) {
        if (in_array($k, $allowed, true)) {
            $sets[] = "$k = ?";
            $vals[] = is_string($v) ? $v : ($v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE));
        }
    }
    if (empty($sets)) return;
    $vals[] = (int)$id;
    $st = db()->prepare('UPDATE cards SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $st->execute($vals);
}

function delete_card($id) {
    $card = get_card((int)$id);
    if ($card) {
        foreach (array('logo', 'cover') as $f) delete_upload($card[$f]);
    }
    $st = db()->prepare('DELETE FROM visits WHERE card_id = ?');
    $st->execute(array((int)$id));
    $st2 = db()->prepare('DELETE FROM cards WHERE id = ?');
    $st2->execute(array((int)$id));
}

function list_cards($search = '', $page = 1, $per = 20, $user_id = null) {
    $where = array();
    $vals = array();
    if ($user_id !== null) {
        $where[] = 'c.user_id = ?';
        $vals[] = (int)$user_id;
    }
    if ($search !== '') {
        $where[] = '(c.full_name LIKE ? OR c.company LIKE ? OR c.code LIKE ?)';
        $vals[] = '%' . $search . '%';
        $vals[] = '%' . $search . '%';
        $vals[] = '%' . $search . '%';
    }
    $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $off = max(0, ((int)$page - 1) * (int)$per);
    $st = db()->prepare('SELECT c.*, u.name AS owner_name FROM cards c LEFT JOIN users u ON u.id = c.user_id '
        . $w . ' ORDER BY c.id DESC LIMIT ' . (int)$per . ' OFFSET ' . $off);
    $st->execute($vals);
    $rows = $st->fetchAll();
    $st2 = db()->prepare('SELECT COUNT(*) AS c FROM cards c ' . $w);
    $st2->execute($vals);
    return array('rows' => $rows, 'total' => (int)$st2->fetch()['c']);
}

function count_cards() {
    return (int)db()->query('SELECT COUNT(*) AS c FROM cards')->fetch()['c'];
}

function count_user_cards($user_id) {
    $st = db()->prepare('SELECT COUNT(*) AS c FROM cards WHERE user_id = ?');
    $st->execute(array((int)$user_id));
    return (int)$st->fetch()['c'];
}

// ---------------------------------------------------------------------------
// Visits
// ---------------------------------------------------------------------------
function is_bot_ua($ua) {
    $ua = (string)$ua;
    if (trim($ua) === '') return true;
    return (bool)preg_match('/(bot|crawl|spider|slurp|preview|headless|curl|wget|python-requests|httpclient|facebookexternalhit|whatsapp|telegrambot|googlebot|bingbot|duckduckbot|yandex|baiduspider|semrush|ahrefs|mj12|dotbot|petalbot|applebot|bytespider|expanse|archive.org|ia_archiver)/i', $ua);
}

function log_visit($card_id, $ip, $ua, $referer) {
    if (is_bot_ua($ua)) return;
    $st = db()->prepare('INSERT INTO visits (card_id, ip, user_agent, referer) VALUES (?, ?, ?, ?)');
    $st->execute(array((int)$card_id, substr((string)$ip, 0, 45), substr((string)$ua, 0, 250), substr((string)$referer, 0, 250)));
    db()->prepare('UPDATE cards SET visits = visits + 1 WHERE id = ?')->execute(array((int)$card_id));
}

function total_visits() {
    return (int)db()->query('SELECT SUM(visits) AS v FROM cards')->fetch()['v'];
}

function today_visits() {
    $st = db()->prepare('SELECT COUNT(*) AS c FROM visits WHERE DATE(visited_at) = ?');
    $st->execute(array(date('Y-m-d')));
    return (int)$st->fetch()['c'];
}

function card_visits($card_id) {
    $st = db()->prepare('SELECT COUNT(*) AS c FROM visits WHERE card_id = ?');
    $st->execute(array((int)$card_id));
    return (int)$st->fetch()['c'];
}

function recent_visits($card_id, $limit = 15) {
    $st = db()->prepare('SELECT * FROM visits WHERE card_id = ? ORDER BY visited_at DESC LIMIT ' . (int)$limit);
    $st->execute(array((int)$card_id));
    return $st->fetchAll();
}

function visits_by_day($days = 14) {
    $st = db()->prepare('SELECT DATE(visited_at) AS d, COUNT(*) AS c FROM visits
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL ' . (int)$days . ' DAY)
        GROUP BY d');
    $st->execute();
    $map = array();
    foreach ($st->fetchAll() as $r) $map[$r['d']] = (int)$r['c'];
    $out = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $ts = time() - $i * 86400;
        $key = date('Y-m-d', $ts);
        $out[$key] = array('label' => fa_date($ts), 'count' => isset($map[$key]) ? $map[$key] : 0);
    }
    return array(
        'labels' => array_values(array_column($out, 'label')),
        'counts' => array_values(array_column($out, 'count')),
    );
}

// ---------------------------------------------------------------------------
// Card helpers
// ---------------------------------------------------------------------------
function social_keys() {
    return array('instagram', 'telegram', 'whatsapp', 'linkedin', 'twitter', 'youtube', 'tiktok', 'facebook', 'github', 'aparat', 'threads', 'snapchat', 'pinterest', 'website');
}

function card_socials($card) {
    $s = $card['socials'] ?? '{}';
    $arr = json_decode($s, true);
    return is_array($arr) ? $arr : array();
}

function card_custom_fields($card) {
    $s = $card['custom_fields'] ?? '[]';
    $arr = json_decode($s, true);
    if (!is_array($arr)) return array();
    return array_values(array_filter($arr, function ($f) {
        return is_array($f) && !empty($f['label']);
    }));
}

function card_public_url($card) {
    return base_url($card['code']);
}

function card_qr_url($card, $extra = '') {
    return base_url('qr/' . $card['code'] . '.png') . ($extra !== '' ? '?' . ltrim($extra, '?') : '');
}

function card_cover_style($card) {
    return 'background-image:linear-gradient(135deg, ' . e($card['color1']) . ', ' . e($card['color2']) . ');';
}

function normalize_url($u) {
    $u = trim((string)$u);
    if ($u === '') return '';
    if (strpos($u, 'http://') !== 0 && strpos($u, 'https://') !== 0) $u = 'https://' . $u;
    return $u;
}

function whatsapp_link($phone, $text = '') {
    $p = preg_replace('/[^0-9]/', '', digits_to_latin($phone));
    return 'https://wa.me/' . $p . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function telegram_link($username) {
    $u = ltrim(trim($username), '@');
    return 'https://t.me/' . $u;
}

function social_detect($key, $value) {
    // value may be full URL or handle; return a clean profile URL if possible
    $v = trim((string)$value);
    if ($v === '') return '';
    if (preg_match('#^(javascript|vbscript|data):#i', $v)) return '';
    $v = preg_replace('/^https?:\/\/(www\.)?/i', '', $v);
    $v = rtrim($v, '/');
    switch ($key) {
        case 'instagram': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://instagram.com/' . ltrim($v, '@');
        case 'telegram': return strpos($value, 't.me') !== false ? (preg_match('/^https?:\/\//i', $value) ? $value : 'https://' . $value) : telegram_link($v);
        case 'whatsapp': return preg_match('/^https?:\/\//i', $value) ? $value : whatsapp_link($v);
        case 'linkedin': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://linkedin.com/in/' . ltrim($v, '@');
        case 'twitter': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://x.com/' . ltrim($v, '@');
        case 'youtube': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://youtube.com/@' . ltrim($v, '@');
        case 'tiktok': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://tiktok.com/@' . ltrim($v, '@');
        case 'facebook': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://facebook.com/' . ltrim($v, '@');
        case 'github': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://github.com/' . ltrim($v, '@');
        case 'aparat': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://aparat.com/' . ltrim($v, '@');
        case 'threads': return preg_match('/^https?:\/\//i', $value) ? $value : 'https://threads.net/@' . ltrim($v, '@');
        default:
            $r = trim((string)$value);
            if (preg_match('#^(https?://|mailto:|tel:)#i', $r)) return $r;
            return 'https://' . $r;
    }
}
