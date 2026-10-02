<?php
require_once __DIR__ . '/../config/config.php';
$user = requireBuyer();
$pageTitle = 'Beranda Pembeli';
$st = db()->prepare("SELECT COUNT(*) total, SUM(order_status IN ('pending_payment')) unpaid, SUM(order_status IN ('ready_for_handover','processing')) active_o, SUM(order_status='completed') done FROM orders WHERE user_id = ?");
$st->execute([$user['id']]);
$stat = $st->fetch();
$cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$latest = db()->query(productSelect() . "WHERE p.status='active' AND s.status='active' AND p.stock>0 ORDER BY p.id DESC LIMIT 8")->fetchAll();
$store = storeOf($user['id']);
include __DIR__ . '/../includes/header.php';
?>
<section class="hero"><h1>Halo, <?= e(explode(' ', $user['name'])[0]) ?>! 👋</h1><p>Temukan barang kampus yang kamu butuhkan hari ini.</p>
  <a class="btn" href="products.php">Mulai Belanja</a></section>
<div class="stats mt">
  <div class="stat"><small>Total Pesanan</small><strong><?= (int)$stat['total'] ?></strong></div>
  <div class="stat"><small>Belum Dibayar</small><strong><?= (int)$stat['unpaid'] ?></strong></div>
  <div class="stat"><small>Menunggu Serah Terima</small><strong><?= (int)$stat['active_o'] ?></strong></div>
  <div class="stat"><small>Selesai</small><strong><?= (int)$stat['done'] ?></strong></div>
</div>
<?php if ($user['seller_enabled'] && $store && $store['status'] === 'active'): ?>
  <div class="card row-flex"><div class="grow"><strong>🏪 <?= e($store['name']) ?></strong><div class="muted small">Kelola produk dan pesanan tokomu.</div></div><a class="btn" href="<?= e(url('seller/dashboard.php')) ?>">Beralih ke Toko</a></div>
<?php elseif ($store): ?>
  <div class="card">Status toko Anda: <?= badge($store['status']) ?> <a href="open-store.php">Lihat detail</a></div>
<?php else: ?>
  <div class="card row-flex"><div class="grow"><strong>Ingin berjualan?</strong><div class="muted small">Buka toko dan jual barang kampus Anda.</div></div><a class="btn btn-outline" href="open-store.php">Buka Toko</a></div>
<?php endif; ?>
<div class="section-head"><h2>Kategori</h2></div>
<div class="chips"><?php foreach ($cats as $c): ?><a class="chip" href="products.php?category=<?= (int)$c['id'] ?>"><span><?= e($c['icon']) ?></span><?= e($c['name']) ?></a><?php endforeach; ?></div>
<div class="section-head"><h2>Produk Terbaru</h2><a href="products.php">Lihat semua →</a></div>
<div class="grid-products"><?php foreach ($latest as $p) echo productCard($p); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
