<?php
require_once __DIR__ . '/config/config.php';
if (currentUser()) redirect(currentUser()['role'] === 'admin' ? 'admin/dashboard.php' : 'buyer/dashboard.php');
$error = '';
if (isPost()) {
    checkCsrfOrFail();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    try {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            if ($u['status'] !== 'active') {
                $error = 'Akun Anda dinonaktifkan. Hubungi admin.';
            } else {
                session_regenerate_id(true);   // cegah session fixation
                $_SESSION['user_id'] = (int)$u['id'];
                flash('success', 'Selamat datang, ' . $u['name'] . '!');
                redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'buyer/dashboard.php');
            }
        } else {
            $error = 'Email atau password salah.';
        }
    } catch (Throwable $e) { logError('login', $e); $error = 'Terjadi kesalahan sistem. Pastikan database sudah diimpor.'; }
}
$layout = 'auth'; $pageTitle = 'Masuk';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="brand"><a class="logo" href="<?= e(url('index.php')) ?>">🎓 KampusMart</a><div class="muted small">Jual Beli Barang Kampus</div></div>
  <h1>Masuk ke Akun Anda</h1>
  <?php if ($error): ?><p class="error-text"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <?= csrfField() ?>
    <label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="email@kampus.ac.id">
    <label>Password</label>
    <div class="pass-wrap"><input type="password" name="password" required placeholder="Password"><button type="button" data-toggle-pass>👁</button></div>
    <button class="btn btn-block" style="margin-top:18px">Login</button>
  </form>
  <div class="auth-foot">Belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar</a></div>
  <p class="muted small" style="text-align:center">Demo: buyer@kampusmart.test / Buyer123!</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
