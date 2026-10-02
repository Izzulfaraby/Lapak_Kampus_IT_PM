<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
$rows = db()->query('SELECT l.*, u.name AS admin FROM admin_logs l LEFT JOIN users u ON u.id=l.admin_id ORDER BY l.id DESC LIMIT 200')->fetchAll();
$layout = 'admin'; $active = 'logs.php'; $pageTitle = 'Log Sistem';
include __DIR__ . '/../includes/header.php';
?>
<div class="card table-wrap"><table><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Keterangan</th></tr>
<?php foreach ($rows as $l): ?><tr><td class="small"><?= tgl($l['created_at']) ?></td><td><?= e($l['admin']) ?></td><td><?= e($l['action']) ?></td><td class="small"><?= e($l['description']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" class="empty">Belum ada log.</td></tr><?php endif; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
