<?php
/** Fungsi bantu (helper) umum */

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect($path) { header('Location: ' . url($path)); exit; }
function flash($type, $msg) { $_SESSION['flash'][] = [$type, $msg]; }
function getFlash() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d) { return $d ? date('d M Y H:i', strtotime($d)) : '-'; }

/** Catat error ke logs/app.log (tanpa menampilkan detail ke user) */
function logError($context, $e = null) {
    $msg = '[' . date('Y-m-d H:i:s') . "] $context";
    if ($e instanceof Throwable) $msg .= ': ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();
    @file_put_contents(ROOT_PATH . '/logs/app.log', $msg . PHP_EOL, FILE_APPEND);
}

/** Label + warna badge untuk semua status */
function badge($status) {
    $map = [
        'pending' => ['Pending', 'warning'], 'pending_payment' => ['Menunggu Pembayaran', 'warning'],
        'paid' => ['Sudah Dibayar', 'info'], 'held' => ['Held (Ditahan)', 'orange'],
        'released' => ['Released', 'success'], 'failed' => ['Gagal', 'danger'],
        'refunded' => ['Refunded', 'muted'], 'processing' => ['Diproses', 'info'],
        'ready_for_handover' => ['Siap Serah Terima', 'info'], 'completed' => ['Selesai', 'success'],
        'cancelled' => ['Dibatalkan', 'danger'], 'active' => ['Aktif', 'success'],
        'rejected' => ['Ditolak', 'danger'], 'suspended' => ['Ditangguhkan', 'danger'],
        'inactive' => ['Nonaktif', 'muted'], 'approved' => ['Disetujui', 'success'],
        'paid_out' => ['Dibayar', 'success'], 'used' => ['Terpakai', 'success'], 'expired' => ['Kedaluwarsa', 'muted'],
    ];
    [$label, $cls] = $map[$status] ?? [$status, 'muted'];
    return '<span class="badge badge-' . $cls . '">' . e($label) . '</span>';
}

function notify($userId, $title, $message) {
    $st = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)');
    $st->execute([$userId, $title, $message]);
}
function adminLog($action, $description) {
    $st = db()->prepare('INSERT INTO admin_logs (admin_id, action, description) VALUES (?,?,?)');
    $st->execute([$_SESSION['user_id'] ?? null, $action, $description]);
}
function setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try { foreach (db()->query('SELECT setting_key, setting_value FROM website_settings') as $r) $cache[$r['setting_key']] = $r['setting_value']; }
        catch (Throwable $e) { logError('setting', $e); }
    }
    return $cache[$key] ?? $default;
}
function ensureBalance($storeId) {
    db()->prepare('INSERT IGNORE INTO seller_balances (store_id) VALUES (?)')->execute([$storeId]);
}
/** Catat mutasi saldo */
function ledger($storeId, $orderId, $withdrawalId, $type, $amount, $before, $after, $desc) {
    $st = db()->prepare('INSERT INTO balance_transactions (store_id, order_id, withdrawal_id, type, amount, balance_before, balance_after, description) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$storeId, $orderId, $withdrawalId, $type, $amount, $before, $after, $desc]);
}

/** Upload gambar aman. Return [namaFile|null, pesanError|null] */
function uploadImage($file, $subdir) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Upload gagal. Coba lagi.'];
    if ($file['size'] > MAX_UPLOAD_BYTES) return [null, 'Ukuran gambar maksimal 2 MB.'];
    if (!is_uploaded_file($file['tmp_name'])) return [null, 'File tidak valid.'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);   // cek MIME asli, bukan ekstensi
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) return [null, 'Format gambar harus JPG, JPEG, PNG, atau WEBP.'];
    $dir = ROOT_PATH . '/uploads/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return [null, 'Gagal menyimpan gambar.'];
    return [$name, null];
}
function deleteUpload($subdir, $name) {
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) @unlink(ROOT_PATH . '/uploads/' . $subdir . '/' . $name);
}

/* ---------- Produk ---------- */
function productSelect() {
    return "SELECT p.*, s.name AS store_name, s.city AS store_city, s.address AS store_address, s.user_id AS owner_id,
        c.name AS category_name, c.icon AS icon,
        (SELECT filename FROM product_images pi WHERE pi.product_id = p.id ORDER BY is_primary DESC, id LIMIT 1) AS image,
        (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.product_id = p.id) AS avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
        FROM products p JOIN stores s ON s.id = p.store_id JOIN categories c ON c.id = p.category_id ";
}
function productImg($p, $class = '') {
    if (!empty($p['image'])) return '<img class="' . $class . '" src="' . e(url('uploads/products/' . $p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy">';
    return '<div class="ph ' . $class . '">' . e($p['icon'] ?? '📦') . '</div>';
}
function productCard($p) {
    ob_start(); ?>
    <a class="pcard" href="<?= e(url('buyer/product-detail.php?id=' . $p['id'])) ?>">
        <div class="pcard-img"><?= productImg($p) ?></div>
        <div class="pcard-body">
            <div class="pcard-name"><?= e($p['name']) ?></div>
            <div class="pcard-price"><?= rupiah($p['price']) ?></div>
            <div class="pcard-meta">★ <?= $p['avg_rating'] ? e($p['avg_rating']) : '-' ?> · <?= e($p['item_condition']) ?></div>
            <div class="pcard-store">📍 <?= e($p['store_name']) ?> · <?= e($p['store_city']) ?></div>
        </div>
    </a>
    <?php return ob_get_clean();
}

/* ---------- Keranjang ---------- */
function getCartId($userId) {
    $st = db()->prepare('SELECT id FROM carts WHERE user_id = ?');
    $st->execute([$userId]);
    $id = $st->fetchColumn();
    if (!$id) { db()->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute([$userId]); $id = db()->lastInsertId(); }
    return (int)$id;
}
function cartItems($userId) {
    $sql = productSelect() . ' JOIN cart_items ci ON ci.product_id = p.id JOIN carts ca ON ca.id = ci.cart_id WHERE ca.user_id = ? ORDER BY ci.id';
    $sql = str_replace('SELECT p.*,', 'SELECT ci.quantity AS qty, ci.id AS cart_item_id, p.*,', $sql);
    $st = db()->prepare($sql);
    $st->execute([$userId]);
    return $st->fetchAll();
}
function cartCount($userId) {
    $st = db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

/**
 * Validasi apakah user boleh membeli produk (dipakai: tambah cart & beli sekarang).
 * Return pesan error atau null jika valid. Validasi self-purchase ada di sini (backend).
 */
function validatePurchase($userId, $productId, $qty) {
    $st = db()->prepare('SELECT p.*, s.user_id AS owner_id, s.status AS store_status FROM products p JOIN stores s ON s.id = p.store_id WHERE p.id = ?');
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p || $p['status'] !== 'active' || $p['store_status'] !== 'active') return 'Produk tidak tersedia.';
    if ((int)$p['owner_id'] === (int)$userId) return 'Anda tidak dapat membeli produk dari toko Anda sendiri.';
    if ($qty < 1) return 'Jumlah tidak valid.';
    if ($p['stock'] < $qty) return 'Stok tidak mencukupi (tersisa ' . $p['stock'] . ').';
    return null;
}
