<?php
/**
 * Front controller — همه‌ی مسیرها به این فایل می‌آیند
 * (لینک‌های کوتاه، پنل کاربر، پنل مدیریت، خروجی QR و ...)
 */

require __DIR__ . '/includes/init.php';

$path = route_path();
if (isset($_GET['u'])) {
    $base = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($path === '' || ($base !== '' && ($path === $base || strpos($path, $base . '/') === 0))) {
        $path = (string)$_GET['u'];
    }
}

$segments = array_values(array_filter(explode('/', $path), function ($s) { return $s !== ''; }));
$route = isset($segments[0]) ? $segments[0] : '';

require VC_ROOT . '/app/views/render.php';
require VC_ROOT . '/app/controllers/auth.php';
require VC_ROOT . '/app/controllers/front.php';
require VC_ROOT . '/app/controllers/card.php';
require VC_ROOT . '/app/controllers/user.php';
require VC_ROOT . '/app/controllers/admin.php';

switch ($route) {
    case '':
        front_page();
        break;

    case 'login':
        auth_login();
        break;
    case 'register':
        auth_register();
        break;
    case 'logout':
        auth_logout();
        break;

    case 'panel':
        panel_home($segments);
        break;

    case 'c':
        $code = isset($segments[1]) ? $segments[1] : '';
        if ($code === '') not_found();
        http_response_code(301);
        header('Location: ' . card_public_url(array('code' => $code)));
        exit;
        break;

    case 'qr':
        qr_image($segments);
        break;

    case 'vcf':
        download_vcf(isset($segments[1]) ? $segments[1] : '');
        break;

    case 'admin':
        admin_route($segments);
        break;

    default:
        if ($route !== '') public_card($route);
        not_found();
        break;
}
