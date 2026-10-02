<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
if (isPost()) {
    checkCsrfOrFail();
    $rid = (int)($_POST['review_id'] ?? 0); $reply = trim($_POST['reply'] ?? '');
    // Pastikan ulasan memang untuk produk toko ini
    $c = db()->prepare('SELECT COUNT(*) FROM reviews r JOIN products p ON p.id=r.product_id WHERE r.id=? AND p.store_id=?'); $c->execute([$rid, $store['id']]);
    if (!$c->fetchColumn() || $reply === '') flash('danger', 'Balasan tidak valid.');
    else { db()->prepare('INSERT INTO review_replies (review_id, store_id, reply) VALUES (?,?,?) ON DUPLICATE KEY UPDATE reply=VALUES(reply)')->execute([$rid, $store['id'], mb_substr($reply, 0, 1000)]); flash('success', 'Balasan terkirim.'); }
    redirect('seller/reviews.php');
}
$st = db()->prepare('SELECT r.*, p.name AS product, u.name AS buyer, rr.reply FROM reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id LEFT JOIN review_replies rr ON rr.review_id=r.id WHERE p.store_id=? ORDER BY r.id DESC');
$st->execute([$store['id']]); $rows = $st->fetchAll();
$layout = 'seller'; $active = 'reviews.php'; $pageTitle = 'Ulasan';
include __DIR__ . '/../includes/header.php';
?>
<?php foreach ($rows as $r): ?><div class="card"><strong><?= e($r['product']) ?></strong> <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span><div class="muted small">oleh <?= e($r['buyer']) ?> · <?= tgl($r['created_at']) ?></div>
  <p><?= nl2br(e($r['comment'])) ?></p>
  <form method="post"><?= csrfField() ?><input type="hidden" name="review_id" value="<?= $r['id'] ?>"><textarea name="reply" placeholder="Tulis balasan..."><?= e($r['reply']) ?></textarea><button class="btn btn-sm mt"><?= $r['reply'] ? 'Perbarui Balasan' : 'Balas' ?></button></form></div>
<?php endforeach; if (!$rows) echo '<div class="card empty">Belum ada ulasan.</div>'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
