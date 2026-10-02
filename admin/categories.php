<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();
if (isPost()) {
    checkCsrfOrFail();
    $act = $_POST['action'] ?? ''; $name = trim($_POST['name'] ?? ''); $icon = trim($_POST['icon'] ?? '') ?: '📦'; $id = (int)($_POST['id'] ?? 0);
    try {
        if ($act === 'add' && $name !== '') { db()->prepare('INSERT INTO categories (name, icon) VALUES (?,?)')->execute([$name, mb_substr($icon, 0, 4)]); adminLog('Tambah kategori', $name); flash('success', 'Kategori ditambahkan.'); }
        elseif ($act === 'edit' && $name !== '') { db()->prepare('UPDATE categories SET name=?, icon=? WHERE id=?')->execute([$name, mb_substr($icon, 0, 4), $id]); adminLog('Ubah kategori', $name); flash('success', 'Kategori diperbarui.'); }
        elseif ($act === 'delete') {
            $c = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id=?'); $c->execute([$id]);
            if ($c->fetchColumn()) flash('danger', 'Kategori masih dipakai produk, tidak dapat dihapus.');
            else { db()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]); adminLog('Hapus kategori', '#' . $id); flash('success', 'Kategori dihapus.'); }
        }
    } catch (PDOException $e) { flash('danger', 'Nama kategori sudah ada atau tidak valid.'); }
    redirect('admin/categories.php');
}
$cats = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) n FROM categories c ORDER BY id')->fetchAll();
$layout = 'admin'; $active = 'categories.php'; $pageTitle = 'Kategori Produk';
include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="card row-flex"><?= csrfField() ?><input type="hidden" name="action" value="add"><input name="icon" placeholder="Ikon 📦" style="width:90px"><input name="name" placeholder="Nama kategori baru" required><button class="btn">Tambah</button></form>
<div class="card table-wrap"><table><tr><th>ID</th><th>Ikon</th><th>Kategori</th><th>Produk</th><th></th></tr>
<?php foreach ($cats as $c): $f = 'cat' . $c['id']; ?><tr>
  <td><?= $c['id'] ?><form id="<?= $f ?>" method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></form></td>
  <td><input form="<?= $f ?>" name="icon" value="<?= e($c['icon']) ?>" style="width:70px"></td>
  <td><input form="<?= $f ?>" name="name" value="<?= e($c['name']) ?>" required></td><td><?= (int)$c['n'] ?></td>
  <td class="row-flex"><button form="<?= $f ?>" class="btn btn-outline btn-sm" name="action" value="edit">Simpan</button>
    <button form="<?= $f ?>" class="btn btn-danger btn-sm" name="action" value="delete" onclick="return confirm('Hapus kategori ini?')" formnovalidate>Hapus</button></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
