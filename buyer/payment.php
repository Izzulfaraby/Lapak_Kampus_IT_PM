<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$oid = (int)($_GET['order'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?'); $st->execute([$oid, $user['id']]);
$order = $st->fetch();
if (!$order) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('buyer/orders.php'); }
if (isPost()) {
    checkCsrfOrFail();
    $method = $_POST['method'] ?? '';
    if (!in_array($method, ['Transfer Bank', 'E-Wallet', 'Virtual Account'], true)) { flash('danger', 'Pilih metode pembayaran.'); redirect('buyer/payment.php?order=' . $oid); }
    if (($_POST['do'] ?? '') === 'cancel') { $err = cancelOrder($oid, $user['id']); flash($err ? 'danger' : 'success', $err ?: 'Pesanan dibatalkan.'); redirect('buyer/orders.php'); }
    $err = simulatePayment($oid, $user['id'], $method);
    if ($err) { flash('danger', $err); redirect('buyer/order-detail.php?id=' . $oid); }
    flash('success', 'Pembayaran berhasil! Dana ditahan sampai serah terima. Tunjukkan QR ke penjual.');
    redirect('buyer/handover.php?order=' . $oid);
}
$pageTitle = 'Pembayaran';
include __DIR__ . '/../includes/header.php';
?>
<h1>Pembayaran (Simulasi)</h1>
<?php if ($order['order_status'] !== 'pending_payment'): ?>
  <div class="card">Pesanan ini berstatus <?= badge($order['order_status']) ?>. <a href="order-detail.php?id=<?= $oid ?>">Lihat detail</a></div>
<?php else: ?>
<div class="card" style="max-width:520px">
  <p>Order Number: <strong><?= e($order['order_number']) ?></strong></p>
  <p>Total: <span class="price-lg"><?= rupiah($order['total_amount']) ?></span></p>
  <form method="post"><?= csrfField() ?>
    <label>Metode Pembayaran</label>
    <?php foreach (['Transfer Bank', 'E-Wallet', 'Virtual Account'] as $i => $m): ?>
      <label style="font-weight:400"><input type="radio" name="method" value="<?= $m ?>" style="width:auto" <?= $i === 0 ? 'checked' : '' ?>> <?= $m ?></label><?php endforeach; ?>
    <p class="muted small">Ini hanya simulasi, tidak ada uang sungguhan.</p>
    <button class="btn btn-block">Simulasikan Pembayaran</button>
    <button class="btn btn-outline btn-block mt" name="do" value="cancel" formnovalidate onclick="return confirm('Batalkan pesanan ini?')">Batalkan Pesanan</button>
  </form>
</div>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
