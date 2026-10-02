<?php
require_once __DIR__ . '/../config/config.php';   // halaman publik: boleh diakses tanpa login
$pageTitle = 'Produk';
$search = trim($_GET['search'] ?? '');
$cat = (int)($_GET['category'] ?? 0);
$min = ($_GET['min'] ?? '') !== '' ? max(0, (float)$_GET['min']) : null;
$max = ($_GET['max'] ?? '') !== '' ? max(0, (float)$_GET['max']) : null;
$loc = trim($_GET['location'] ?? '');
$cond = in_array($_GET['condition'] ?? '', ['Baru', 'Bekas'], true) ? $_GET['condition'] : '';
$rating = (int)($_GET['rating'] ?? 0);
$storeId = (int)($_GET['store'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';

$where = ["p.status='active'", "s.status='active'"]; $params = [];
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.description LIKE ? OR s.name LIKE ? OR c.name LIKE ?)'; $like = '%' . $search . '%'; array_push($params, $like, $like, $like, $like); }
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($min !== null) { $where[] = 'p.price >= ?'; $params[] = $min; }
if ($max !== null) { $where[] = 'p.price <= ?'; $params[] = $max; }
if ($loc !== '') { $where[] = 's.city LIKE ?'; $params[] = '%' . $loc . '%'; }
if ($cond) { $where[] = 'p.item_condition = ?'; $params[] = $cond; }
if ($rating) { $where[] = '(SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id) >= ?'; $params[] = $rating; }
if ($storeId) { $where[] = 'p.store_id = ?'; $params[] = $storeId; }
$orderBy = ['newest' => 'p.id DESC', 'cheap' => 'p.price ASC', 'expensive' => 'p.price DESC', 'rating' => 'avg_rating DESC'][$sort] ?? 'p.id DESC';
try {
    $st = db()->prepare(productSelect() . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $orderBy LIMIT 60");
    $st->execute($params);
    $products = $st->fetchAll();
    $cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    $stores = db()->query("SELECT id, name FROM stores WHERE status='active' ORDER BY name")->fetchAll();
} catch (Throwable $e) { logError('products', $e); $products = $cats = $stores = []; }
include __DIR__ . '/../includes/header.php';
?>
<div class="filters">
  <aside><form class="card" method="get">
    <h3>Filter</h3>
    <input type="hidden" name="search" value="<?= e($search) ?>">
    <label>Kategori</label><select name="category"><option value="0">Semua</option>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <div class="form-row"><div><label>Harga min</label><input type="number" name="min" min="0" value="<?= e($_GET['min'] ?? '') ?>"></div><div><label>Harga maks</label><input type="number" name="max" min="0" value="<?= e($_GET['max'] ?? '') ?>"></div></div>
    <label>Lokasi (kota)</label><input name="location" value="<?= e($loc) ?>">
    <label>Kondisi</label><select name="condition"><option value="">Semua</option><option <?= $cond === 'Baru' ? 'selected' : '' ?>>Baru</option><option <?= $cond === 'Bekas' ? 'selected' : '' ?>>Bekas</option></select>
    <label>Rating minimal</label><select name="rating"><option value="0">Semua</option><?php for ($i = 4; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>><?= $i ?>★ ke atas</option><?php endfor; ?></select>
    <label>Toko</label><select name="store"><option value="0">Semua Toko</option><?php foreach ($stores as $s): ?><option value="<?= $s['id'] ?>" <?= $storeId == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select>
    <label>Urutkan</label><select name="sort">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option><option value="cheap" <?= $sort === 'cheap' ? 'selected' : '' ?>>Harga termurah</option>
      <option value="expensive" <?= $sort === 'expensive' ? 'selected' : '' ?>>Harga termahal</option><option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating tertinggi</option></select>
    <button class="btn btn-block mt">Terapkan Filter</button>
    <a class="btn btn-outline btn-block mt" href="products.php">Reset</a>
  </form></aside>
  <section>
    <h2><?= $search !== '' ? 'Hasil pencarian "' . e($search) . '"' : 'Semua Produk' ?> <span class="muted small">(<?= count($products) ?> produk)</span></h2>
    <?php if (!$products): ?><div class="card empty">Produk tidak ditemukan. Coba ubah filter.</div><?php endif; ?>
    <div class="grid-products" style="grid-template-columns:repeat(auto-fill,minmax(190px,1fr))"><?php foreach ($products as $p) echo productCard($p); ?></div>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
