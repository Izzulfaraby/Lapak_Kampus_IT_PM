<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
$q = fn($sql) => db()->query($sql)->fetchColumn();
$s = [
  'Total Pengguna' => $q("SELECT COUNT(*) FROM users WHERE role='buyer'"),
  'Total Penjual' => $q('SELECT COUNT(*) FROM users WHERE seller_enabled=1'),
  'Total Toko' => $q('SELECT COUNT(*) FROM stores'),
  'Total Produk' => $q('SELECT COUNT(*) FROM products'),
  'Total Transaksi' => $q("SELECT COUNT(*) FROM orders WHERE order_status NOT IN ('pending_payment','cancelled')"),
  'Total Pendapatan (Released)' => rupiah($q("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE type='release'")),
  'Dana Ditahan' => rupiah($q('SELECT COALESCE(SUM(held_balance),0) FROM seller_balances')),
  'Dana Released' => rupiah($q("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE type='release'")),
  'Withdrawal Pending' => $q("SELECT COUNT(*) FROM withdrawals WHERE status='pending'"),
];
$pendingStores = $q("SELECT COUNT(*) FROM stores WHERE status='pending'");
$layout = 'admin'; $active = 'dashboard.php'; $pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="stats"><?php foreach ($s as $l => $v): ?><div class="stat"><small><?= e($l) ?></small><strong><?= e($v) ?></strong></div><?php endforeach; ?></div>
<?php if ($pendingStores): ?><div class="card">🏪 Ada <strong><?= (int)$pendingStores ?></strong> pengajuan toko menunggu. <a href="stores.php">Tinjau sekarang</a></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
