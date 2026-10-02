<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
ensureBalance($store['id']);
if (isPost()) {
    checkCsrfOrFail();
    $err = requestWithdrawal($store, (float)($_POST['amount'] ?? 0), trim($_POST['bank'] ?? ''), trim($_POST['number'] ?? ''), trim($_POST['holder'] ?? ''));
    flash($err ? 'danger' : 'success', $err ?: 'Pengajuan penarikan dikirim. Menunggu persetujuan admin.');
    redirect('seller/withdrawals.php');
}
$b = db()->prepare('SELECT available_balance FROM seller_balances WHERE store_id=?'); $b->execute([$store['id']]); $avail = $b->fetchColumn();
$l = db()->prepare('SELECT * FROM withdrawals WHERE store_id=? ORDER BY id DESC'); $l->execute([$store['id']]); $list = $l->fetchAll();
$layout = 'seller'; $active = 'withdrawals.php'; $pageTitle = 'Penarikan Dana';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid-2">
<form method="post" class="card"><?= csrfField() ?><h3>Tarik Dana</h3><p>Saldo tersedia: <strong><?= rupiah($avail) ?></strong></p>
  <label>Nominal</label><input type="number" name="amount" min="1" max="<?= (int)$avail ?>" required>
  <label>Bank</label><input name="bank" required><label>Nomor Rekening</label><input name="number" required pattern="[0-9]{6,20}">
  <label>Nama Pemilik Rekening</label><input name="holder" required><button class="btn mt">Ajukan Penarikan</button></form>
<div class="card table-wrap"><h3>Riwayat</h3><table><tr><th>ID</th><th>Nominal</th><th>Bank</th><th>Status</th><th>Tanggal</th></tr>
<?php foreach ($list as $w): ?><tr><td>#<?= $w['id'] ?></td><td><?= rupiah($w['amount']) ?></td><td class="small"><?= e($w['bank_name']) ?> <?= e($w['account_number']) ?></td><td><?= badge($w['status']) ?></td><td class="small"><?= tgl($w['created_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="5" class="empty">Belum ada penarikan.</td></tr><?php endif; ?></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
