<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Beranda';
try {
    $cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    $where = "WHERE p.status='active' AND s.status='active' AND p.stock > 0";
    $featured = db()->query(productSelect() . $where . ' ORDER BY avg_rating DESC, p.id DESC LIMIT 4')->fetchAll();
    $latest = db()->query(productSelect() . $where . ' ORDER BY p.id DESC LIMIT 8')->fetchAll();
} catch (Throwable $e) { logError('index', $e); $cats = $featured = $latest = []; }
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Jual Beli Barang Kampus dengan Mudah &amp; Aman</h1>
  <p>Temukan buku, jas lab, perlengkapan kos, alat praktikum, kalkulator dan kebutuhan kampus lainnya.</p>
  <a class="btn" href="<?= e(url('buyer/products.php')) ?>">Mulai Belanja</a>
</section>

<div class="section-head" id="kategori"><h2>Kategori</h2></div>
<div class="chips">
<?php foreach ($cats as $c): ?>
  <a class="chip" href="<?= e(url('buyer/products.php?category=' . $c['id'])) ?>"><span><?= e($c['icon']) ?></span><?= e($c['name']) ?></a>
<?php endforeach; ?>
</div>

<div class="section-head"><h2>Produk Pilihan</h2><a href="<?= e(url('buyer/products.php')) ?>">Lihat semua →</a></div>
<div class="grid-products"><?php foreach ($featured as $p) echo productCard($p); ?></div>
<?php if (!$featured): ?><p class="empty">Belum ada produk.</p><?php endif; ?>

<div class="section-head"><h2>Produk Terbaru</h2><a href="<?= e(url('buyer/products.php?sort=newest')) ?>">Lihat semua →</a></div>
<div class="grid-products"><?php foreach ($latest as $p) echo productCard($p); ?></div>

<div class="section-head"><h2>Transaksi Aman di KampusMart</h2></div>
<div class="trust">
  <div class="card"><div class="ico">🔒</div><strong>Dana Ditahan</strong><p class="muted small">Pembayaran ditahan sistem, belum diterima penjual.</p></div>
  <div class="card"><div class="ico">🤝</div><strong>Serah Terima QR</strong><p class="muted small">Penjual scan QR saat barang diserahkan langsung.</p></div>
  <div class="card"><div class="ico">✅</div><strong>Dana Dilepas</strong><p class="muted small">Setelah QR valid, dana masuk ke saldo penjual.</p></div>
</div>

<section class="cta">
  <h2>Punya barang yang tidak terpakai?</h2>
  <p class="muted">Daftar, buka toko, dan mulai jual ke sesama mahasiswa. Satu akun untuk membeli dan menjual.</p>
  <a class="btn" href="<?= e(url(isLoggedIn() ? 'buyer/open-store.php' : 'register.php')) ?>">Mulai Jualan</a>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
