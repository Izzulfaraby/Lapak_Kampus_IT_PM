<?php
require_once __DIR__ . '/../config/config.php';
$admin = requireAdmin();
if (isPost()) {
    checkCsrfOrFail();
    $id = (int)$_POST['id']; $to = ($_POST['to'] ?? '') === 'active' ? 'active' : 'inactive';
    if ($id === (int)$admin['id']) flash('danger', 'Anda tidak dapat menonaktifkan akun sendiri.');
    else { db()->prepare("UPDATE users SET status=? WHERE id=? AND role='buyer'")->execute([$to, $id]); adminLog($to === 'active' ? 'Aktifkan user' : 'Nonaktifkan user', 'User #' . $id); flash('success', 'Status pengguna diperbarui.'); }
    redirect('admin/users.php');
}
$users = db()->query("SELECT u.*, (SELECT status FROM stores WHERE user_id=u.id) AS store_status FROM users u ORDER BY u.id")->fetchAll();
$layout = 'admin'; $active = 'users.php'; $pageTitle = 'Manajemen Pengguna';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>ID</th><th>Nama</th><th>Email</th><th>HP</th><th>Peran</th><th>Status</th><th>Terdaftar</th><th></th></tr>
<?php foreach ($users as $u): ?><tr><td><?= $u['id'] ?></td><td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td>
  <td><?= $u['role'] === 'admin' ? 'Admin' : ($u['seller_enabled'] ? 'Pembeli + Penjual' : 'Pembeli') ?></td><td><?= badge($u['status']) ?></td><td class="small"><?= tgl($u['created_at']) ?></td>
  <td><?php if ($u['role'] !== 'admin'): ?><form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="to" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
    <button class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>"><?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
