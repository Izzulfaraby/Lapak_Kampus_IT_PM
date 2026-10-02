<?php
/** Autentikasi dasar */
function isLoggedIn() { return !empty($_SESSION['user_id']); }

/** Data user yang sedang login (null jika belum login / nonaktif) */
function currentUser() {
    static $user = false;
    if ($user !== false) return $user;
    if (!isLoggedIn()) return $user = null;
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch() ?: null;
    if (!$user || $user['status'] !== 'active') { unset($_SESSION['user_id']); $user = null; }
    return $user;
}
function requireLogin() {
    $u = currentUser();
    if (!$u) { flash('warning', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
    return $u;
}
/** Ambil toko milik user (atau null) */
function storeOf($userId) {
    $st = db()->prepare('SELECT * FROM stores WHERE user_id = ?');
    $st->execute([$userId]);
    return $st->fetch() ?: null;
}
