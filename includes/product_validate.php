<?php
/** Validasi input produk; return [data|null, errors[]] */
function validateProductInput() {
    $err = [];
    $d = ['name' => trim($_POST['name'] ?? ''), 'category_id' => (int)($_POST['category_id'] ?? 0), 'price' => $_POST['price'] ?? '',
          'stock' => $_POST['stock'] ?? '', 'item_condition' => $_POST['item_condition'] ?? '', 'status' => $_POST['status'] ?? 'active', 'description' => trim($_POST['description'] ?? '')];
    if ($d['name'] === '') $err[] = 'Nama produk wajib diisi.';
    if (!$d['category_id']) $err[] = 'Kategori wajib dipilih.';
    if (!is_numeric($d['price']) || $d['price'] <= 0) $err[] = 'Harga harus angka dan lebih dari 0.';
    if (!ctype_digit((string)$d['stock'])) $err[] = 'Stok harus angka >= 0.';
    if (!in_array($d['item_condition'], ['Baru', 'Bekas'], true)) $err[] = 'Kondisi tidak valid.';
    if (!in_array($d['status'], ['active', 'inactive'], true)) $d['status'] = 'active';
    return [$err ? null : $d, $err];
}
