<?php
$home = $authUser ? 'buyer/dashboard.php' : 'index.php';
$cnt = ($authUser && $authUser['role'] === 'buyer') ? cartCount($authUser['id']) : 0;
$myStore = ($authUser && $authUser['role'] === 'buyer') ? storeOf($authUser['id']) : null;
?>
<header class="navbar">
  <div class="container nav-inner">
    <a class="logo" href="<?= e(url($home)) ?>">🎓 KampusMart</a>
    <button class="menu-btn" id="menuBtn" aria-label="Menu">☰</button>
    <nav class="nav-links" id="navLinks">
      <a href="<?= e(url($home)) ?>">Beranda</a>
      <a href="<?= e(url('buyer/products.php')) ?>">Produk</a>
      <a href="<?= e(url('index.php#kategori')) ?>">Kategori</a>
      <?php if ($authUser): ?><a href="<?= e(url('buyer/orders.php')) ?>">Pesanan</a><?php endif; ?>
      <a href="<?= e(url('buyer/cart.php')) ?>">Keranjang<?= $cnt ? ' <span class="pill">' . $cnt . '</span>' : '' ?></a>
    </nav>
    <form class="nav-search" action="<?= e(url('buyer/products.php')) ?>" method="get">
      <input type="search" name="search" placeholder="Cari produk, toko, atau kategori..." value="<?= e($_GET['search'] ?? '') ?>">
    </form>
    <div class="nav-user">
    <?php if ($authUser): ?>
      <details class="dropdown">
        <summary>👤 <?= e(explode(' ', $authUser['name'])[0]) ?></summary>
        <div class="dropdown-menu">
          <a href="<?= e(url('buyer/profile.php')) ?>">Profil Saya</a>
          <a href="<?= e(url('buyer/addresses.php')) ?>">Alamat</a>
          <?php if ($authUser['role'] === 'admin'): ?><a href="<?= e(url('admin/dashboard.php')) ?>">Panel Admin</a>
          <?php elseif ($authUser['seller_enabled'] && $myStore && $myStore['status'] === 'active'): ?><a href="<?= e(url('seller/dashboard.php')) ?>">Beralih ke Toko</a>
          <?php else: ?><a href="<?= e(url('buyer/open-store.php')) ?>">Buka Toko</a><?php endif; ?>
          <a href="<?= e(url('logout.php')) ?>">Logout</a>
        </div>
      </details>
    <?php else: ?>
      <a class="btn btn-outline btn-sm" href="<?= e(url('login.php')) ?>">Masuk</a>
      <a class="btn btn-sm" href="<?= e(url('register.php')) ?>">Daftar</a>
    <?php endif; ?>
    </div>
  </div>
</header>
