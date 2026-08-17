<?php

function admin_route($segments = array()) {
    require_admin();
    $sub = isset($segments[1]) ? $segments[1] : '';
    switch ($sub) {
        case 'users':
            admin_users();
            break;
        case 'user':
            admin_user_action(isset($segments[2]) ? $segments[2] : '');
            break;
        case 'cards':
            admin_cards();
            break;
        case 'card':
            admin_card_action(isset($segments[2]) ? $segments[2] : '');
            break;
        case 'settings':
            admin_settings();
            break;
        default:
            admin_dashboard();
            break;
    }
}

function admin_dashboard() {
    $chart = visits_by_day(14);
    $cards = list_cards('', 1, 8);
    $users = list_users('', 1, 8);
    render_panel('داشبورد مدیریت', 'admin/dashboard.php', array(
        'user' => current_user(),
        'stats' => array(
            'users' => count_users(),
            'cards' => count_cards(),
            'visits' => total_visits(),
            'today' => today_visits(),
        ),
        'chart' => $chart,
        'cards' => $cards['rows'],
        'users' => $users['rows'],
    ), 'admin-dashboard');
}

function admin_users($addError = '', $addData = array()) {
    $search = clean_text(get('q'), 80);
    $page = max(1, (int)get('page', 1));
    $list = list_users($search, $page, 15);
    render_panel('مدیریت کاربران', 'admin/users.php', array(
        'user' => current_user(),
        'list' => $list,
        'search' => $search,
        'page' => $page,
        'addError' => $addError,
        'addData' => $addData,
    ), 'admin-users');
}

function admin_user_action($action) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') not_found();
    csrf_check();
    $id = (int)get('id', 0);
    $me = current_user();

    if ($action === 'add') {
        $name = clean_text(post('name'), 100);
        $email = strtolower(trim(post('email')));
        $pass = (string)post('password');
        $role = post('role') === 'admin' ? 'admin' : 'user';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 6) {
            admin_users('اطلاعات کاربر ناقص است (نام، ایمیل معتبر و رمز حداقل ۶ کاراکتر).', array('name' => $name, 'email' => $email, 'role' => $role));
            exit;
        }
        if (get_user_by_email($email)) {
            admin_users('این ایمیل قبلاً ثبت شده است.', array('name' => $name, 'email' => $email, 'role' => $role));
            exit;
        }
        create_user($name, $email, $pass, $role);
        flash('کاربر جدید ساخته شد.');
        redirect('admin/users');
    }

    $target = get_user($id);
    if (!$target) not_found();

    if ($action === 'toggle') {
        if ((int)$target['id'] === (int)$me['id']) {
            flash('نمی‌توانید حساب خودتان را غیرفعال کنید.', 'error');
            redirect('admin/users');
        }
        update_user($id, array('status' => $target['status'] ? 0 : 1));
        flash($target['status'] ? 'کاربر غیرفعال شد.' : 'کاربر فعال شد.');
        redirect('admin/users');
    }

    if ($action === 'reset') {
        $pass = (string)post('password');
        if (mb_strlen($pass) < 6) {
            flash('رمز عبور جدید باید حداقل ۶ کاراکتر باشد.', 'error');
        } else {
            update_user($id, array('password' => password_hash($pass, PASSWORD_DEFAULT)));
            flash('رمز عبور کاربر تغییر کرد.');
        }
        redirect('admin/users');
    }

    if ($action === 'delete') {
        if ((int)$target['id'] === (int)$me['id']) {
            flash('نمی‌توانید حساب خودتان را حذف کنید.', 'error');
            redirect('admin/users');
        }
        delete_user($id);
        flash('کاربر حذف شد.');
        redirect('admin/users');
    }

    not_found();
}

function admin_cards() {
    $search = clean_text(get('q'), 80);
    $page = max(1, (int)get('page', 1));
    $list = list_cards($search, $page, 15);
    render_panel('مدیریت کارت‌ها', 'admin/cards.php', array(
        'user' => current_user(),
        'list' => $list,
        'search' => $search,
        'page' => $page,
    ), 'admin-cards');
}

function admin_card_action($action) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') not_found();
    csrf_check();
    $id = (int)get('id', 0);
    $card = get_card($id);
    if (!$card) not_found();
    if ($action === 'toggle') {
        update_card($id, array('active' => $card['active'] ? 0 : 1));
        flash($card['active'] ? 'کارت غیرفعال شد.' : 'کارت فعال شد.');
    } elseif ($action === 'code') {
        $newCode = clean_text(post('code'), 16);
        if (!is_valid_card_code($newCode)) {
            flash('کد کوتاه نامعتبر است (فقط حروف انگلیسی و اعداد، ' . card_code_min() . ' تا ' . card_code_max() . ' کاراکتر).', 'error');
        } elseif ($newCode === $card['code']) {
            flash('کد تغییری نکرده است.', 'error');
        } elseif (get_card_by_code($newCode)) {
            flash('این کد قبلاً استفاده شده است.', 'error');
        } else {
            update_card($id, array('code' => $newCode));
            flash('کد کوتاه کارت تغییر کرد.');
        }
    } elseif ($action === 'delete') {
        delete_card($id);
        flash('کارت حذف شد.');
    } else {
        not_found();
    }
    redirect('admin/cards');
}

function admin_settings() {
    $error = '';
    $saved = false;
    $detected = rtrim(
        (is_https() ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . path_prefix(),
        '/'
    );
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $appName = clean_text(post('app_name'), 80) !== '' ? clean_text(post('app_name'), 80) : 'cv4u';
        $baseUrl = rtrim(clean_text(post('base_url'), 200), '/');
        $allowReg = post('allow_registration') === '1' ? '1' : '0';
        $footer = clean_text(post('footer_text'), 500);
        if ($baseUrl === '') $baseUrl = $detected;
        // Use sanitize_base_url from install.php for proper validation
        require_once VC_ROOT . '/install.php';
        $sanitized = sanitize_base_url($baseUrl);
        if ($sanitized === '') {
            $error = 'آدرس پایه نامعتبر است. فقط http/https و کاراکترهای مجاز hostname/path مجاز هستند.';
        } else {
            $baseUrl = $sanitized;
            $len = (int)post('code_length', 6);
            if ($len < 4 || $len > 12) $len = 6;
            set_setting('app_name', $appName);
            set_setting('base_url', $baseUrl);
            set_setting('allow_registration', $allowReg);
            set_setting('footer_text', $footer);
            set_setting('code_length', (string)$len);
            $GLOBALS['__cfg']['base_url'] = $baseUrl;
            $saved = true;
            flash('تنظیمات ذخیره شد.');
            redirect('admin/settings');
        }
    }
    render_panel('تنظیمات سایت', 'admin/settings.php', array(
        'user' => current_user(),
        'error' => $error,
        'detected' => $detected,
    ), 'admin-settings');
}
