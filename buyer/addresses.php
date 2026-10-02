<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
if (isPost()) {
    checkCsrfOrFail();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $f = array_map('trim', [$_POST['label'] ?? '', $_POST['recipient'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? '', $_POST['city'] ?? '', $_POST['province'] ?? '']);
        if (in_array('', $f, true)) flash('danger', 'Semua kolom alamat wajib diisi.');
        else {
            $has = db()->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id=?'); $has->execute([$user['id']]);
            $def = $has->fetchColumn() ? 0 : 1;
            db()->prepare('INSERT INTO user_addresses (user_id,label,recipient,phone,address,city,province,is_default) VALUES (?,?,?,?,?,?,?,?)')->execute(array_merge([$user['id']], $f, [$def]));
            flash('success', 'Alamat ditambahkan.');
        }
    } elseif ($act === 'delete') {
        db()->prepare('DELETE FROM user_addresses WHERE id=? AND user_id=?')->execute([(int)$_POST['id'], $user['id']]); flash('success', 'Alamat dihapus.');
    } elseif ($act === 'default') {
        db()->prepare('UPDATE user_addresses SET is_default = (id = ?) WHERE user_id = ?')->execute([(int)$_POST['id'], $user['id']]); flash('success', 'Alamat utama diubah.');
    }
    redirect('buyer/addresses.php');
}
$st = db()->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id'); $st->execute([$user['id']]);
$list = $st->fetchAll();
$pageTitle = 'Buku Alamat';
include __DIR__ . '/../includes/header.php';
?>
<h1>Buku Alamat</h1>
<div class="grid-2">
  <div><?php foreach ($list as $a): ?><div class="card"><strong><?= e($a['label']) ?></strong> <?= $a['is_default'] ? '<span class="badge badge-success">Utama</span>' : '' ?>
    <p class="small"><?= e($a['recipient']) ?> (<?= e($a['phone']) ?>)<br><?= e($a['address']) ?>, <?= e($a['city']) ?>, <?= e($a['province']) ?></p>
    <div class="row-flex">
      <?php if (!$a['is_default']): ?><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="default"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-outline btn-sm">Jadikan Utama</button></form><?php endif; ?>
      <form method="post" data-confirm="Hapus alamat ini?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></div></div>
  <?php endforeach; if (!$list) echo '<div class="card empty">Belum ada alamat.</div>'; ?></div>
  <form method="post" class="card"><?= csrfField() ?><input type="hidden" name="action" value="add"><h3>Tambah Alamat</h3>
    <label>Label (Kos/Rumah)</label><input name="label" required><label>Nama Penerima</label><input name="recipient" required value="<?= e($user['name']) ?>">
    <label>Nomor HP</label><input name="phone" required value="<?= e($user['phone']) ?>"><label>Alamat Lengkap</label><textarea name="address" required></textarea>
    <div class="form-row"><div><label>Kota</label><input name="city" required></div><div><label>Provinsi</label><input name="province" required></div></div>
    <button class="btn mt">Simpan Alamat</button></form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
