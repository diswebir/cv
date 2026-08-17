<?php

function panel_home($segments = array()) {
    require_login();
    $sub = isset($segments[1]) ? $segments[1] : '';
    if ($sub === 'card') {
        $action = isset($segments[2]) ? $segments[2] : '';
        if ($action === 'new') panel_card_form(null);
        elseif ($action === 'edit') panel_card_form((int)get('id', 0));
        elseif ($action === 'delete') panel_card_delete();
        elseif ($action === 'toggle') panel_card_toggle();
        elseif (is_numeric($action)) panel_card_stats((int)$action);
        else not_found();
    } else {
        panel_dashboard();
    }
}

function panel_dashboard() {
    $user = current_user();
    $cards = get_user_cards($user['id']);
    $totalVisits = 0;
    foreach ($cards as $c) $totalVisits += (int)$c['visits'];
    render_panel('پنل کاربری', 'panel/dashboard.php', array(
        'user' => $user,
        'cards' => $cards,
        'totalVisits' => $totalVisits,
    ), 'dashboard');
}

function card_form_socials() {
    $out = array();
    // Limit to prevent excessive data
    $maxSocials = 15;
    $count = 0;
    foreach (social_keys() as $k) {
        if ($count >= $maxSocials) break;
        $v = clean_text(post('social_' . $k), 300);
        if ($v !== '') {
            $out[$k] = $v;
            $count++;
        }
    }
    return $out;
}

function card_form_custom() {
    $labels = isset($_POST['cf_label']) && is_array($_POST['cf_label']) ? $_POST['cf_label'] : array();
    $values = isset($_POST['cf_value']) && is_array($_POST['cf_value']) ? $_POST['cf_value'] : array();
    $out = array();
    // Limit to 20 custom fields to prevent DoS
    $maxFields = 20;
    $count = 0;
    foreach ($labels as $i => $l) {
        if ($count >= $maxFields) break;
        $l = clean_text($l, 100);
        $v = clean_text(isset($values[$i]) ? $values[$i] : '', 500);
        if ($l !== '' && $v !== '') {
            $out[] = array('label' => $l, 'value' => $v);
            $count++;
        }
    }
    return $out;
}

function float_or_null($v, $maxAbs = 90) {
    $v = preg_replace('/[^0-9.\-]/', '', digits_to_latin((string)$v));
    if ($v === '' || $v === '-' || $v === '.') return null;
    $f = (float)$v;
    if ($f < -$maxAbs || $f > $maxAbs) return null;
    return $f;
}

function handle_card_form($card = null) {
    $error = '';
    $fullNameError = '';
    $emailError = '';
    $phoneError = '';
    $codeError = '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $error;
    csrf_check();

    $user = current_user();
    $isNew = $card === null;

    $fullName = clean_text(post('full_name'), 120);
    if ($fullName === '') {
        $fullNameError = 'نام و نام خانوادگی الزامی است.';
    }

    $email = strtolower(clean_text(post('email'), 160));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'ایمیل واردشده معتبر نیست.';
    }
    $phone = clean_text(post('phone'), 40);
    if ($phone !== '' && !preg_match('/^[0-9+\s().\-]{5,20}$/', digits_to_latin($phone))) {
        $phoneError = 'شماره موبایل واردشده معتبر نیست.';
    }

    $code = '';
    if ($isNew) {
        $code = clean_text(post('code'), 16);
        if (!is_valid_card_code($code)) {
            $code = unique_card_code();
        } else {
            $taken = get_card_by_code($code);
            if ($taken) $code = unique_card_code();
        }
    } else {
        // For edit, validate the code if it was changed
        $submittedCode = clean_text(post('code'), 16);
        if ($submittedCode !== '' && $submittedCode !== $card['code']) {
            if (!is_valid_card_code($submittedCode)) {
                $codeError = 'کد کوتاه نامعتبر است (فقط حروف انگلیسی و اعداد، ' . card_code_min() . ' تا ' . card_code_max() . ' کاراکتر).';
            } elseif (get_card_by_code($submittedCode)) {
                $codeError = 'این کد قبلاً استفاده شده است.';
            } else {
                $data['code'] = $submittedCode;
            }
        }
    }

    if ($fullNameError !== '' || $emailError !== '' || $phoneError !== '' || $codeError !== '') {
        return array(
            'error' => 'لطفاً خطاهای فرم را برطرف کنید.',
            'fullNameError' => $fullNameError,
            'emailError' => $emailError,
            'phoneError' => $phoneError,
            'codeError' => $codeError,
        );
    }

    $data = array(
        'full_name' => $fullName,
        'job_title' => clean_text(post('job_title'), 120),
        'company' => clean_text(post('company'), 160),
        'phone' => $phone,
        'phone2' => clean_text(post('phone2'), 40),
        'email' => $email,
        'website' => clean_text(post('website'), 200),
        'address' => clean_text(post('address'), 1000),
        'bio' => clean_text(post('bio'), 2000),
        'template' => in_array(post('template'), card_templates_allowed(), true) ? post('template') : 'classic',
        'color1' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)post('color1')) ? post('color1') : '#4f46e5',
        'color2' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)post('color2')) ? post('color2') : '#7c3aed',
        'qr_theme' => in_array(post('qr_theme'), VQR::themes_list(), true) ? post('qr_theme') : 'classic',
        'qr_dots' => in_array(post('qr_dots'), array('square', 'round', 'circle'), true) ? post('qr_dots') : 'square',
        'qr_logo' => post('qr_logo') === '1' ? 1 : 0,
        'logo_pos' => in_array(post('logo_pos'), array('center', 'left', 'right'), true) ? post('logo_pos') : 'center',
        'map_address' => clean_text(post('map_address'), 255),
        'map_lat' => float_or_null(post('map_lat'), 90),
        'map_lng' => float_or_null(post('map_lng'), 180),
        'active' => post('active') === '1' ? 1 : 0,
        'socials' => json_encode(card_form_socials(), JSON_UNESCAPED_UNICODE),
        'custom_fields' => json_encode(card_form_custom(), JSON_UNESCAPED_UNICODE),
    );

    $uploaded = array();
    $removed = array();
    foreach (array('logo', 'cover') as $f) {
        if (post('rm_' . $f) === '1') {
            if ($card && $card[$f]) $removed[] = $card[$f];
            $data[$f] = '';
            continue;
        }
        $up = upload_file($f, array('allowed' => array('jpg', 'jpeg', 'png', 'webp')));
        if ($up['ok']) {
            $data[$f] = $up['path'];
            $uploaded[] = $up['path'];
            if ($card && $card[$f]) $removed[] = $card[$f];
        } elseif ($up['error'] !== '') {
            $error = $up['error'];
        }
    }

    if ($error !== '') {
        foreach ($uploaded as $p) delete_upload($p);
        return $error;
    }
    foreach ($removed as $p) delete_upload($p);

    if ($isNew) {
        $data['code'] = $code;
        try {
            $newId = create_card($user['id'], $data);
        } catch (Exception $e) {
            // Most likely a UNIQUE constraint violation on `code` due to a
            // concurrent request (TOCTOU between get_card_by_code and insert).
            // Clean up the uploaded files so they do not become orphans, then
            // retry once with a fresh unique code.
            foreach ($uploaded as $p) delete_upload($p);
            $data['code'] = unique_card_code();
            try {
                $newId = create_card($user['id'], $data);
            } catch (Exception $e2) {
                foreach ($uploaded as $p) delete_upload($p);
                return 'ساخت کارت ناموفق بود. لطفاً مجدداً تلاش کنید.';
            }
        }
        flash('کارت ویزیت با موفقیت ساخته شد.');
        redirect('panel/card/edit?id=' . $newId);
    }

    try {
        update_card($card['id'], $data);
    } catch (Exception $e) {
        // On a DB error the new uploads are not linked to the card; remove them
        // to avoid orphaned files on disk.
        foreach ($uploaded as $p) delete_upload($p);
        return 'ذخیره‌سازی تغییرات ناموفق بود. لطفاً مجدداً تلاش کنید.';
    }
    flash('تغییرات با موفقیت ذخیره شد.');
    redirect('panel/card/edit?id=' . $card['id']);
}

function resolve_pre_code($card) {
    if ($card) return $card['code'];
    $c = clean_text(post('code'), 16);
    if (is_valid_card_code($c) && !get_card_by_code($c)) return $c;
    return unique_card_code();
}

function panel_card_form($id) {
    $user = current_user();
    $card = null;
    if ($id) {
        $card = get_card($id);
        if (!$card || (int)$card['user_id'] !== (int)$user['id']) not_found();
    }
    $error = handle_card_form($card);

    $preCode = resolve_pre_code($card);
    $socials = $card ? card_socials($card) : array();
    $fields = $card ? card_custom_fields($card) : array();

    $fullNameError = '';
    $emailError = '';
    $phoneError = '';
    $codeError = '';
    if (is_array($error)) {
        $fullNameError = $error['fullNameError'] ?? '';
        $emailError = $error['emailError'] ?? '';
        $phoneError = $error['phoneError'] ?? '';
        $codeError = $error['codeError'] ?? '';
        $error = $error['error'];
    }

    render_panel($card ? 'ویرایش کارت' : 'ساخت کارت جدید', 'panel/card_form.php', array(
        'user' => $user,
        'card' => $card,
        'error' => $error,
        'preCode' => $preCode,
        'socials' => $socials,
        'fields' => $fields,
        'fullNameError' => $fullNameError,
        'emailError' => $emailError,
        'phoneError' => $phoneError,
        'codeError' => $codeError,
    ), 'cards');
}

function panel_card_delete() {
    csrf_check();
    $user = current_user();
    $id = (int)get('id', 0);
    $card = get_card($id);
    if (!$card) not_found();
    if ((int)$card['user_id'] !== (int)$user['id'] && !is_admin()) not_found();
    delete_card($id);
    flash('کارت حذف شد.');
    redirect(is_admin() ? 'admin/cards' : 'panel');
}

function panel_card_toggle() {
    csrf_check();
    $user = current_user();
    $id = (int)get('id', 0);
    $card = get_card($id);
    if (!$card) not_found();
    if ((int)$card['user_id'] !== (int)$user['id'] && !is_admin()) not_found();
    $new = $card['active'] ? 0 : 1;
    update_card($id, array('active' => $new));
    flash($new ? 'کارت فعال شد.' : 'کارت غیرفعال شد.');
    redirect(is_admin() ? 'admin/cards' : 'panel');
}

function panel_card_stats($id) {
    $user = current_user();
    $card = get_card($id);
    if (!$card) not_found();
    if ((int)$card['user_id'] !== (int)$user['id'] && !is_admin()) not_found();
    $chart = visits_by_day(14);
    $recent = recent_visits($id, 12);
    render_panel('آمار کارت', 'panel/card_stats.php', array(
        'user' => $user,
        'card' => $card,
        'chart' => $chart,
        'recent' => $recent,
    ), 'cards');
}
