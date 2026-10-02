<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$store = storeOf($user['id']);
if (isPost() && !$store) {
    checkCsrfOrFail();
    $f = array_map('trim', [$_POST['name'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? '', $_POST['city'] ?? '', $_POST['province'] ?? '']);
    $desc = trim($_POST['description'] ?? '');
    if (in_array('', $f, true)) flash('danger', 'Semua kolom bertanda wajib harus diisi.');
    elseif (!preg_match('/^[0-9+\- ]{8,20}$/', $f[1])) flash('danger', 'Nomor HP toko tidak valid.');
    else {
        [$logo, $err] = uploadImage($_FILES['logo'] ?? [], 'stores');
        if ($err) flash('danger', $err);
        else {
            db()->prepare('INSERT INTO stores (user_id,name,description,phone,address,city,province,logo,status) VALUES (?,?,?,?,?,?,?,?,"pending")')
                ->execute([$user['id'], $f[0], $desc, $f[1], $f[2], $f[3], $f[4], $logo]);
            flash('success', 'Pengajuan toko dikirim. Menunggu persetujuan admin.');
        }
    }
    redirect('buyer/open-store.php');
}
$pageTitle = 'Buka Toko';
include __DIR__ . '/../includes/header.php';
?>
<h1>Buka Toko</h1>
<?php if ($store): ?>
  <div class="card"><h3><?= e($store['name']) ?> <?= badge($store['status']) ?></h3>
    <?php if ($store['status'] === 'pending'): ?><p>Pengajuan Anda sedang ditinjau admin.</p>
    <?php elseif ($store['status'] === 'active'): ?><p>Toko aktif! <a class="btn" href="<?= e(url('seller/dashboard.php')) ?>">Beralih ke Toko</a></p>
    <?php else: ?><p>Toko Anda berstatus <?= e($store['status']) ?>. Hubungi admin untuk informasi.</p><?php endif; ?></div>
<?php else: ?>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:640px"><?= csrfField() ?>
  <p class="muted">Jadikan akun Anda juga sebagai penjual. Setelah disetujui admin, Anda dapat beralih ke mode toko tanpa login ulang.</p>
  <label>Nama Toko *</label><input name="name" required maxlength="100"><label>Deskripsi</label><textarea name="description"></textarea>
  <label>Nomor HP Toko *</label><input name="phone" required><label>Alamat Gudang *</label><textarea name="address" required></textarea>
  <div class="form-row"><div><label>Kota *</label><input name="city" required></div><div><label>Provinsi *</label><input name="province" required></div></div>
  <label>Logo (JPG/PNG/WEBP, maks 2MB)</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-preview="logoPrev">
  <img id="logoPrev" class="thumb" style="display:none;margin-top:8px" alt="">
  <button class="btn mt">Ajukan Toko</button>
</form>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
