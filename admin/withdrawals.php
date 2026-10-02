<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
if (isPost()) {
    checkCsrfOrFail();
    [$ok, $msg] = adminWithdrawalAction((int)$_POST['id'], $_POST['action'] ?? '');
    flash($ok ? 'success' : 'danger', $msg);
    redirect('admin/withdrawals.php');
}
$rows = db()->query('SELECT w.*, s.name AS store FROM withdrawals w JOIN stores s ON s.id=w.store_id ORDER BY FIELD(w.status,"pending","approved","paid","rejected"), w.id DESC')->fetchAll();
$layout = 'admin'; $active = 'withdrawals.php'; $pageTitle = 'Persetujuan Penarikan';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>ID</th><th>Seller</th><th>Nominal</th><th>Bank</th><th>No. Rekening</th><th>Nama</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $w): ?><tr><td>#<?= $w['id'] ?></td><td><?= e($w['store']) ?></td><td><?= rupiah($w['amount']) ?></td><td><?= e($w['bank_name']) ?></td><td><?= e($w['account_number']) ?></td><td><?= e($w['account_name']) ?></td><td class="small"><?= tgl($w['created_at']) ?></td><td><?= badge($w['status']) ?></td>
  <td><form method="post" class="row-flex" data-confirm="Yakin memproses penarikan ini?"><?= csrfField() ?><input type="hidden" name="id" value="<?= $w['id'] ?>">
    <?php if ($w['status'] === 'pending'): ?><button class="btn btn-success btn-sm" name="action" value="approve">Approve</button><button class="btn btn-danger btn-sm" name="action" value="reject">Reject</button>
    <?php elseif ($w['status'] === 'approved'): ?><button class="btn btn-sm" name="action" value="paid">Tandai Dibayar</button><?php endif; ?></form></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="empty">Belum ada pengajuan.</td></tr><?php endif; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
