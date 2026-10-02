<?php
/**
 * Layout pembungkus. Variabel yang diatur halaman sebelum include:
 *   $layout    : 'site' (default) | 'auth' | 'seller' | 'admin'
 *   $pageTitle : judul halaman
 *   $active    : menu sidebar yang aktif
 */
$layout = $layout ?? 'site';
$pageTitle = $pageTitle ?? APP_NAME;
$active = $active ?? '';
$authUser = isLoggedIn() ? currentUser() : null;
$menus = [
  'seller' => [
    ['dashboard.php', 'Dashboard', '🏠'], ['products.php', 'Produk', '📦'], ['orders.php', 'Pesanan Masuk', '🧾'],
    ['sales.php', 'Riwayat Penjualan', '📈'], ['balance.php', 'Saldo & Keuangan', '💰'], ['withdrawals.php', 'Penarikan', '🏦'],
    ['store-settings.php', 'Pengaturan Toko', '⚙️'], ['reviews.php', 'Ulasan', '⭐'], ['scan-handover.php', 'Scan QR', '📷'],
  ],
  'admin' => [
    ['dashboard.php', 'Dashboard', '🏠'], ['users.php', 'Pengguna', '👥'], ['stores.php', 'Toko/Penjual', '🏪'],
    ['categories.php', 'Kategori', '🏷️'], ['transactions.php', 'Transaksi', '💳'], ['withdrawals.php', 'Penarikan', '🏦'],
    ['settings.php', 'Pengaturan Website', '⚙️'], ['logs.php', 'Log Sistem', '📜'],
  ],
];
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> - <?= e(setting('site_title', APP_NAME)) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<?php if ($layout === 'auth'): ?><link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>"><?php endif; ?>
<?php if ($layout === 'site'): ?><link rel="stylesheet" href="<?= e(url('assets/css/buyer.css')) ?>"><?php endif; ?>
<?php if ($layout === 'seller'): ?><link rel="stylesheet" href="<?= e(url('assets/css/seller.css')) ?>"><?php endif; ?>
<?php if ($layout === 'admin'): ?><link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>"><?php endif; ?>
</head>
<body class="layout-<?= e($layout) ?>">
<div class="toasts" id="toasts">
<?php foreach (getFlash() as [$t, $m]): ?><div class="toast toast-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
</div>
<?php if ($layout === 'site'): include __DIR__ . '/navbar.php'; ?>
<main class="container page">
<?php elseif ($layout === 'seller' || $layout === 'admin'): $base = $layout . '/'; ?>
<div class="panel">
  <aside class="sidebar" id="sidebar">
    <a class="logo" href="<?= e(url('index.php')) ?>">🎓 KampusMart</a>
    <div class="sidebar-sub"><?= $layout === 'seller' ? 'Mode Toko' : 'Admin Panel' ?></div>
    <nav>
      <?php foreach ($menus[$layout] as [$f, $label, $ico]): ?>
        <a href="<?= e(url($base . $f)) ?>" class="<?= $active === $f ? 'active' : '' ?>"><span><?= $ico ?></span> <?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if ($layout === 'seller'): ?><a href="<?= e(url('buyer/dashboard.php')) ?>"><span>🛒</span> Beralih ke Pembeli</a><?php endif; ?>
      <a href="<?= e(url('logout.php')) ?>"><span>🚪</span> Logout</a>
    </nav>
  </aside>
  <div class="panel-main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn" aria-label="Menu">☰</button>
      <h1><?= e($pageTitle) ?></h1>
      <span class="topbar-user"><?= e($authUser['name'] ?? '') ?></span>
    </header>
    <div class="panel-content">
<?php endif; ?>
