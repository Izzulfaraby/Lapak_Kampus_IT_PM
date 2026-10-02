<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT o.*, u.name AS buyer, a.address, a.city FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN user_addresses a ON a.id=o.address_id WHERE o.id=? AND EXISTS (SELECT 1 FROM order_items x WHERE x.order_id=o.id AND x.store_id=?)');
$st->execute([$id, $store['id']]);
$o = $st->fetch();
if (!$o) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('seller/orders.php'); }
$it = db()->prepare('SELECT * FROM order_items WHERE order_id=? AND store_id=?'); $it->execute([$id, $store['id']]); $items = $it->fetchAll();
$ht = db()->prepare('SELECT status, amount, scanned_at FROM handover_tokens WHERE order_id=? AND seller_id=?'); $ht->execute([$id, $store['id']]); $ht = $ht->fetch();
$layout = 'seller'; $active = 'orders.php'; $pageTitle = 'Detail Pesanan';
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h3><?= e($o['order_number']) ?></h3><p>Pembeli: <strong><?= e($o['buyer']) ?></strong></p>
  <p>Pembayaran: <?= badge($o['payment_status']) ?> Status order: <?= badge($o['order_status']) ?></p>
  <?php if ($ht): ?><p>Serah terima: <?= badge($ht['status']) ?> — bagian toko Anda: <strong><?= rupiah($ht['amount']) ?></strong> <?= $ht['scanned_at'] ? '(' . tgl($ht['scanned_at']) . ')' : '' ?></p>
    <?php if ($ht['status'] === 'active'): ?><p class="muted small">Serahkan barang ke pembeli lalu <a href="scan-handover.php">scan QR</a>. Dana baru dilepas setelah QR valid.</p><?php endif; endif; ?></div>
<div class="card table-wrap"><table><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
<?php foreach ($items as $i): ?><tr><td><?= e($i['product_name']) ?></td><td><?= rupiah($i['price']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= rupiah($i['subtotal']) ?></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
