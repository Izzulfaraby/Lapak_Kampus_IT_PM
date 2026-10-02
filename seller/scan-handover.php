<?php
require_once __DIR__ . '/../config/config.php';
$store = requireSeller();
if (isPost()) {
    checkCsrfOrFail();
    [$ok, $msg] = processHandover($store['id'], $_POST['token'] ?? '');
    flash($ok ? 'success' : 'danger', $msg);
    redirect('seller/scan-handover.php');
}
$layout = 'seller'; $active = 'scan-handover.php'; $pageTitle = 'Scan QR Serah Terima';
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:520px">
  <p>Minta pembeli menunjukkan QR Serah Terima, lalu scan dengan kamera. Dana dilepas ke saldo Anda setelah token valid.</p>
  <video id="scanVideo" class="scan-video" playsinline muted></video>
  <p><button type="button" class="btn" id="startScan">📷 Buka Kamera</button></p>
  <p class="muted small" id="scanNote">Jika kamera tidak tersedia, masukkan token secara manual.</p>
  <form method="post"><?= csrfField() ?>
    <label>Token / isi QR</label><input id="tokenInput" name="token" required placeholder="KAMPUSMART-HANDOVER-...">
    <button class="btn btn-success btn-block mt">Verifikasi &amp; Selesaikan</button></form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
