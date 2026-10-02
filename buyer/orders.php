<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$tab = $_GET['tab'] ?? 'all';
$groups = ['all' => null, 'unpaid' => ['pending_payment'], 'active' => ['ready_for_handover', 'processing', 'paid'], 'done' => ['completed'], 'cancelled' => ['cancelled', 'refunded']];
if (!isset($groups[$tab])) $tab = 'all';
$sql = 'SELECT o.*, (SELECT GROUP_CONCAT(product_name SEPARATOR ", ") FROM order_items WHERE order_id = o.id) AS names FROM orders o WHERE o.user_id = ?';
$params = [$user['id']];
if ($groups[$tab]) { $sql .= ' AND o.order_status IN (' . implode(',', array_fill(0, count($groups[$tab]), '?')) . ')'; $params = array_merge($params, $groups[$tab]); }
$st = db()->prepare($sql . ' ORDER BY o.id DESC');
$st->execute($params);
$orders = $st->fetchAll();
$pageTitle = 'Pesanan Saya';
include __DIR__ . '/../includes/header.php';
?>
<h1>Pesanan Saya</h1>
<p><?php foreach (['all' => 'Semua', 'unpaid' => 'Belum Dibayar', 'active' => 'Berlangsung', 'done' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $k => $l): ?>
  <a class="btn btn-sm <?= $tab === $k ? '' : 'btn-outline' ?>" href="?tab=<?= $k ?>"><?= $l ?></a> <?php endforeach; ?></p>
<div class="card table-wrap"><table>
  <tr><th>No. Order</th><th>Produk</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th><th></th></tr>
  <?php foreach ($orders as $o): ?><tr>
    <td><?= e($o['order_number']) ?></td><td class="small"><?= e(mb_strimwidth($o['names'], 0, 50, '…')) ?></td><td><?= rupiah($o['total_amount']) ?></td>
    <td><?= badge($o['payment_status']) ?></td><td><?= badge($o['order_status']) ?></td><td class="small"><?= tgl($o['created_at']) ?></td>
    <td><a class="btn btn-sm btn-outline" href="order-detail.php?id=<?= $o['id'] ?>">Detail</a></td></tr><?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="7" class="empty">Belum ada pesanan.</td></tr><?php endif; ?>
</table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
