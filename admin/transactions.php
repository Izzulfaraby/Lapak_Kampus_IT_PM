<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
$rows = db()->query("SELECT o.order_number, o.payment_status, o.order_status, o.created_at, u.name AS buyer, s.name AS seller, oi.product_name, oi.quantity, oi.subtotal
  FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN users u ON u.id=o.user_id JOIN stores s ON s.id=oi.store_id ORDER BY o.id DESC, oi.id LIMIT 300")->fetchAll();
$layout = 'admin'; $active = 'transactions.php'; $pageTitle = 'Pantau Transaksi';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>Order ID</th><th>Pembeli</th><th>Penjual</th><th>Produk</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['order_number']) ?></td><td><?= e($r['buyer']) ?></td><td><?= e($r['seller']) ?></td><td><?= e($r['product_name']) ?> ×<?= (int)$r['quantity'] ?></td><td><?= rupiah($r['subtotal']) ?></td><td><?= badge($r['payment_status']) ?></td><td><?= badge($r['order_status']) ?></td><td class="small"><?= tgl($r['created_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="8" class="empty">Belum ada transaksi.</td></tr><?php endif; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
