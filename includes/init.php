<?php
/**
 * Bootstrap. Loaded by every public entry point (index.php and install.php
 * uses its own self-contained bootstrapping instead).
 */

define('VC_VERSION', '1.0.0');
define('VC_ROOT', dirname(__DIR__));
define('VC_INC', __DIR__);
define('VC_UPLOAD_DIR', VC_ROOT . '/uploads');

require_once VC_INC . '/helpers.php';
require_once VC_INC . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => path_prefix(), // dynamic for subdirectory installs
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ));
    session_name('vcsess');
    session_start();
}

// Session idle timeout (absolute timeout) - default 2 hours
$sessionMaxIdle = (int)config('session_idle_timeout', 7200);
if (isset($_SESSION['last_activity'])) {
    $idle = time() - (int)$_SESSION['last_activity'];
    if ($idle > $sessionMaxIdle) {
        // Session expired due to inactivity
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start(); // start fresh session
    }
}

// Only update last_activity on actual user activity (non-asset, non-AJAX requests)
// This prevents the idle timeout from never triggering because it's reset on every request
$isAssetRequest = preg_match('#\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?|ico|map)$#i', $_SERVER['REQUEST_URI'] ?? '');
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAssetRequest && !$isAjaxRequest) {
    $_SESSION['last_activity'] = time();
}

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

// Content Security Policy headers
// Use per-request nonce so we can drop 'unsafe-inline' for script-src.
if (!isset($GLOBALS['__csp_nonce'])) {
    try {
        $GLOBALS['__csp_nonce'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $GLOBALS['__csp_nonce'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
}
$__cspNonce = $GLOBALS['__csp_nonce'];
$csp = "default-src 'self'; " .
       "script-src 'self' 'nonce-" . $__cspNonce . "' https://cdn.jsdelivr.net; " .
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
       "font-src 'self' data: https://fonts.gstatic.com; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self'; " .
       "frame-ancestors 'none'; " .
       "base-uri 'self'; " .
       "form-action 'self';";
header("Content-Security-Policy: $csp");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
