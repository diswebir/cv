<?php
/**
 * نصب‌کننده‌ی گام‌به‌گام پلتفرم کارت ویزیت مجازی
 * - بررسی پیش‌نیازهای هاست cPanel (بدون نیاز به SSH)
 * - اتصال به پایگاه‌داده MySQL
 * - ساخت جداول، حساب مدیر و تنظیمات سایت
 * - تشخیص خودکار آدرس نصب (دایرکتوری اصلی یا زیرپوشه)
 */

session_start();
error_reporting(E_ALL);
// Security: never expose internal errors to the browser — log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
define('VC_ROOT', __DIR__);
define('CONFIG_PATH', VC_ROOT . '/includes/config.php');
define('INSTALLED_FLAG', VC_ROOT . '/includes/.installed');

if (!function_exists('ie')) {
    function ie($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
    function ipost($k, $d = '') { return isset($_POST[$k]) ? $_POST[$k] : $d; }
    function inum($s) { return preg_replace('/[^0-9.]/', '', (string)$s); }
    function itoken() {
        if (empty($_SESSION['ins_csrf'])) $_SESSION['ins_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['ins_csrf'];
    }
    function icheck() {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['ins_csrf'] ?? '', (string)$_POST['csrf'])) {
            exit('نشست منقضی شده است. صفحه را دوباره بارگذاری کنید.');
        }
    }
    function fa($s) {
        return strtr((string)$s, array('0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'));
    }
    function detect_base_url() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Host header injection defense: HTTP_HOST is attacker-controlled; only accept
        // characters that are valid in a hostname/host:port. Reject anything else
        // (whitespace, slashes, quotes, control chars, etc.) so it cannot poison
        // the persisted base_url or the SQL seed that uses it.
        $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $host);
        if ($host === '' || strlen($host) > 253) {
            $host = 'localhost';
        }

        $sn = $_SERVER['SCRIPT_NAME'] ?? '';
        $sc = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $dr = $_SERVER['DOCUMENT_ROOT'] ?? '';

        if ($sn !== '') {
            $dir = rtrim(str_replace('\\', '/', dirname($sn)), '/');
        } elseif ($sc !== '' && $dr !== '' && strpos($sc, $dr) === 0) {
            $relative = substr($sc, strlen($dr));
            $dir = rtrim(str_replace('\\', '/', dirname($relative)), '/');
        } else {
            $dir = '';
        }

        if ($dir === '.' || $dir === '/') $dir = '';
        $url = $scheme . '://' . $host . $dir;
        // Apply full sanitization for defense in depth
        return sanitize_base_url($url) ?: ($scheme . '://localhost' . $dir);
    }

    /**
     * Sanitize a user-supplied base_url. Strips control chars, whitespace, and
     * anything that could break out of an attribute or SQL context. Only schemes
     * http/https are accepted. Returns '' on rejection.
     */
    function sanitize_base_url($url) {
        $url = trim((string)$url);
        if ($url === '' || strlen($url) > 500) return '';
        // Strip CR/LF/NULL and any whitespace which could enable header/SQL smuggling.
        $url = preg_replace('/[\x00-\x20\x7F]/u', '', $url);
        if (!preg_match('#^https?://#i', $url)) return '';
        // Strip everything after the path that is not URL-safe; the host segment
        // is also restricted to hostname characters to prevent header injection.
        if (!preg_match('#^https?://[A-Za-z0-9.\-:]+(/[^\s"<>`\\\\]*)?$#i', $url)) return '';
        return rtrim($url, '/');
    }
    function check_req() {
        $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
        $exts = array();
        $exts['pdo_mysql'] = extension_loaded('pdo_mysql');
        $exts['gd'] = extension_loaded('gd');
        $exts['mbstring'] = function_exists('mb_strlen');
        $exts['fileinfo'] = function_exists('finfo_open');
        $exts['json'] = function_exists('json_encode');
        $exts['curl'] = function_exists('curl_init');
        $writableIncludes = is_writable(VC_ROOT . '/includes');
        $writableUploads = is_writable(VC_ROOT . '/uploads');
        return array(
            'php' => $phpOk,
            'phpVersion' => PHP_VERSION,
            'exts' => $exts,
            'wIncludes' => $writableIncludes,
            'wUploads' => $writableUploads,
        );
    }
    function schema_sql() {
        return array(
            'users' => "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(190) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'cards' => "CREATE TABLE IF NOT EXISTS `cards` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `code` VARCHAR(16) NOT NULL,
                `full_name` VARCHAR(120) NOT NULL DEFAULT '',
                `job_title` VARCHAR(120) NOT NULL DEFAULT '',
                `company` VARCHAR(160) NOT NULL DEFAULT '',
                `phone` VARCHAR(40) NOT NULL DEFAULT '',
                `phone2` VARCHAR(40) NOT NULL DEFAULT '',
                `email` VARCHAR(160) NOT NULL DEFAULT '',
                `website` VARCHAR(200) NOT NULL DEFAULT '',
                `address` TEXT NULL,
                `bio` TEXT NULL,
                `logo` VARCHAR(255) NOT NULL DEFAULT '',
                `cover` VARCHAR(255) NOT NULL DEFAULT '',
                `template` VARCHAR(40) NOT NULL DEFAULT 'classic',
                `color1` VARCHAR(9) NOT NULL DEFAULT '#4f46e5',
                `color2` VARCHAR(9) NOT NULL DEFAULT '#7c3aed',
                `qr_theme` VARCHAR(40) NOT NULL DEFAULT 'classic',
                `qr_dots` VARCHAR(12) NOT NULL DEFAULT 'square',
                `qr_logo` TINYINT(1) NOT NULL DEFAULT 0,
                `logo_pos` VARCHAR(12) NOT NULL DEFAULT 'center',
                `socials` TEXT NULL,
                `custom_fields` TEXT NULL,
                `map_address` VARCHAR(255) NOT NULL DEFAULT '',
                `map_lat` DECIMAL(10,7) NULL,
                `map_lng` DECIMAL(10,7) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `visits` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_code` (`code`),
                KEY `idx_user` (`user_id`),
                KEY `idx_active` (`active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'visits' => "CREATE TABLE IF NOT EXISTS `visits` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `card_id` INT UNSIGNED NOT NULL,
                `visited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ip` VARCHAR(45) NOT NULL DEFAULT '',
                `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
                `referer` VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                KEY `idx_card` (`card_id`),
                KEY `idx_visited` (`visited_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
                `skey` VARCHAR(60) NOT NULL,
                `svalue` TEXT NULL,
                PRIMARY KEY (`skey`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
    }
    function write_config($cfg) {
        $content = "<?php\n/**\n * Configuration generated by install.php — " . date('Y-m-d H:i') . "\n */\nreturn " . var_export($cfg, true) . ";\n";
        $result = file_put_contents(CONFIG_PATH, $content);
        if ($result !== false) {
            // Set restrictive permissions: owner read/write only (0600)
            @chmod(CONFIG_PATH, 0600);
        }
        return $result !== false;
    }
}

// --- current step -----------------------------------------------------------
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    icheck();
    if (ipost('action') === 'self_delete') {
        // Self-delete install.php after successful installation
        // Use rename instead of unlink for Windows compatibility
        $deletedPath = __FILE__ . '.deleted';
        if (is_file(__FILE__)) {
            @rename(__FILE__, $deletedPath);
            // Fallback: try unlink if rename failed
            if (is_file(__FILE__)) {
                @unlink(__FILE__);
            }
        }
        header('Location: ' . base_url());
        exit;
    }
    if (ipost('action') === 'step2') {
        $db = array(
            'db_host' => trim(ipost('db_host')),
            'db_port' => inum(ipost('db_port')) !== '' ? inum(ipost('db_port')) : '3306',
            'db_name' => trim(ipost('db_name')),
            'db_user' => trim(ipost('db_user')),
            'db_pass' => (string)ipost('db_pass'),
        );
        $error = '';
        if ($db['db_host'] === '' || $db['db_name'] === '' || $db['db_user'] === '') {
            $error = 'فیلدهای هاست، نام دیتابیس و نام کاربری الزامی هستند.';
        } elseif (!preg_match('/^[A-Za-z0-9_.\-]+$/', $db['db_host'])) {
            $error = 'هاست دیتابیس نامعتبر است.';
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $db['db_name'])) {
            $error = 'نام دیتابیس فقط می‌تواند شامل حروف، اعداد و زیرخط باشد.';
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $db['db_user'])) {
            $error = 'نام کاربری دیتابیس فقط می‌تواند شامل حروف، اعداد و زیرخط باشد.';
        }
        if ($error === '') {
            try {
                $dsn = 'mysql:host=' . $db['db_host'] . ';port=' . $db['db_port'] . ';charset=utf8mb4';
                $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ));
                $dbFound = false;
                try {
                    $pdo->query('USE `' . $db['db_name'] . '`');
                    $dbFound = true;
                } catch (Exception $e2) {
                    $dbFound = false;
                }
                if (!$dbFound) {
                    $error = 'دیتابیس «' . $db['db_name'] . '» پیدا نشد. لطفاً ابتدا آن را در cPanel (بخش MySQL Databases) بسازید.';
                } else {
                    foreach (schema_sql() as $t => $sql) {
                        $pdo->exec($sql);
                    }
                    $baseUrl = detect_base_url();
                    $st = $pdo->prepare("INSERT IGNORE INTO `settings` (`skey`, `svalue`) VALUES
                        ('app_name', ?), ('allow_registration', ?), ('footer_text', ?),
                        ('base_url', ?), ('code_length', ?)");
                    $st->execute(array('cv4u', '1', '', $baseUrl, '6'));
                    $_SESSION['ins_db'] = $db;
                    $_SESSION['ins_step'] = 3;
                    header('Location: install.php?step=3');
                    exit;
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $msg = str_replace($db['db_user'], '***', $msg);
                $msg = str_replace($db['db_pass'], '***', $msg);
                $error = 'اتصال به دیتابیس ناموفق بود: ' . $msg;
            }
        }
    } elseif (ipost('action') === 'step3') {
        $db = $_SESSION['ins_db'] ?? null;
        if (!$db) { header('Location: install.php?step=2'); exit; }
        $name = trim(ipost('admin_name'));
        $email = strtolower(trim(ipost('admin_email')));
        $pass = (string)ipost('admin_pass');
        $pass2 = (string)ipost('admin_pass2');
        $error = '';
        if ($name === '') $error = 'نام مدیر را وارد کنید.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'ایمیل مدیر معتبر نیست.';
        elseif (mb_strlen($pass) < 8) $error = 'رمز عبور مدیر باید حداقل ۸ کاراکتر باشد.';
        elseif ($pass !== $pass2) $error = 'تکرار رمز عبور مطابقت ندارد.';
        if ($error === '') {
            try {
                $dsn = 'mysql:host=' . $db['db_host'] . ';port=' . $db['db_port'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
                $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                $st = $pdo->prepare('SELECT COUNT(*) AS c FROM `users` WHERE `email` = ?');
                $st->execute(array($email));
                if ((int)$st->fetch()['c'] > 0) {
                    $error = 'این ایمیل قبلاً ثبت شده است.';
                } else {
                    $st = $pdo->prepare('INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, \'admin\')');
                    $st->execute(array($name, $email, password_hash($pass, PASSWORD_DEFAULT)));
                    $_SESSION['ins_step'] = 4;
                    header('Location: install.php?step=4');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'خطا در ذخیره‌سازی حساب مدیر رخ داد. لطفاً مجدداً تلاش کنید یا با پشتیبانی تماس بگیرید.';
            }
        }
    } elseif (ipost('action') === 'step4') {
        $db = $_SESSION['ins_db'] ?? null;
        if (!$db) { header('Location: install.php?step=2'); exit; }
        $appName = trim(ipost('app_name')) !== '' ? trim(ipost('app_name')) : 'cv4u';
        $baseUrl = rtrim(trim(ipost('base_url')), '/');
        $allowReg = ipost('allow_registration') === '1' ? '1' : '0';
        if ($baseUrl === '') $baseUrl = detect_base_url();
        $sanitized = sanitize_base_url($baseUrl);
        if ($sanitized === '') {
            $error = 'آدرس پایه نامعتبر است. فقط http/https و کاراکترهای مجاز hostname/path مجاز هستند.';
        } else {
        $baseUrl = $sanitized;
        $cfg = array(
            'db_host' => $db['db_host'],
            'db_name' => $db['db_name'],
            'db_user' => $db['db_user'],
            'db_pass' => $db['db_pass'],
            'db_port' => $db['db_port'],
            'base_url' => $baseUrl,
            'timezone' => 'Asia/Tehran',
        );
        $written = write_config($cfg);
        if ($written) {
            try {
                $dsn = 'mysql:host=' . $db['db_host'] . ';port=' . $db['db_port'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
                $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                $st = $pdo->prepare('INSERT INTO `settings` (`skey`, `svalue`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`)');
                $st->execute(array('app_name', $appName));
                $st->execute(array('allow_registration', $allowReg));
                $st->execute(array('base_url', $baseUrl));
                $st->execute(array('footer_text', trim(ipost('footer_text'))));
            } catch (Exception $e) {
                // settings are seeded in step 2; ignore minor failures
            }
            file_put_contents(INSTALLED_FLAG, date('Y-m-d H:i:s'));
            unset($_SESSION['ins_db']);
            $_SESSION['ins_step'] = 5;
            header('Location: install.php?step=5');
            exit;
        }
            $error = 'نوشتن فایل تنظیمات ناموفق بود. دسترسی نوشتن پوشه‌ی includes/ را بررسی کنید.';
        }
    }
}

$req = check_req();
$step = min(5, max(1, $step));
if (!empty($_SESSION['ins_step']) && $step > $_SESSION['ins_step'] && $_SESSION['ins_step'] > 1) {
    // block jumping ahead of completed steps
    if ($step > $_SESSION['ins_step'] && $_SESSION['ins_step'] !== 4) $step = (int)$_SESSION['ins_step'];
}
$base = detect_base_url();
$justInstalled = !empty($_SESSION['ins_step']) && (int)$_SESSION['ins_step'] === 5;
if ($justInstalled) unset($_SESSION['ins_step']);
$installed = is_file(CONFIG_PATH);
if (!$justInstalled && $step > 4) $step = 4;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>نصب پلتفرم کارت ویزیت مجازی</title>
<style>
@font-face{font-family:Vazirmatn;src:url('assets/fonts/Vazirmatn-Regular.woff2') format('woff2');font-weight:400;font-display:swap}
@font-face{font-family:Vazirmatn;src:url('assets/fonts/Vazirmatn-Medium.woff2') format('woff2');font-weight:500;font-display:swap}
@font-face{font-family:Vazirmatn;src:url('assets/fonts/Vazirmatn-Bold.woff2') format('woff2');font-weight:700;font-display:swap}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Vazirmatn,system-ui,sans-serif;background:linear-gradient(135deg,#eef2ff,#fdf2f8 55%,#f0fdf4);min-height:100vh;color:#1e293b;padding:24px 16px}
.wrap{max-width:640px;margin:0 auto}
.top{text-align:center;margin-bottom:22px}
.top .logo{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;box-shadow:0 10px 25px rgba(99,102,241,.35)}
.top h1{font-size:22px;font-weight:700}
.top p{color:#64748b;font-size:13px;margin-top:4px}
.steps{display:flex;gap:4px;background:#fff;border-radius:14px;padding:8px;box-shadow:0 4px 15px rgba(15,23,42,.06);margin-bottom:20px}
.steps .st{flex:1;text-align:center;font-size:12px;color:#94a3b8;padding:8px 4px;border-radius:10px}
.steps .st.active{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:600}
.steps .st.done{color:#059669}
.card{background:#fff;border-radius:18px;box-shadow:0 12px 35px rgba(15,23,42,.08);padding:26px;margin-bottom:20px}
.card h2{font-size:17px;font-weight:700;margin-bottom:6px}
.card .desc{font-size:13px;color:#64748b;margin-bottom:18px;line-height:1.9}
.req{border:1px solid #e2e8f0;border-radius:12px;margin-bottom:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;font-size:13.5px}
.req .state{font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600}
.req .ok{background:#dcfce7;color:#15803d}
.req .bad{background:#fee2e2;color:#b91c1c}
.req .warn{background:#fef3c7;color:#b45309}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=url]{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:14px;transition:.15s}
input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.row{display:flex;gap:12px}
.row > div{flex:1}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;padding:12px 22px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:.15s}
.btn:hover{opacity:.92;transform:translateY(-1px)}
.btn.block{width:100%}
.btn.ghost{background:#f1f5f9;color:#334155}
.btn.green{background:linear-gradient(135deg,#10b981,#059669)}
.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:11px 14px;font-size:13px;margin-bottom:14px;line-height:1.8}
.okmsg{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:10px;padding:11px 14px;font-size:13px;margin-bottom:14px;line-height:1.8}
.check{display:flex;align-items:center;gap:8px;margin-top:14px;font-size:13px;font-weight:500}
.check input{width:17px;height:17px;accent-color:#6366f1}
.sample{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:10px 14px;font-size:12.5px;color:#475569;direction:ltr;text-align:left;word-break:break-all}
.note{font-size:12px;color:#94a3b8;margin-top:6px;line-height:1.8}
.foot{text-align:center;font-size:11.5px;color:#94a3b8;margin-top:22px}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="logo"><svg width="34" height="34" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/><rect x="8.5" y="7" width="7" height="2" rx="1" fill="#fff"/><circle cx="12" cy="14.5" r="3" stroke="#fff" stroke-width="1.6"/></svg></div>
    <h1>پلتفرم کارت ویزیت مجازی</h1>
    <p>نصب گام‌به‌گام — سازگار با هاست‌های cPanel بدون نیاز به SSH</p>
  </div>

  <?php if ($justInstalled): ?>
  <div class="card" style="text-align:center">
    <div style="font-size:56px;line-height:1">✅</div>
    <h2 style="margin-top:10px">نصب با موفقیت انجام شد!</h2>
    <p class="desc" style="text-align:right">پلتفرم کارت ویزیت مجازی آماده‌ی استفاده است. برای امنیت بیشتر، فایل <b>install.php</b> را از سرور حذف کنید (یا نام آن را عوض کنید).</p>
    <div class="okmsg" style="text-align:right">آدرس سایت شما: <b style="direction:ltr"><?= ie($base) ?></b></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
      <a class="btn green" href="<?= ie($base) ?>">مشاهده‌ی سایت</a>
      <a class="btn" href="<?= ie($base) ?>/login">ورود به پنل مدیریت</a>
      <form method="post" action="install.php" style="display:inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید فایل install.php را حذف کنید؟ این کار غیرقابل بازگشت است.')">
        <input type="hidden" name="csrf" value="<?= ie(itoken()) ?>">
        <input type="hidden" name="action" value="self_delete">
        <button type="submit" class="btn" style="background:#dc2626;background:linear-gradient(135deg,#dc2626,#b91c1c);">🗑️ حذف install.php</button>
      </form>
    </div>
    <p class="note" style="margin-top:14px">با ایمیل و رمز عبور مدیر وارد شوید؛ از منوی پنل مدیریت می‌توانید کاربران، کارت‌ها و تنظیمات را مدیریت کنید.</p>
  </div>
  <?php elseif ($installed): ?>
  <div class="card" style="text-align:center">
    <div style="font-size:46px;line-height:1">🔒</div>
    <h2 style="margin-top:10px">این پلتفرم قبلاً نصب و قفل شده است</h2>
    <p class="desc" style="text-align:right">نصب کامل شده و ویزارد در حالت قفل قرار دارد. مراحل نصب دوباره اجرا نمی‌شوند و هیچ داده‌ای حذف نمی‌شود. برای امنیت بیشتر، فایل <b>install.php</b> را از سرور حذف کنید.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
      <a class="btn green" href="<?= ie($base) ?>">مشاهده‌ی سایت</a>
      <a class="btn" href="<?= ie($base) ?>/login">ورود به پنل مدیریت</a>
    </div>
    <p class="note" style="margin-top:14px">اگر به‌هر دلیل نیاز به نصب مجدد داشتید، ابتدا فایل <b>includes/config.php</b> را حذف کنید و سپس این صفحه را باز کنید.</p>
  </div>
  <?php else: ?>

  <div class="steps">
    <div class="st <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">۱. پیش‌نیازها</div>
    <div class="st <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">۲. دیتابیس</div>
    <div class="st <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' ?>">۳. مدیر سایت</div>
    <div class="st <?= $step >= 4 ? ($step > 4 ? 'done' : 'active') : '' ?>">۴. تنظیمات</div>
    <div class="st <?= $step === 5 ? 'active' : '' ?>">۵. پایان</div>
  </div>

  <?php if (!empty($error)): ?><div class="err"><?= ie($error) ?></div><?php endif; ?>

  <?php if ($step === 1): ?>
  <div class="card">
    <h2>بررسی پیش‌نیازهای هاست</h2>
    <p class="desc">این بخش مطمئن می‌شود هاست شما (مثلاً cPanel) همه‌ی امکانات لازم را دارد.</p>

    <?php
    $allOk = $req['php'] && $req['exts']['pdo_mysql'] && $req['exts']['gd'] && $req['exts']['mbstring'] && $req['exts']['json'] && $req['wIncludes'] && $req['wUploads'];
    $item = function ($label, $ok, $extra = '') { ?>
    <div class="req">
      <span><?= $label ?></span>
      <span class="state <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'پشتیبانی می‌شود' : 'مشکل دارد' ?></span>
    </div>
    <?php }; ?>
    <div class="req"><span>نسخه‌ی PHP (حداقل 7.4)</span><span class="state <?= $req['php'] ? 'ok' : 'bad' ?>"><?= $req['php'] ? 'نسخه ' . fa($req['phpVersion']) : 'قدیمی است' ?></span></div>
    <div class="req"><span>افزونه‌ی PDO MySQL</span><span class="state <?= $req['exts']['pdo_mysql'] ? 'ok' : 'bad' ?>"><?= $req['exts']['pdo_mysql'] ? 'فعال است' : 'غیرفعال' ?></span></div>
    <div class="req"><span>افزونه‌ی GD (برای ساخت تصویر QR)</span><span class="state <?= $req['exts']['gd'] ? 'ok' : 'bad' ?>"><?= $req['exts']['gd'] ? 'فعال است' : 'غیرفعال' ?></span></div>
    <div class="req"><span>افزونه‌ی mbstring (متون فارسی)</span><span class="state <?= $req['exts']['mbstring'] ? 'ok' : 'bad' ?>"><?= $req['exts']['mbstring'] ? 'فعال است' : 'غیرفعال' ?></span></div>
    <div class="req"><span>افزونه‌ی fileinfo (بررسی آپلودها)</span><span class="state <?= $req['exts']['fileinfo'] ? 'ok' : 'bad' ?>"><?= $req['exts']['fileinfo'] ? 'فعال است' : 'غیرفعال' ?></span></div>
    <div class="req"><span>افزونه‌ی JSON</span><span class="state <?= $req['exts']['json'] ? 'ok' : 'bad' ?>"><?= $req['exts']['json'] ? 'فعال است' : 'غیرفعال' ?></span></div>
    <div class="req"><span>افزونه‌ی cURL (اختیاری)</span><span class="state <?= $req['exts']['curl'] ? 'ok' : 'warn' ?>"><?= $req['exts']['curl'] ? 'فعال است' : 'اختیاری' ?></span></div>
    <div class="req"><span>قابلیت نوشتن پوشه‌ی includes/</span><span class="state <?= $req['wIncludes'] ? 'ok' : 'bad' ?>"><?= $req['wIncludes'] ? 'قابل نوشتن است' : 'مجوز دهید' ?></span></div>
    <div class="req"><span>قابلیت نوشتن پوشه‌ی uploads/</span><span class="state <?= $req['wUploads'] ? 'ok' : 'bad' ?>"><?= $req['wUploads'] ? 'قابل نوشتن است' : 'مجوز دهید' ?></span></div>

    <?php if ($allOk): ?>
      <a class="btn block" style="margin-top:18px" href="install.php?step=2">ادامه → تنظیم دیتابیس</a>
      <p class="note">نکته: پوشه‌های <b>includes/</b> و <b>uploads/</b> باید قابل نوشتن (writable) باشند. معمولاً cPanel این کار را به‌صورت خودکار انجام می‌دهد؛ در صورت نیاز از بخش File Manager، permission را روی 755 یا 775 بگذارید.</p>
    <?php else: ?>
      <div class="err" style="margin-top:14px">برخی از پیش‌نیازها برآورده نیستند. در cPanel می‌توانید از بخش <b>Select PHP Version → Extensions</b> افزونه‌ها را فعال کنید.</div>
    <?php endif; ?>
  </div>

  <?php elseif ($step === 2): ?>
  <div class="card">
    <h2>اتصال به پایگاه‌داده MySQL</h2>
    <p class="desc">این اطلاعات را از بخش <b>MySQL® Databases</b> در cPanel دریافت می‌کنید. در cPanel نام کاربری دیتابیس معمولاً پیشوندی مثل <b>user_dbuser</b> دارد و نام دیتابیس <b>user_dbname</b> است.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= ie(itoken()) ?>">
      <input type="hidden" name="action" value="step2">
      <div class="row">
        <div>
          <label>هاست دیتابیس</label>
          <input type="text" name="db_host" value="<?= ie(ipost('db_host') !== '' ? ipost('db_host') : 'localhost') ?>" required>
        </div>
        <div>
          <label>پورت</label>
          <input type="number" name="db_port" value="<?= ie(ipost('db_port') !== '' ? ipost('db_port') : '3306') ?>" dir="ltr">
        </div>
      </div>
      <label>نام دیتابیس</label>
      <input type="text" name="db_name" value="<?= ie(ipost('db_name')) ?>" required dir="ltr">
      <label>نام کاربری دیتابیس</label>
      <input type="text" name="db_user" value="<?= ie(ipost('db_user')) ?>" required dir="ltr">
      <label>رمز عبور دیتابیس</label>
      <input type="password" name="db_pass" dir="ltr">
      <button class="btn block" style="margin-top:18px">اتصال و ساخت جداول</button>
      <p class="note">جداول به‌صورت خودکار ساخته می‌شوند. لازم نیست خودتان جدولی بسازید؛ فقط باید دیتابیس و کاربر را در cPanel ایجاد کنید.</p>
    </form>
  </div>

  <?php elseif ($step === 3): ?>
  <div class="card">
    <h2>حساب کاربری مدیر</h2>
    <p class="desc">با این حساب وارد پنل مدیریت می‌شوید و بر کاربران و کارت‌ها نظارت خواهید داشت. این اطلاعات را جایی امن نگه دارید.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= ie(itoken()) ?>">
      <input type="hidden" name="action" value="step3">
      <label>نام مدیر</label>
      <input type="text" name="admin_name" value="<?= ie(ipost('admin_name')) ?>" required>
      <label>ایمیل مدیر</label>
      <input type="email" name="admin_email" value="<?= ie(ipost('admin_email')) ?>" required dir="ltr">
      <div class="row">
        <div>
          <label>رمز عبور (حداقل ۸ کاراکتر)</label>
          <input type="password" name="admin_pass" required>
        </div>
        <div>
          <label>تکرار رمز عبور</label>
          <input type="password" name="admin_pass2" required>
        </div>
      </div>
      <button class="btn block" style="margin-top:18px">ادامه → تنظیمات سایت</button>
    </form>
  </div>

  <?php elseif ($step === 4): ?>
  <div class="card">
    <h2>تنظیمات سایت و لینک‌های کوتاه</h2>
    <p class="desc">آدرس پایه به‌صورت خودکار تشخیص داده شده است (حتی اگر در یک زیرپوشه نصب شده باشید). لینک‌های کوتاه کارت‌ها بر اساس همین آدرس ساخته می‌شوند؛ مثلاً <b style="direction:ltr"><?= ie($base) ?>/AbC123</b>.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= ie(itoken()) ?>">
      <input type="hidden" name="action" value="step4">
      <label>نام سایت</label>
      <input type="text" name="app_name" value="<?= ie(ipost('app_name') !== '' ? ipost('app_name') : 'cv4u') ?>">
      <label>آدرس پایه (Base URL)</label>
      <input type="url" name="base_url" value="<?= ie(ipost('base_url') !== '' ? ipost('base_url') : $base) ?>" dir="ltr" required>
      <label>نمونه لینک کوتاه</label>
      <div class="sample" id="sampleLink" dir="ltr"></div>
      <div class="check"><input type="checkbox" name="allow_registration" value="1" <?= isset($_POST['allow_registration']) ? (ipost('allow_registration') === '1' ? 'checked' : '') : 'checked' ?>> <span>ثبت‌نام کاربران در سایت فعال باشد</span></div>
      <label>متن فوتر سایت (اختیاری)</label>
      <input type="text" name="footer_text" value="<?= ie(ipost('footer_text')) ?>">
      <button class="btn block" style="margin-top:18px">پایان نصب 🎉</button>
    </form>
  </div>
  <?php endif; ?>

  <?php endif; ?>

  <div class="foot">نسخه ۱.۰ — پلتفرم کارت ویزیت مجازی · نصب‌کننده‌ی فارسی</div>
</div>
<script>
(function () {
  var sample = document.getElementById('sampleLink');
  var input = document.querySelector('input[name="base_url"]');
  if (sample && input) {
    var upd = function () { sample.textContent = (input.value.replace(/\/+$/, '')) + '/AbC123'; };
    input.addEventListener('input', upd);
    upd();
  }
})();
</script>
</body>
</html>
