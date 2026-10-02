<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
ensureBalance($store['id']);
$b = db()->prepare('SELECT * FROM seller_balances WHERE store_id=?'); $b->execute([$store['id']]); $bal = $b->fetch();
$s = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM balance_transactions WHERE store_id=? AND type='release'"); $s->execute([$store['id']]); $totalSales = $s->fetchColumn();
$w = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE store_id=? AND status IN ('pending','approved','paid')"); $w->execute([$store['id']]); $totalWd = $w->fetchColumn();
$l = db()->prepare('SELECT * FROM balance_transactions WHERE store_id=? ORDER BY id DESC LIMIT 50'); $l->execute([$store['id']]); $log = $l->fetchAll();
$layout = 'seller'; $active = 'balance.php'; $pageTitle = 'Saldo & Keuangan';
include __DIR__ . '/../includes/header.php';
?>
<div class="stats">
  <div class="stat"><small>Saldo Tersedia</small><strong><?= rupiah($bal['available_balance']) ?></strong></div>
  <div class="stat"><small>Dana Ditahan</small><strong><?= rupiah($bal['held_balance']) ?></strong></div>
  <div class="stat"><small>Total Penjualan</small><strong><?= rupiah($totalSales) ?></strong></div>
  <div class="stat"><small>Total Penarikan</small><strong><?= rupiah($totalWd) ?></strong></div>
</div>
<p><a class="btn" href="withdrawals.php">Tarik Dana</a></p>
<div class="card table-wrap"><h3>Riwayat Mutasi Saldo</h3><table><tr><th>Waktu</th><th>Tipe</th><th>Nominal</th><th>Sebelum</th><th>Sesudah</th><th>Keterangan</th></tr>
<?php foreach ($log as $r): ?><tr><td class="small"><?= tgl($r['created_at']) ?></td><td><?= e($r['type']) ?></td><td><?= rupiah($r['amount']) ?></td><td><?= rupiah($r['balance_before']) ?></td><td><?= rupiah($r['balance_after']) ?></td><td class="small"><?= e($r['description']) ?></td></tr><?php endforeach; ?>
<?php if (!$log): ?><tr><td colspan="6" class="empty">Belum ada mutasi.</td></tr><?php endif; ?></table>
<p class="muted small">Catatan: pada baris hold, saldo yang ditampilkan adalah saldo ditahan; pada release/withdrawal/refund adalah saldo tersedia.</p></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
