<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$oid = (int)($_GET['order'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?'); $st->execute([$oid, $user['id']]);
$order = $st->fetch();
if (!$order) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('buyer/orders.php'); }
// Token hanya ditampilkan kepada pembeli pemilik order
$t = db()->prepare('SELECT ht.*, s.name AS store_name FROM handover_tokens ht JOIN stores s ON s.id = ht.seller_id WHERE ht.order_id = ?');
$t->execute([$oid]);
$tokens = $t->fetchAll();
$pageTitle = 'QR Serah Terima';
include __DIR__ . '/../includes/header.php';
?>
<h1>QR Serah Terima</h1>
<p class="muted">Order <?= e($order['order_number']) ?>. Tunjukkan QR ke penjual saat barang diserahkan. Jangan kirim QR ke orang lain sebelum menerima barang.</p>
<?php if (!$tokens): ?><div class="card empty">QR tersedia setelah pembayaran berhasil. <a href="payment.php?order=<?= $oid ?>">Bayar sekarang</a></div><?php endif; ?>
<div class="grid-2">
<?php foreach ($tokens as $tk): $payload = 'KAMPUSMART-HANDOVER-' . $tk['token']; ?>
  <div class="card qr-box">
    <h3>🏪 <?= e($tk['store_name']) ?></h3><?= badge($tk['status']) ?>
    <?php if ($tk['status'] === 'active'): ?>
      <p><img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?= e(rawurlencode($payload)) ?>" width="220" height="220" alt="QR Serah Terima" onerror="this.style.display='none'"></p>
      <p class="small muted">Jika gambar QR tidak muncul (tanpa internet), berikan kode di bawah kepada penjual untuk diinput manual:</p>
      <div class="token"><?= e($payload) ?></div>
      <p class="small muted">Berlaku sampai <?= tgl($tk['expires_at']) ?></p>
    <?php else: ?><p class="muted">Serah terima selesai pada <?= tgl($tk['scanned_at']) ?>.</p><?php endif; ?>
  </div>
<?php endforeach; ?></div>
<a class="btn btn-outline" href="order-detail.php?id=<?= $oid ?>">Kembali ke Detail Pesanan</a>
<?php include __DIR__ . '/../includes/footer.php'; ?>
