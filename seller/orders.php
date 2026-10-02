<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
// Seller hanya melihat order yang memuat produk tokonya
$st = db()->prepare("SELECT o.id, o.order_number, o.payment_status, o.order_status, o.created_at, u.name AS buyer,
  GROUP_CONCAT(CONCAT(oi.product_name,' x',oi.quantity) SEPARATOR ', ') AS items, SUM(oi.quantity) qty, SUM(oi.subtotal) total,
  (SELECT status FROM handover_tokens ht WHERE ht.order_id=o.id AND ht.seller_id=oi.store_id) AS ht_status
  FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN users u ON u.id=o.user_id WHERE oi.store_id=? GROUP BY o.id, oi.store_id ORDER BY o.id DESC");
$st->execute([$store['id']]);
$rows = $st->fetchAll();
$layout = 'seller'; $active = 'orders.php'; $pageTitle = 'Pesanan Masuk';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>Order</th><th>Pembeli</th><th>Produk</th><th>Jumlah</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['order_number']) ?></td><td><?= e($r['buyer']) ?></td><td class="small"><?= e($r['items']) ?></td><td><?= (int)$r['qty'] ?></td><td><?= rupiah($r['total']) ?></td>
  <td><?= badge($r['payment_status']) ?></td><td><?= $r['ht_status'] === 'used' ? badge('completed') : badge($r['order_status']) ?></td><td class="small"><?= tgl($r['created_at']) ?></td>
  <td><a class="btn btn-outline btn-sm" href="order-detail.php?id=<?= $r['id'] ?>">Detail</a></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="9" class="empty">Belum ada pesanan.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
