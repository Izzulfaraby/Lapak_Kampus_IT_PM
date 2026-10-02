<?php
require_once __DIR__ . '/config/config.php';
if (currentUser()) redirect('buyer/dashboard.php');
$errors = []; $old = ['name' => '', 'email' => '', 'phone' => ''];
if (isPost()) {
    checkCsrfOrFail();
    $old = ['name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')];
    $pass = $_POST['password'] ?? ''; $conf = $_POST['confirm'] ?? '';
    if (mb_strlen($old['name']) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\- ]{8,20}$/', $old['phone'])) $errors[] = 'Nomor HP tidak valid.';
    if (strlen($pass) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($pass !== $conf) $errors[] = 'Konfirmasi password tidak sama.';
    if (!$errors) {
        try {
            $st = db()->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$old['email']]);
            if ($st->fetch()) { $errors[] = 'Email sudah terdaftar.'; }
            else {
                // Role awal selalu buyer; menjadi penjual lewat "Buka Toko"
                db()->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,"buyer")')
                    ->execute([$old['name'], $old['email'], $old['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
                flash('success', 'Pendaftaran berhasil. Silakan login.');
                redirect('login.php');
            }
        } catch (Throwable $e) { logError('register', $e); $errors[] = 'Terjadi kesalahan sistem.'; }
    }
}
$layout = 'auth'; $pageTitle = 'Daftar';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="brand"><a class="logo" href="<?= e(url('index.php')) ?>">🎓 KampusMart</a></div>
  <h1>Buat Akun Baru</h1>
  <?php foreach ($errors as $er): ?><p class="error-text"><?= e($er) ?></p><?php endforeach; ?>
  <form method="post">
    <?= csrfField() ?>
    <label>Nama Lengkap</label><input name="name" required value="<?= e($old['name']) ?>">
    <label>Email</label><input type="email" name="email" required value="<?= e($old['email']) ?>">
    <label>Nomor HP</label><input name="phone" value="<?= e($old['phone']) ?>">
    <label>Password</label><div class="pass-wrap"><input type="password" name="password" required minlength="8"><button type="button" data-toggle-pass>👁</button></div>
    <label>Konfirmasi Password</label><div class="pass-wrap"><input type="password" name="confirm" required><button type="button" data-toggle-pass>👁</button></div>
    <button class="btn btn-block" style="margin-top:18px">Daftar</button>
  </form>
  <div class="auth-foot">Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Login</a></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
