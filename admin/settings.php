<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
$keys = ['site_title' => 'Judul Website', 'site_description' => 'Deskripsi', 'banner_text' => 'Teks Banner', 'policy' => 'Kebijakan', 'terms' => 'Syarat & Ketentuan', 'contact_info' => 'Informasi Kontak'];
if (isPost()) {
    checkCsrfOrFail();
    $up = db()->prepare('INSERT INTO website_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach ($keys as $k => $l) $up->execute([$k, trim($_POST[$k] ?? '')]);
    adminLog('Ubah pengaturan website', 'Pengaturan diperbarui');
    flash('success', 'Pengaturan disimpan.'); redirect('admin/settings.php');
}
$layout = 'admin'; $active = 'settings.php'; $pageTitle = 'Pengaturan Website';
include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="card" style="max-width:720px"><?= csrfField() ?>
<?php foreach ($keys as $k => $l): ?><label><?= e($l) ?></label><?php if ($k === 'site_title'): ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"><?php else: ?><textarea name="<?= $k ?>"><?= e(setting($k)) ?></textarea><?php endif; endforeach; ?>
<button class="btn mt">Simpan</button></form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
