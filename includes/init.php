<?php
/**
 * Bootstrap. Loaded by every public entry point (index.php and install.php
 * uses its own self-contained bootstrapping instead).
 */

define('VC_VERSION', '1.0.0');
define('VC_ROOT', dirname(__DIR__));
define('VC_INC', __DIR__);
define('VC_UPLOAD_DIR', VC_ROOT . '/uploads');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_name('vcsess');
    session_start();
}

require_once VC_INC . '/helpers.php';
require_once VC_INC . '/db.php';
require_once VC_INC . '/jalali.php';
require_once VC_INC . '/auth.php';
require_once VC_INC . '/models.php';
require_once VC_INC . '/vcf.php';
require_once VC_INC . '/QRRenderer.php';

$configFile = VC_INC . '/config.php';
if (!is_file($configFile)) {
    $p = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($p === '.' || $p === '/') $p = '';
    header('Location: ' . ($p === '' ? '/install.php' : $p . '/install.php'));
    exit;
}

$GLOBALS['__cfg'] = require $configFile;
date_default_timezone_set((string)config('timezone', 'Asia/Tehran'));

if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
