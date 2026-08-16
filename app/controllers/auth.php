<?php

function auth_login() {
    if (is_logged_in()) redirect('panel');
    $error = '';
    $email = '';
    // Where to send the user after a successful login. Defaults to the panel,
    // but if they came from a protected page (?next=...) we return them there.
    $next = (string)get('next', '');
    $safeNext = '';
    if ($next !== '' && preg_match('#^[a-zA-Z0-9_\-/]{1,120}$#', $next)) $safeNext = $next;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $email = strtolower(trim(post('email')));
        $pass = (string)post('password');
        $res = attempt_login($email, $pass);
        if ($res['ok']) {
            // "Remember me": extend the session cookie lifetime (30 days).
            if (post('remember') === '1') {
                $p = session_get_cookie_params();
                session_set_cookie_params(array(
                    'lifetime' => 30 * 86400,
                    'path' => $p['path'],
                    'secure' => $p['secure'],
                    'httponly' => $p['httponly'],
                    'samesite' => $p['samesite'] ?? 'Lax',
                ));
                session_regenerate_id(true);
            }
            redirect($safeNext !== '' ? $safeNext : 'panel');
        }
        $error = $res['error'];
    }
    render_public('ورود به حساب', 'auth/login.php', array(
        'error' => $error,
        'email' => $email,
    ));
}

function auth_register() {
    if (is_logged_in()) redirect('panel');
    if ((string)get_setting('allow_registration', '1') !== '1') {
        flash('ثبت‌نام کاربران جدید غیرفعال شده است.', 'error');
        redirect('login');
    }
    // Rate-limit registration attempts per IP to prevent mass account creation.
    if (rate_limit_blocked('register_ip_' . md5(client_ip()), 'x', 5, 3600)) {
        http_error_page(429, 'تعداد ثبت‌نام از این آی‌پی بیش از حد است. لطفاً بعداً تلاش کنید.');
    }
    $error = '';
    $data = array('name' => '', 'email' => '');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['name'] = clean_text(post('name'), 100);
        $data['email'] = strtolower(trim(post('email')));
        $pass = (string)post('password');
        $pass2 = (string)post('password2');
        if ($pass !== $pass2) {
            $error = 'تکرار رمز عبور مطابقت ندارد.';
        } else {
            rate_limit_hit('register_ip_' . md5(client_ip()), 'x', 5, 3600);
            $res = register_user($data['name'], $data['email'], $pass);
            if ($res['ok']) {
                attempt_login($data['email'], $pass);
                redirect('panel');
            }
            $error = $res['error'];
        }
    }
    render_public('ثبت‌نام', 'auth/register.php', array(
        'error' => $error,
        'data' => $data,
    ));
}

function auth_logout() {
    logout_user();
    redirect('');
}
