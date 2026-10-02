<?php
/** Halaman admin */
function requireAdmin() {
    $u = requireLogin();
    if ($u['role'] !== 'admin') { flash('danger', 'Akses ditolak.'); redirect('buyer/dashboard.php'); }
    return $u;
}
