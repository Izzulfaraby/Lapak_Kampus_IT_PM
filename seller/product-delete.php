<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
if (!isPost()) redirect('seller/products.php');
checkCsrfOrFail();
$id = (int)($_POST['id'] ?? 0);
$st = db()->prepare(productSelect() . ' WHERE p.id = ? AND p.store_id = ?'); $st->execute([$id, $store['id']]);
$p = $st->fetch();
if ($p) {
    $used = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id=?'); $used->execute([$id]);
    if ($used->fetchColumn() > 0) {   // sudah pernah dipesan: jangan hapus, cukup nonaktifkan (histori tetap utuh)
        db()->prepare("UPDATE products SET status='inactive' WHERE id=?")->execute([$id]);
        flash('warning', 'Produk sudah pernah dipesan, sehingga dinonaktifkan (tidak dihapus).');
    } else {
        deleteUpload('products', $p['image']);
        db()->prepare('DELETE FROM products WHERE id=? AND store_id=?')->execute([$id, $store['id']]);
        flash('success', 'Produk dihapus.');
    }
}
redirect('seller/products.php');
