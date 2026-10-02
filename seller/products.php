<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
$st = db()->prepare(productSelect() . ' WHERE p.store_id = ? ORDER BY p.id DESC'); $st->execute([$store['id']]);
$products = $st->fetchAll();
$layout = 'seller'; $active = 'products.php'; $pageTitle = 'Produk Saya';
include __DIR__ . '/../includes/header.php';
?>
<p><a class="btn" href="product-add.php">+ Tambah Produk</a></p>
<div class="card table-wrap"><table><tr><th></th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Kondisi</th><th>Status</th><th></th></tr>
<?php foreach ($products as $p): ?><tr>
  <td><?= productImg($p, 'thumb') ?></td><td><?= e($p['name']) ?></td><td><?= e($p['category_name']) ?></td><td><?= rupiah($p['price']) ?></td><td><?= (int)$p['stock'] ?></td><td><?= e($p['item_condition']) ?></td><td><?= badge($p['status']) ?></td>
  <td class="row-flex"><a class="btn btn-outline btn-sm" href="product-edit.php?id=<?= $p['id'] ?>">Edit</a>
    <form method="post" action="product-delete.php" data-confirm="Hapus produk ini?"><?= csrfField() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></td></tr>
<?php endforeach; if (!$products) echo '<tr><td colspan="8" class="empty">Belum ada produk.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
