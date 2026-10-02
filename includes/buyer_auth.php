<?php
/** Halaman pembeli: harus login dan bukan admin */
function requireBuyer() {
    $u = requireLogin();
    if ($u['role'] === 'admin') redirect('admin/dashboard.php');
    return $u;
}
