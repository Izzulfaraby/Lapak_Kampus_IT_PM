<?php
/** Form produk bersama untuk product-add.php & product-edit.php. Variabel: $product (array|null), $cats, $errors */
$v = fn($k, $d = '') => e($_POST[$k] ?? ($product[$k] ?? $d));
?>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:680px">
  <?= csrfField() ?>
  <?php foreach ($errors as $er): ?><p class="error-text"><?= e($er) ?></p><?php endforeach; ?>
  <label>Nama Produk *</label><input name="name" required maxlength="150" value="<?= $v('name') ?>">
  <label>Kategori *</label><select name="category_id" required><option value="">Pilih kategori</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? ($product['category_id'] ?? '')) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <div class="form-row"><div><label>Harga (Rp) *</label><input type="number" name="price" min="1" step="1" required value="<?= $v('price') ?>"></div>
  <div><label>Stok *</label><input type="number" name="stock" min="0" required value="<?= $v('stock', '1') ?>"></div></div>
  <div class="form-row"><div><label>Kondisi *</label><select name="item_condition">
    <?php foreach (['Baru', 'Bekas'] as $k): ?><option <?= ($_POST['item_condition'] ?? ($product['item_condition'] ?? 'Bekas')) === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select></div>
  <div><label>Status</label><select name="status"><?php foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_POST['status'] ?? ($product['status'] ?? 'active')) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div></div>
  <label>Deskripsi</label><textarea name="description"><?= $v('description') ?></textarea>
  <label>Foto (JPG/PNG/WEBP, maks 2MB)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="prev">
  <img id="prev" class="thumb" style="display:none;width:120px;height:120px;margin-top:8px" alt="">
  <?php if (!empty($product['image'])): ?><p class="muted small">Foto saat ini:</p><img class="thumb" style="width:120px;height:120px" src="<?= e(url('uploads/products/' . $product['image'])) ?>" alt=""><?php endif; ?>
  <div class="mt"><button class="btn">Simpan</button> <a class="btn btn-outline" href="products.php">Batal</a></div>
</form>
