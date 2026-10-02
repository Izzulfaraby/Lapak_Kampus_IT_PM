<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
if (isPost()) {
    checkCsrfOrFail();
    $f = array_map('trim', [$_POST['name'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? '', $_POST['city'] ?? '', $_POST['province'] ?? '']);
    if (in_array('', $f, true)) flash('danger', 'Semua kolom wajib diisi.');
    else {
        [$logo, $err] = uploadImage($_FILES['logo'] ?? [], 'stores');
        if ($err) flash('danger', $err);
        else {
            if ($logo) { deleteUpload('stores', $store['logo']); db()->prepare('UPDATE stores SET logo=? WHERE id=?')->execute([$logo, $store['id']]); }
            db()->prepare('UPDATE stores SET name=?,phone=?,address=?,city=?,province=?,description=? WHERE id=?')->execute([...$f, trim($_POST['description'] ?? ''), $store['id']]);
            flash('success', 'Pengaturan toko disimpan.');
        }
    }
    redirect('seller/store-settings.php');
}
$layout = 'seller'; $active = 'store-settings.php'; $pageTitle = 'Pengaturan Toko';
include __DIR__ . '/../includes/header.php';
?>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:640px"><?= csrfField() ?>
  <label>Nama Toko</label><input name="name" required value="<?= e($store['name']) ?>"><label>Deskripsi</label><textarea name="description"><?= e($store['description']) ?></textarea>
  <label>Nomor HP Toko</label><input name="phone" required value="<?= e($store['phone']) ?>"><label>Alamat Gudang</label><textarea name="address" required><?= e($store['address']) ?></textarea>
  <div class="form-row"><div><label>Kota</label><input name="city" required value="<?= e($store['city']) ?>"></div><div><label>Provinsi</label><input name="province" required value="<?= e($store['province']) ?>"></div></div>
  <label>Logo</label><?php if ($store['logo']): ?><br><img class="thumb" src="<?= e(url('uploads/stores/' . $store['logo'])) ?>" alt=""><?php endif; ?>
  <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"><button class="btn mt">Simpan</button></form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
