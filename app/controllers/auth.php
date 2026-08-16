<?php

function auth_login() {
    if (is_logged_in()) redirect('panel');
    $error = '';
    $email = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $email = strtolower(trim(post('email')));
        $pass = (string)post('password');
        $res = attempt_login($email, $pass);
        if ($res['ok']) redirect('panel');
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
