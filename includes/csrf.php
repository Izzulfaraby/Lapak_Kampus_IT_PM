<?php
/** Proteksi CSRF sederhana */
function generateCsrfToken() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}
function verifyCsrfToken($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
/** Panggil di awal blok POST: jika token salah, kembali ke halaman yang sama */
function checkCsrfOrFail() {
    if (!verifyCsrfToken()) {
        flash('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
