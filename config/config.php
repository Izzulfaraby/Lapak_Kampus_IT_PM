<?php
/** Konfigurasi umum aplikasi KampusMart */
define('APP_NAME', 'KampusMart');
define('APP_ENV', 'development');      // ganti ke 'production' saat online
define('ROOT_PATH', dirname(__DIR__));
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB
define('HANDOVER_EXPIRE_DAYS', 7);

// BASE_URL dideteksi otomatis, mis. /kampusmart
$docRoot = str_replace('\\', '/', rtrim((string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
$appRoot = str_replace('\\', '/', (string)realpath(ROOT_PATH));
define('BASE_URL', ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '/kampusmart');

date_default_timezone_set('Asia/Makassar');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/logs/app.log');
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/buyer_auth.php';
require_once ROOT_PATH . '/includes/seller_auth.php';
require_once ROOT_PATH . '/includes/admin_auth.php';
require_once ROOT_PATH . '/includes/services.php';
