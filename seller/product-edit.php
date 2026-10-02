<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/product_validate.php';
$store = requireSeller();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(productSelect() . ' WHERE p.id = ? AND p.store_id = ?'); $st->execute([$id, $store['id']]);   // hanya milik toko sendiri
$product = $st->fetch();
if (!$product) { flash('danger', 'Produk tidak ditemukan.'); redirect('seller/products.php'); }
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];
if (isPost()) {
    checkCsrfOrFail();
    [$d, $errors] = validateProductInput();
    if ($d) {
        [$photo, $ue] = uploadImage($_FILES['photo'] ?? [], 'products');
        if ($ue) $errors[] = $ue;
        else {
            db()->prepare('UPDATE products SET category_id=?,name=?,description=?,price=?,stock=?,item_condition=?,status=? WHERE id=? AND store_id=?')
                ->execute([$d['category_id'], $d['name'], $d['description'], $d['price'], $d['stock'], $d['item_condition'], $d['status'], $id, $store['id']]);
            if ($photo) {
                deleteUpload('products', $product['image']);
                db()->prepare('DELETE FROM product_images WHERE product_id=?')->execute([$id]);
                db()->prepare('INSERT INTO product_images (product_id, filename, is_primary) VALUES (?,?,1)')->execute([$id, $photo]);
            }
            flash('success', 'Produk diperbarui.'); redirect('seller/products.php');
        }
    }
}
$layout = 'seller'; $active = 'products.php'; $pageTitle = 'Edit Produk';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/product_form.php';
include __DIR__ . '/../includes/footer.php';
