<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/product_validate.php';
$store = requireSeller();
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = []; $product = null;
if (isPost()) {
    checkCsrfOrFail();
    [$d, $errors] = validateProductInput();
    if ($d) {
        [$photo, $ue] = uploadImage($_FILES['photo'] ?? [], 'products');
        if ($ue) $errors[] = $ue;
        else {
            try {
                db()->prepare('INSERT INTO products (store_id,category_id,name,description,price,stock,item_condition,status) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$store['id'], $d['category_id'], $d['name'], $d['description'], $d['price'], $d['stock'], $d['item_condition'], $d['status']]);
                $pid = db()->lastInsertId();
                if ($photo) db()->prepare('INSERT INTO product_images (product_id, filename, is_primary) VALUES (?,?,1)')->execute([$pid, $photo]);
                flash('success', 'Produk ditambahkan.'); redirect('seller/products.php');
            } catch (PDOException $e) { logError('product-add', $e); $errors[] = 'Gagal menyimpan produk (kategori tidak valid?).'; }
        }
    }
}
$layout = 'seller'; $active = 'products.php'; $pageTitle = 'Tambah Produk';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/product_form.php';
include __DIR__ . '/../includes/footer.php';
