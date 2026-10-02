<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT o.*, a.recipient, a.phone AS a_phone, a.address, a.city, a.province FROM orders o LEFT JOIN user_addresses a ON a.id = o.address_id WHERE o.id = ? AND o.user_id = ?');
$st->execute([$id, $user['id']]);
$o = $st->fetch();
if (!$o) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('buyer/orders.php'); }
$it = db()->prepare('SELECT oi.*, s.name AS store_name, ht.status AS ht_status FROM order_items oi JOIN stores s ON s.id = oi.store_id LEFT JOIN handover_tokens ht ON ht.order_id = oi.order_id AND ht.seller_id = oi.store_id WHERE oi.order_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();
$pageTitle = 'Detail Pesanan';
include __DIR__ . '/../includes/header.php';
?>
<h1>Pesanan <?= e($o['order_number']) ?></h1>
<div class="grid-2">
  <div class="card"><p>Status: <?= badge($o['order_status']) ?> Pembayaran: <?= badge($o['payment_status']) ?></p><p class="muted small">Dibuat: <?= tgl($o['created_at']) ?></p><p>Total: <strong><?= rupiah($o['total_amount']) ?></strong></p></div>
  <div class="card"><strong>Alamat</strong><p class="small"><?= e($o['recipient']) ?> (<?= e($o['a_phone']) ?>)<br><?= e($o['address']) ?>, <?= e($o['city']) ?>, <?= e($o['province']) ?></p></div>
</div>
<div class="card table-wrap"><table><tr><th>Produk</th><th>Toko</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th>Serah Terima</th></tr>
<?php foreach ($items as $i): ?><tr><td><?= e($i['product_name']) ?></td><td><?= e($i['store_name']) ?></td><td><?= rupiah($i['price']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= rupiah($i['subtotal']) ?></td><td><?= $i['ht_status'] ? badge($i['ht_status']) : '-' ?></td></tr><?php endforeach; ?></table></div>
<p>
<?php if ($o['order_status'] === 'pending_payment'): ?><a class="btn" href="payment.php?order=<?= $id ?>">Bayar Sekarang</a>
<?php elseif (in_array($o['order_status'], ['ready_for_handover', 'processing', 'completed'], true)): ?><a class="btn" href="handover.php?order=<?= $id ?>">QR Serah Terima</a><?php endif; ?>
<a class="btn btn-outline" href="orders.php">Kembali</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
