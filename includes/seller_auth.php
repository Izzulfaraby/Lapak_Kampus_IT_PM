<?php
/** Halaman penjual: user login + seller_enabled + toko aktif. Mengembalikan data toko. */
function requireSeller() {
    $u = requireBuyer();
    $store = storeOf($u['id']);
    if (!$u['seller_enabled'] || !$store || $store['status'] !== 'active') {
        flash('warning', 'Anda belum memiliki toko yang aktif. Silakan buka toko terlebih dahulu.');
        redirect('buyer/open-store.php');
    }
    return $store;
}
