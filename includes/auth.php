<?php

function current_user() {
    static $u = false;
    if ($u !== false) return $u;
    if (empty($_SESSION['user_id'])) { $u = null; return $u; }
    $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute(array((int)$_SESSION['user_id']));
    $u = $st->fetch();
    if (!$u || (int)$u['status'] !== 1) {
        $u = null;
        unset($_SESSION['user_id']);
    }
    return $u;
}

function is_logged_in() {
    return (bool)current_user();
}

function is_admin() {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login() {
    if (!current_user()) {
        flash('برای ادامه‌ی کار ابتدا وارد حساب خود شوید.', 'error');
        redirect('login');
    }
}

function require_admin() {
    if (!is_admin()) {
        http_error_page(403, 'دسترسی مجاز نیست. برای ورود به این بخش باید مدیر باشید.');
    }
}

function login_fail_key($email, $byIp = false) {
    return $byIp ? 'login_fail_ip_' . md5(client_ip()) : 'login_fail_' . md5(strtolower($email));
}

function login_rate_limited($email) {
    foreach (array(login_fail_key($email), login_fail_key($email, true)) as $key) {
        if (empty($_SESSION[$key])) continue;
        $info = $_SESSION[$key];
        if ($info['count'] >= 5 && (time() - $info['time']) < 900) return true;
        if ((time() - $info['time']) >= 900) unset($_SESSION[$key]);
    }
    return false;
}

function login_rate_fail($email) {
    foreach (array(login_fail_key($email), login_fail_key($email, true)) as $key) {
        $info = $_SESSION[$key] ?? array('count' => 0, 'time' => time());
        $info['count']++;
        $info['time'] = time();
        $_SESSION[$key] = $info;
    }
}

function login_rate_reset($email) {
    unset($_SESSION[login_fail_key($email)]);
    unset($_SESSION[login_fail_key($email, true)]);
}

function attempt_login($email, $password) {
    if (login_rate_limited($email)) {
        return array('ok' => false, 'error' => 'تلاش‌های ناموفق زیاد بود. لطفاً ۱۵ دقیقه دیگر دوباره امتحان کنید.');
    }
    $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute(array($email));
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        login_rate_fail($email);
        return array('ok' => false, 'error' => 'ایمیل یا رمز عبور اشتباه است.');
    }
    if ((int)$user['status'] !== 1) {
        login_rate_fail($email);
        return array('ok' => false, 'error' => 'حساب شما غیرفعال شده است. با مدیر تماس بگیرید.');
    }
    login_rate_reset($email);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    return array('ok' => true, 'user' => $user);
}

function logout_user() {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function register_user($name, $email, $password) {
    $name = clean_text($name, 100);
    $email = strtolower(trim((string)$email));
    if ($name === '') return array('ok' => false, 'error' => 'نام را وارد کنید.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('ok' => false, 'error' => 'ایمیل معتبر نیست.');
    if (mb_strlen($password) < 6) return array('ok' => false, 'error' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.');
    $existing = get_user_by_email($email);
    if ($existing) return array('ok' => false, 'error' => 'این ایمیل قبلاً ثبت شده است.');
    $id = create_user($name, $email, $password, 'user');
    if (!$id) return array('ok' => false, 'error' => 'ثبت‌نام ناموفق بود. دوباره تلاش کنید.');
    return array('ok' => true, 'id' => $id);
}
