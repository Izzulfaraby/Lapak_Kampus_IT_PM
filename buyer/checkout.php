<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$now = ($_GET['mode'] ?? '') === 'now' && !empty($_SESSION['buynow']);
// Sumber item: "Beli Sekarang" (satu produk) atau seluruh keranjang (satu user = satu cart)
if ($now) {
    $bn = $_SESSION['buynow'];
    $st = db()->prepare(str_replace('SELECT p.*,', 'SELECT ? AS qty, p.*,', productSelect()) . ' WHERE p.id = ?');
    $st->execute([$bn['qty'], $bn['product_id']]);
    $items = $st->fetchAll();
} else {
    $items = cartItems($user['id']);
}
$addrs = db()->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id'); $addrs->execute([$user['id']]);
$addresses = $addrs->fetchAll();

if (isPost()) {
    checkCsrfOrFail();
    $lines = [];
    foreach ($items as $i) $lines[] = ['product_id' => (int)$i['id'], 'qty' => (int)$i['qty']];
    [$orderId, $err] = createOrder($user['id'], (int)($_POST['address_id'] ?? 0), $lines);   // validasi self-purchase + stok ada di sini
    if ($err) { flash('danger', $err); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
    if ($now) unset($_SESSION['buynow']);
    else db()->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([getCartId($user['id'])]);
    flash('success', 'Pesanan dibuat. Silakan selesaikan pembayaran.');
    redirect('buyer/payment.php?order=' . $orderId);
}
$total = 0; foreach ($items as $i) $total += $i['price'] * $i['qty'];
$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>
<h1>Checkout</h1>
<?php if (!$items): ?><div class="card empty">Tidak ada produk untuk di-checkout. <a href="products.php">Belanja dulu</a></div><?php else: ?>
<form method="post" class="grid-2" style="grid-template-columns:2fr 1fr;align-items:start">
  <?= csrfField() ?>
  <div>
    <div class="card"><h3>Alamat Pembeli</h3>
      <?php if (!$addresses): ?><p>Belum ada alamat. <a href="addresses.php">Tambah alamat</a> dulu.</p><?php endif; ?>
      <?php foreach ($addresses as $a): ?>
        <label style="font-weight:400;display:flex;gap:10px;border:1px solid var(--border);border-radius:10px;padding:10px;margin-bottom:8px">
          <input type="radio" name="address_id" value="<?= $a['id'] ?>" style="width:auto" <?= $a['is_default'] ? 'checked' : '' ?> required>
          <span><strong><?= e($a['label']) ?></strong> — <?= e($a['recipient']) ?> (<?= e($a['phone']) ?>)<br><span class="muted small"><?= e($a['address']) ?>, <?= e($a['city']) ?>, <?= e($a['province']) ?></span></span></label>
      <?php endforeach; ?>
    </div>
    <div class="card table-wrap"><h3>Produk</h3><table><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
      <?php foreach ($items as $i): ?><tr><td><?= e($i['name']) ?><div class="muted small">🏪 <?= e($i['store_name']) ?></div></td><td><?= rupiah($i['price']) ?></td><td><?= (int)$i['qty'] ?></td><td><?= rupiah($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?></table></div>
  </div>
  <div class="card"><h3>Ringkasan</h3><div class="row-flex"><span class="grow">Total</span><strong class="price-lg" style="font-size:22px"><?= rupiah($total) ?></strong></div>
    <p class="muted small">Dana ditahan sistem sampai serah terima barang diverifikasi.</p>
    <button class="btn btn-block" <?= $addresses ? '' : 'disabled' ?>>Bayar Sekarang</button></div>
</form>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
