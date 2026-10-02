<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
$sid = $store['id'];
$q = function ($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); };
ensureBalance($sid);
$bal = db()->prepare('SELECT * FROM seller_balances WHERE store_id=?'); $bal->execute([$sid]); $bal = $bal->fetch();
$totalProducts = $q('SELECT COUNT(*) FROM products WHERE store_id=?', [$sid]);
$newOrders = $q("SELECT COUNT(*) FROM handover_tokens WHERE seller_id=? AND status='active'", [$sid]);
$doneOrders = $q("SELECT COUNT(*) FROM handover_tokens WHERE seller_id=? AND status='used'", [$sid]);
$income = $q("SELECT COALESCE(SUM(amount),0) FROM handover_tokens WHERE seller_id=? AND status='used'", [$sid]);
// Grafik 7 hari terakhir (penjualan yang sudah dibayar)
$days = [];
for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
$st = db()->prepare("SELECT DATE(o.created_at) d, SUM(oi.subtotal) t FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.store_id=? AND o.order_status IN ('ready_for_handover','processing','completed') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(o.created_at)");
$st->execute([$sid]);
foreach ($st->fetchAll() as $r) $days[$r['d']] = (float)$r['t'];
$maxV = max(1, max($days));
$layout = 'seller'; $active = 'dashboard.php'; $pageTitle = 'Dashboard Toko';
include __DIR__ . '/../includes/header.php';
?>
<p class="muted">🏪 <?= e($store['name']) ?></p>
<div class="stats">
  <div class="stat"><small>Total Produk</small><strong><?= (int)$totalProducts ?></strong></div>
  <div class="stat"><small>Pesanan Baru (menunggu serah terima)</small><strong><?= (int)$newOrders ?></strong></div>
  <div class="stat"><small>Pesanan Selesai</small><strong><?= (int)$doneOrders ?></strong></div>
  <div class="stat"><small>Pendapatan</small><strong><?= rupiah($income) ?></strong></div>
  <div class="stat"><small>Saldo Tersedia</small><strong><?= rupiah($bal['available_balance']) ?></strong></div>
  <div class="stat"><small>Dana Ditahan</small><strong><?= rupiah($bal['held_balance']) ?></strong></div>
</div>
<div class="card"><h3>Penjualan 7 Hari Terakhir</h3>
  <div class="bars"><?php foreach ($days as $d => $v): ?><div class="bar" title="<?= rupiah($v) ?>"><i style="height:<?= round($v / $maxV * 100) ?>%"></i><?= date('d/m', strtotime($d)) ?></div><?php endforeach; ?></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
