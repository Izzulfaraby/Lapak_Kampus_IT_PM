<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
if (isPost()) {
    checkCsrfOrFail();
    $id = (int)$_POST['id']; $act = $_POST['action'] ?? '';
    $map = ['approve' => ['active', 'Approve toko'], 'reject' => ['rejected', 'Reject toko'], 'suspend' => ['suspended', 'Suspend toko']];
    $st = db()->prepare('SELECT * FROM stores WHERE id=?'); $st->execute([$id]); $s = $st->fetch();
    if ($s && isset($map[$act])) {
        try {
            db()->beginTransaction();
            db()->prepare('UPDATE stores SET status=? WHERE id=?')->execute([$map[$act][0], $id]);
            // Approve => user boleh beralih ke toko. Selain aktif => seller_enabled dimatikan
            db()->prepare('UPDATE users SET seller_enabled=? WHERE id=?')->execute([$act === 'approve' ? 1 : 0, $s['user_id']]);
            if ($act === 'approve') ensureBalance($id);
            adminLog($map[$act][1], 'Toko "' . $s['name'] . '" (#' . $id . ')');
            notify($s['user_id'], 'Status toko: ' . $map[$act][0], 'Toko ' . $s['name'] . ' kini berstatus ' . $map[$act][0] . '.');
            db()->commit(); flash('success', 'Status toko diperbarui.');
        } catch (Throwable $e) { if (db()->inTransaction()) db()->rollBack(); logError('admin stores', $e); flash('danger', 'Gagal memproses.'); }
    }
    redirect('admin/stores.php');
}
$rows = db()->query('SELECT s.*, u.name AS owner, u.email FROM stores s JOIN users u ON u.id=s.user_id ORDER BY FIELD(s.status,"pending","active","suspended","rejected"), s.id DESC')->fetchAll();
$layout = 'admin'; $active = 'stores.php'; $pageTitle = 'Manajemen Toko / Penjual';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>ID</th><th>Toko</th><th>Pemilik</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $s): ?><tr><td><?= $s['id'] ?></td><td><strong><?= e($s['name']) ?></strong><div class="muted small"><?= e(mb_strimwidth($s['description'], 0, 60, '…')) ?></div></td>
  <td><?= e($s['owner']) ?><div class="muted small"><?= e($s['email']) ?></div></td><td class="small"><?= e($s['city']) ?>, <?= e($s['province']) ?></td><td><?= badge($s['status']) ?></td>
  <td><form method="post" class="row-flex"><?= csrfField() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
    <?php if ($s['status'] !== 'active'): ?><button class="btn btn-success btn-sm" name="action" value="approve">Approve</button><?php endif; ?>
    <?php if ($s['status'] === 'pending'): ?><button class="btn btn-danger btn-sm" name="action" value="reject">Reject</button><?php endif; ?>
    <?php if ($s['status'] === 'active'): ?><button class="btn btn-warning btn-sm" name="action" value="suspend">Suspend</button><?php endif; ?></form></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
