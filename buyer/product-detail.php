<?php
require_once __DIR__ . '/../config/config.php';   // publik; aksi beli butuh login
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(productSelect() . " WHERE p.id = ? AND p.status='active' AND s.status='active'");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { flash('warning', 'Produk tidak ditemukan.'); redirect('buyer/products.php'); }
$user = currentUser();
$isOwn = $user && (int)$p['owner_id'] === (int)$user['id'];

// Cek apakah user berhak memberi review (pernah membeli & pesanan selesai) dan belum review
$canReview = false;
if ($user) {
    $c = db()->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.user_id = ? AND o.order_status = 'completed'");
    $c->execute([$id, $user['id']]);
    $bought = $c->fetchColumn() > 0;
    $r = db()->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?'); $r->execute([$id, $user['id']]);
    $canReview = $bought && !$r->fetchColumn();
}
if (isPost() && ($_POST['action'] ?? '') === 'review') {
    requireBuyer(); checkCsrfOrFail();
    $rating = (int)($_POST['rating'] ?? 0); $comment = trim($_POST['comment'] ?? '');
    if (!$canReview) flash('danger', 'Anda hanya dapat mengulas produk yang sudah Anda beli dan terima.');
    elseif ($rating < 1 || $rating > 5) flash('danger', 'Rating harus 1-5.');
    else {
        db()->prepare('INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?,?,?,?)')->execute([$id, $user['id'], $rating, mb_substr($comment, 0, 1000)]);
        flash('success', 'Terima kasih atas ulasan Anda!');
    }
    header('Location: product-detail.php?id=' . $id); exit;
}
$rv = db()->prepare('SELECT r.*, u.name AS user_name, rr.reply FROM reviews r JOIN users u ON u.id = r.user_id LEFT JOIN review_replies rr ON rr.review_id = r.id WHERE r.product_id = ? ORDER BY r.id DESC');
$rv->execute([$id]);
$reviews = $rv->fetchAll();
$pageTitle = $p['name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="detail">
  <div class="big"><?= productImg($p) ?></div>
  <div>
    <h1><?= e($p['name']) ?></h1>
    <div class="stars">★ <?= $p['avg_rating'] ? e($p['avg_rating']) : '-' ?> <span class="muted small">(<?= (int)$p['review_count'] ?> ulasan)</span></div>
    <div class="price-lg"><?= rupiah($p['price']) ?></div>
    <p><span class="badge badge-info"><?= e($p['category_name']) ?></span> <span class="badge badge-muted"><?= e($p['item_condition']) ?></span>
       Stok: <strong><?= $p['stock'] > 0 ? (int)$p['stock'] : 'Habis' ?></strong></p>
    <div class="card"><strong>🏪 <?= e($p['store_name']) ?></strong><div class="muted small">📍 <?= e($p['store_city']) ?> — <?= e($p['store_address']) ?></div></div>
    <?php if ($isOwn): ?>
      <p class="badge badge-warning">Ini produk toko Anda sendiri.</p>
    <?php elseif ($p['stock'] > 0): ?>
      <form method="post" action="<?= e(url('buyer/cart.php')) ?>">
        <?= csrfField() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <label>Jumlah</label>
        <div class="qty"><button type="button" data-d="-">−</button><input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>"><button type="button" data-d="+">+</button></div>
        <div class="row-flex mt">
          <button class="btn btn-outline" name="action" value="add">🛒 Tambah ke Keranjang</button>
          <button class="btn" name="action" value="buynow">Beli Sekarang</button>
        </div>
      </form>
    <?php else: ?><p class="badge badge-danger">Stok habis</p><?php endif; ?>
  </div>
</div>
<div class="card mt"><h3>Deskripsi</h3><p><?= nl2br(e($p['description'])) ?></p></div>
<div class="card"><h3>Ulasan (<?= count($reviews) ?>)</h3>
  <?php if ($canReview): ?>
  <form method="post" class="mb"><?= csrfField() ?><input type="hidden" name="action" value="review">
    <label>Rating</label><select name="rating"><?php for ($i = 5; $i >= 1; $i--) echo "<option value=\"$i\">$i ★</option>"; ?></select>
    <label>Komentar</label><textarea name="comment" maxlength="1000"></textarea><button class="btn mt">Kirim Ulasan</button></form>
  <?php endif; ?>
  <?php foreach ($reviews as $r): ?>
    <div style="border-top:1px solid var(--border);padding:10px 0"><strong><?= e($r['user_name']) ?></strong> <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span>
      <span class="muted small"><?= tgl($r['created_at']) ?></span><p><?= nl2br(e($r['comment'])) ?></p>
      <?php if ($r['reply']): ?><div class="token" style="font-family:inherit">💬 Balasan toko: <?= e($r['reply']) ?></div><?php endif; ?></div>
  <?php endforeach; ?>
  <?php if (!$reviews): ?><p class="muted">Belum ada ulasan.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
