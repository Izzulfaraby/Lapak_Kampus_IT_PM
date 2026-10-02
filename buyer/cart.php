<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$cartId = getCartId($user['id']);
if (isPost()) {
    checkCsrfOrFail();
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($action === 'add' || $action === 'buynow') {
        // Validasi BACKEND: produk aktif, stok cukup, bukan toko sendiri
        $err = validatePurchase($user['id'], $pid, $qty);
        if ($err) { flash('danger', $err); header('Location: product-detail.php?id=' . $pid); exit; }
        if ($action === 'buynow') { $_SESSION['buynow'] = ['product_id' => $pid, 'qty' => $qty]; redirect('buyer/checkout.php?mode=now'); }
        $cur = db()->prepare('SELECT quantity FROM cart_items WHERE cart_id=? AND product_id=?'); $cur->execute([$cartId, $pid]);
        $newQty = $qty + (int)$cur->fetchColumn();
        $err = validatePurchase($user['id'], $pid, $newQty);
        if ($err) { flash('danger', $err); header('Location: product-detail.php?id=' . $pid); exit; }
        db()->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = ?')->execute([$cartId, $pid, $newQty, $newQty]);
        flash('success', 'Produk ditambahkan ke keranjang.');
        redirect('buyer/cart.php');
    }
    if ($action === 'update') {
        $err = validatePurchase($user['id'], $pid, $qty);
        if ($err) flash('danger', $err);
        else db()->prepare('UPDATE cart_items SET quantity=? WHERE cart_id=? AND product_id=?')->execute([$qty, $cartId, $pid]);
    }
    if ($action === 'remove') db()->prepare('DELETE FROM cart_items WHERE cart_id=? AND product_id=?')->execute([$cartId, $pid]);
    redirect('buyer/cart.php');
}
$items = cartItems($user['id']);
$total = 0; foreach ($items as $i) $total += $i['price'] * $i['qty'];
$pageTitle = 'Keranjang Belanja';
include __DIR__ . '/../includes/header.php';
?>
<h1>Keranjang Belanja</h1>
<?php if (!$items): ?><div class="card empty">Keranjang kosong. <a href="products.php">Mulai belanja</a></div><?php else: ?>
<div class="card table-wrap"><table>
  <tr><th>Produk</th><th>Toko</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
  <?php foreach ($items as $i): $bad = $i['status'] !== 'active' || $i['owner_id'] == $user['id']; ?>
  <tr>
    <td><div class="row-flex"><?= productImg($i, 'thumb') ?><a href="product-detail.php?id=<?= $i['id'] ?>"><?= e($i['name']) ?></a></div></td>
    <td><?= e($i['store_name']) ?></td><td><?= rupiah($i['price']) ?></td>
    <td><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= $i['id'] ?>">
      <div class="qty"><button type="button" data-d="-">−</button><input type="number" name="qty" value="<?= (int)$i['qty'] ?>" min="1" max="<?= (int)$i['stock'] ?>" data-autosubmit="1" onchange="this.form.submit()"><button type="button" data-d="+">+</button></div></form></td>
    <td><strong><?= rupiah($i['price'] * $i['qty']) ?></strong><?= $bad ? '<br><span class="error-text">Tidak dapat dibeli</span>' : '' ?></td>
    <td><form method="post" data-confirm="Hapus dari keranjang?"><?= csrfField() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= $i['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></td>
  </tr><?php endforeach; ?>
</table></div>
<div class="card row-flex"><div class="grow"><span class="muted">Total (<?= count($items) ?> produk)</span><div class="price-lg"><?= rupiah($total) ?></div></div>
  <a class="btn" href="checkout.php">Checkout</a></div>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
