<?php

function not_found() {
    http_response_code(404);
    $GLOBALS['__page_title'] = 'صفحه پیدا نشد';
    $pageTitle = 'صفحه پیدا نشد';
    $content = '<div class="nf-wrap"><div class="nf-404">۴۰۴</div><p>صفحه‌ای که دنبال آن هستید پیدا نشد.</p><a class="btn" href="' . e(base_url('')) . '">بازگشت به خانه</a></div>';
    require VC_ROOT . '/app/views/layouts/public.php';
    exit;
}

function render_public($title, $file, $vars = array()) {
    extract($vars, EXTR_SKIP);
    ob_start();
    require VC_ROOT . '/app/views/' . $file;
    $content = ob_get_clean();
    $pageTitle = $title;
    require VC_ROOT . '/app/views/layouts/public.php';
}

function render_panel($title, $file, $vars = array(), $active = '') {
    extract($vars, EXTR_SKIP);
    ob_start();
    require VC_ROOT . '/app/views/' . $file;
    $content = ob_get_clean();
    $pageTitle = $title;
    require VC_ROOT . '/app/views/layouts/panel.php';
}
