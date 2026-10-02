<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
if (isPost()) {
    checkCsrfOrFail();
    if (($_POST['action'] ?? '') === 'profile') {
        $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
        if (mb_strlen($name) < 3) flash('danger', 'Nama minimal 3 karakter.');
        elseif ($phone !== '' && !preg_match('/^[0-9+\- ]{8,20}$/', $phone)) flash('danger', 'Nomor HP tidak valid.');
        else { db()->prepare('UPDATE users SET name=?, phone=?, address=? WHERE id=?')->execute([$name, $phone, $addr, $user['id']]); flash('success', 'Profil diperbarui.'); }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['old'] ?? '', $user['password'])) flash('danger', 'Password lama salah.');
        elseif (strlen($new) < 8 || $new !== ($_POST['confirm'] ?? '')) flash('danger', 'Password baru minimal 8 karakter dan harus sama dengan konfirmasi.');
        else { db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]); session_regenerate_id(true); flash('success', 'Password diubah.'); }
    }
    redirect('buyer/profile.php');
}
$pageTitle = 'Profil Saya';
include __DIR__ . '/../includes/header.php';
?>
<h1>Profil Saya</h1>
<div class="grid-2">
<form method="post" class="card"><?= csrfField() ?><input type="hidden" name="action" value="profile"><h3>Data Diri</h3>
  <label>Nama</label><input name="name" required value="<?= e($user['name']) ?>">
  <label>Email</label><input value="<?= e($user['email']) ?>" disabled>
  <label>Nomor HP</label><input name="phone" value="<?= e($user['phone']) ?>">
  <label>Alamat Rumah</label><textarea name="address"><?= e($user['address']) ?></textarea>
  <button class="btn mt">Simpan</button></form>
<form method="post" class="card"><?= csrfField() ?><input type="hidden" name="action" value="password"><h3>Ubah Password</h3>
  <label>Password Lama</label><input type="password" name="old" required>
  <label>Password Baru</label><input type="password" name="new" required minlength="8">
  <label>Konfirmasi</label><input type="password" name="confirm" required>
  <button class="btn mt">Ubah Password</button>
  <p class="muted small mt">Data toko dikelola terpisah di menu Pengaturan Toko / Buka Toko.</p></form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
