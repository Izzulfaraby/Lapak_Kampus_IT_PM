<?php
/**
 * Konfigurasi database - ubah nilai di bawah sesuai komputer Anda.
 * Default XAMPP: user root, password kosong.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'kampusmart');
define('DB_USER', 'root');
define('DB_PASS', '');

/** Mengembalikan satu koneksi PDO (singleton) */
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
