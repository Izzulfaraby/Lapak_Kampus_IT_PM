<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
$sql = "SELECT ht.*, o.order_number FROM handover_tokens ht JOIN orders o ON o.id=ht.order_id WHERE ht.seller_id=? AND ht.status='used'"; $p = [$store['id']];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(ht.scanned_at) >= ?'; $p[] = $from; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $sql .= ' AND DATE(ht.scanned_at) <= ?'; $p[] = $to; }
$st = db()->prepare($sql . ' ORDER BY ht.scanned_at DESC'); $st->execute($p);
$rows = $st->fetchAll();
$sum = array_sum(array_column($rows, 'amount'));
$layout = 'seller'; $active = 'sales.php'; $pageTitle = 'Riwayat Penjualan';
include __DIR__ . '/../includes/header.php';
?>
<form class="card row-flex" method="get"><div><label>Dari</label><input type="date" name="from" value="<?= e($from) ?>"></div><div><label>Sampai</label><input type="date" name="to" value="<?= e($to) ?>"></div><button class="btn" style="margin-top:28px">Filter</button></form>
<div class="card table-wrap"><table><tr><th>Order</th><th>Jumlah</th><th>Tanggal Serah Terima</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['order_number']) ?></td><td><?= rupiah($r['amount']) ?></td><td><?= tgl($r['scanned_at']) ?></td><td><?= badge('completed') ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" class="empty">Belum ada penjualan selesai.</td></tr><?php endif; ?></table>
<p class="right"><strong>Total: <?= rupiah($sum) ?></strong></p></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
