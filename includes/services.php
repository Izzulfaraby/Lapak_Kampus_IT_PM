<?php
/**
 * Logika bisnis yang memakai TRANSAKSI database:
 * checkout, pembayaran simulasi, pembatalan, handover QR (release dana), withdrawal.
 */

/** Membuat order dari daftar [['product_id'=>, 'qty'=>], ...]. Return [orderId|null, error|null] */
function createOrder($userId, $addressId, array $items) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        if (!$items) throw new RuntimeException('Keranjang masih kosong.');
        $st = $pdo->prepare('SELECT id FROM user_addresses WHERE id = ? AND user_id = ?');
        $st->execute([$addressId, $userId]);
        if (!$st->fetchColumn()) throw new RuntimeException('Pilih alamat pengiriman/serah terima terlebih dahulu.');

        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']); // urutan tetap -> hindari deadlock
        $lines = []; $total = 0;
        foreach ($items as $it) {
            // Kunci baris produk agar stok tidak bisa dibeli dua kali bersamaan
            $st = $pdo->prepare('SELECT p.*, s.user_id AS owner_id, s.status AS store_status FROM products p JOIN stores s ON s.id = p.store_id WHERE p.id = ? FOR UPDATE');
            $st->execute([$it['product_id']]);
            $p = $st->fetch();
            $qty = (int)$it['qty'];
            if (!$p || $p['status'] !== 'active' || $p['store_status'] !== 'active') throw new RuntimeException('Ada produk yang sudah tidak tersedia.');
            if ((int)$p['owner_id'] === (int)$userId) throw new RuntimeException('Anda tidak dapat membeli produk dari toko Anda sendiri.');
            if ($qty < 1 || $p['stock'] < $qty) throw new RuntimeException('Stok "' . $p['name'] . '" tidak mencukupi.');
            $lines[] = ['p' => $p, 'qty' => $qty, 'sub' => $p['price'] * $qty];
            $total += $p['price'] * $qty;
        }
        $orderNo = 'KM' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $pdo->prepare('INSERT INTO orders (user_id, order_number, address_id, total_amount) VALUES (?,?,?,?)')
            ->execute([$userId, $orderNo, $addressId, $total]);
        $orderId = (int)$pdo->lastInsertId();
        foreach ($lines as $l) {
            $p = $l['p'];
            // Snapshot nama & harga agar histori tidak berubah
            $pdo->prepare('INSERT INTO order_items (order_id, product_id, store_id, product_name, price, quantity, subtotal) VALUES (?,?,?,?,?,?,?)')
                ->execute([$orderId, $p['id'], $p['store_id'], $p['name'], $p['price'], $l['qty'], $l['sub']]);
            $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?')->execute([$l['qty'], $p['id'], $l['qty']]);
        }
        $pdo->prepare('INSERT INTO payments (order_id, user_id, amount, status) VALUES (?,?,?,"pending")')->execute([$orderId, $userId, $total]);
        $pdo->commit();
        return [$orderId, null];
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return [null, $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('createOrder', $e);
        return [null, 'Terjadi kesalahan sistem. Silakan coba lagi.'];
    }
}

/** Simulasi pembayaran: dana masuk HELD, buat token QR per toko. Return pesan error atau null */
function simulatePayment($orderId, $userId, $method) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
        $st->execute([$orderId, $userId]);
        $order = $st->fetch();
        if (!$order || $order['order_status'] !== 'pending_payment') throw new RuntimeException('Pesanan tidak dapat dibayar.');
        $st = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $pay = $st->fetch();
        if (!$pay || $pay['status'] !== 'pending') throw new RuntimeException('Pembayaran sudah diproses.');

        $ref = 'SIM-' . strtoupper(bin2hex(random_bytes(5)));
        $pdo->prepare('UPDATE payments SET status="held", payment_method=?, transaction_reference=?, paid_at=NOW() WHERE id=?')->execute([$method, $ref, $pay['id']]);
        $ins = $pdo->prepare('INSERT INTO payment_transactions (payment_id, type, amount, reference, description) VALUES (?,?,?,?,?)');
        $ins->execute([$pay['id'], 'payment', $order['total_amount'], $ref, 'Pembayaran simulasi via ' . $method]);
        $ins->execute([$pay['id'], 'hold', $order['total_amount'], $ref, 'Dana ditahan (escrow) sampai serah terima']);
        $pdo->prepare('UPDATE orders SET payment_status="held", order_status="ready_for_handover" WHERE id=?')->execute([$orderId]);

        // Order multi-toko: satu token + satu bagian dana HELD per toko
        $st = $pdo->prepare('SELECT oi.store_id, SUM(oi.subtotal) AS amount, s.user_id AS owner_id FROM order_items oi JOIN stores s ON s.id = oi.store_id WHERE oi.order_id = ? GROUP BY oi.store_id, s.user_id');
        $st->execute([$orderId]);
        foreach ($st->fetchAll() as $row) {
            ensureBalance($row['store_id']);
            $b = $pdo->prepare('SELECT * FROM seller_balances WHERE store_id = ? FOR UPDATE');
            $b->execute([$row['store_id']]);
            $bal = $b->fetch();
            $newHeld = $bal['held_balance'] + $row['amount'];
            $pdo->prepare('UPDATE seller_balances SET held_balance = ? WHERE store_id = ?')->execute([$newHeld, $row['store_id']]);
            ledger($row['store_id'], $orderId, null, 'hold', $row['amount'], $bal['held_balance'], $newHeld, 'Dana ditahan untuk order ' . $order['order_number']);
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO handover_tokens (order_id, seller_id, token, amount, status, expires_at) VALUES (?,?,?,?,"active", DATE_ADD(NOW(), INTERVAL ' . (int)HANDOVER_EXPIRE_DAYS . ' DAY))')
                ->execute([$orderId, $row['store_id'], $token, $row['amount']]);
            notify($row['owner_id'], 'Pesanan siap diserahkan', 'Order ' . $order['order_number'] . ' sudah dibayar. Siapkan barang untuk serah terima.');
        }
        notify($userId, 'Pembayaran berhasil', 'Pembayaran order ' . $order['order_number'] . ' berhasil. Tunjukkan QR Serah Terima ke penjual.');
        $pdo->commit();
        return null;
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('simulatePayment', $e);
        return 'Terjadi kesalahan sistem.';
    }
}

/** Batalkan order yang belum dibayar, kembalikan stok */
function cancelOrder($orderId, $userId) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
        $st->execute([$orderId, $userId]);
        $o = $st->fetch();
        if (!$o || $o['order_status'] !== 'pending_payment') throw new RuntimeException('Pesanan tidak dapat dibatalkan.');
        $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $items->execute([$orderId]);
        foreach ($items->fetchAll() as $i) $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$i['quantity'], $i['product_id']]);
        $pdo->prepare('UPDATE orders SET order_status="cancelled", payment_status="failed" WHERE id=?')->execute([$orderId]);
        $pdo->prepare('UPDATE payments SET status="failed" WHERE order_id=?')->execute([$orderId]);
        $pdo->commit();
        return null;
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('cancelOrder', $e);
        return 'Terjadi kesalahan sistem.';
    }
}

/** Verifikasi QR oleh penjual lalu release dana. Return [ok(bool), pesan] */
function processHandover($storeId, $raw) {
    $raw = trim((string)$raw);
    $token = preg_replace('/^KAMPUSMART-HANDOVER-/i', '', $raw);
    if (!preg_match('/^[a-f0-9]{64}$/i', $token)) return [false, 'Format token tidak valid.'];
    $token = strtolower($token);
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM handover_tokens WHERE token = ? FOR UPDATE');
        $st->execute([$token]);
        $t = $st->fetch();
        if (!$t) throw new RuntimeException('Token tidak ditemukan.');
        if ((int)$t['seller_id'] !== (int)$storeId) throw new RuntimeException('Token ini bukan milik toko Anda.');
        if ($t['status'] === 'used') throw new RuntimeException('Token sudah pernah digunakan.');
        if ($t['status'] !== 'active') throw new RuntimeException('Token tidak aktif.');
        if ($t['expires_at'] && strtotime($t['expires_at']) < time()) throw new RuntimeException('Token sudah kedaluwarsa.');

        $s = $pdo->prepare('SELECT * FROM stores WHERE id = ?'); $s->execute([$storeId]);
        $store = $s->fetch();
        if (!$store || $store['status'] !== 'active') throw new RuntimeException('Toko tidak aktif.');

        $o = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE'); $o->execute([$t['order_id']]);
        $order = $o->fetch();
        if (!$order || in_array($order['order_status'], ['completed', 'cancelled', 'refunded'], true)) throw new RuntimeException('Pesanan sudah selesai atau dibatalkan.');
        $p = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? FOR UPDATE'); $p->execute([$order['id']]);
        $pay = $p->fetch();
        if (!$pay || $pay['status'] !== 'held') throw new RuntimeException('Status pembayaran bukan HELD.');

        // Pastikan penjual benar-benar pemilik item pada order ini
        $c = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ? AND store_id = ?'); $c->execute([$order['id'], $storeId]);
        if (!$c->fetchColumn()) throw new RuntimeException('Anda bukan pemilik item pesanan ini.');

        $b = $pdo->prepare('SELECT * FROM seller_balances WHERE store_id = ? FOR UPDATE'); $b->execute([$storeId]);
        $bal = $b->fetch();
        $amount = $t['amount'];
        if (!$bal || $bal['held_balance'] < $amount) throw new RuntimeException('Saldo tertahan tidak mencukupi.');

        $pdo->prepare('UPDATE handover_tokens SET status="used", scanned_at=NOW() WHERE id=?')->execute([$t['id']]);
        $newHeld = $bal['held_balance'] - $amount;
        $newAvail = $bal['available_balance'] + $amount;
        $pdo->prepare('UPDATE seller_balances SET held_balance=?, available_balance=? WHERE store_id=?')->execute([$newHeld, $newAvail, $storeId]);
        ledger($storeId, $order['id'], null, 'release', $amount, $bal['available_balance'], $newAvail, 'Dana dilepas dari order ' . $order['order_number']);
        $pdo->prepare('INSERT INTO payment_transactions (payment_id, type, amount, reference, description) VALUES (?,?,?,?,?)')
            ->execute([$pay['id'], 'release', $amount, $pay['transaction_reference'], 'Release ke toko #' . $storeId]);

        $r = $pdo->prepare('SELECT COUNT(*) FROM handover_tokens WHERE order_id = ? AND status = "active"'); $r->execute([$order['id']]);
        if ((int)$r->fetchColumn() === 0) {   // semua toko sudah serah terima
            $pdo->prepare('UPDATE orders SET order_status="completed", payment_status="released" WHERE id=?')->execute([$order['id']]);
            $pdo->prepare('UPDATE payments SET status="released" WHERE id=?')->execute([$pay['id']]);
        } else {
            $pdo->prepare('UPDATE orders SET order_status="processing" WHERE id=?')->execute([$order['id']]);
        }
        notify($order['user_id'], 'Serah terima berhasil', 'Serah terima untuk order ' . $order['order_number'] . ' dari ' . $store['name'] . ' berhasil.');
        notify($store['user_id'], 'Dana masuk ke saldo', rupiah($amount) . ' dari order ' . $order['order_number'] . ' kini tersedia di saldo Anda.');
        $pdo->commit();
        return [true, 'Serah terima berhasil! ' . rupiah($amount) . ' masuk ke saldo tersedia.'];
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return [false, $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('processHandover', $e);
        return [false, 'Terjadi kesalahan sistem.'];
    }
}

/** Ajukan penarikan dana. Saldo langsung dikurangi (dikunci). Return pesan error atau null */
function requestWithdrawal($store, $amount, $bank, $number, $holder) {
    if ($amount <= 0) return 'Nominal harus lebih dari 0.';
    if ($bank === '' || $holder === '' || !preg_match('/^[0-9]{6,20}$/', $number)) return 'Data rekening tidak valid.';
    $pdo = db();
    try {
        $pdo->beginTransaction();
        ensureBalance($store['id']);
        $b = $pdo->prepare('SELECT * FROM seller_balances WHERE store_id = ? FOR UPDATE'); $b->execute([$store['id']]);
        $bal = $b->fetch();
        if ($amount > $bal['available_balance']) throw new RuntimeException('Nominal melebihi saldo tersedia.');
        $pdo->prepare('INSERT INTO withdrawals (store_id, amount, bank_name, account_number, account_name) VALUES (?,?,?,?,?)')
            ->execute([$store['id'], $amount, $bank, $number, $holder]);
        $wid = $pdo->lastInsertId();
        $new = $bal['available_balance'] - $amount;
        $pdo->prepare('UPDATE seller_balances SET available_balance=? WHERE store_id=?')->execute([$new, $store['id']]);
        ledger($store['id'], null, $wid, 'withdrawal', $amount, $bal['available_balance'], $new, 'Pengajuan penarikan #' . $wid);
        $pdo->commit();
        return null;
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('requestWithdrawal', $e);
        return 'Terjadi kesalahan sistem.';
    }
}

/** Aksi admin pada withdrawal: approve | reject | paid. Return [ok, pesan] */
function adminWithdrawalAction($id, $action) {
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT w.*, s.user_id AS owner_id FROM withdrawals w JOIN stores s ON s.id = w.store_id WHERE w.id = ? FOR UPDATE');
        $st->execute([$id]);
        $w = $st->fetch();
        if (!$w) throw new RuntimeException('Data tidak ditemukan.');
        if ($action === 'approve' && $w['status'] === 'pending') {
            $pdo->prepare('UPDATE withdrawals SET status="approved", processed_at=NOW() WHERE id=?')->execute([$id]);
            notify($w['owner_id'], 'Withdrawal disetujui', 'Penarikan ' . rupiah($w['amount']) . ' disetujui admin.');
            adminLog('Approve withdrawal', 'Withdrawal #' . $id . ' ' . rupiah($w['amount']));
        } elseif ($action === 'reject' && $w['status'] === 'pending') {
            $b = $pdo->prepare('SELECT * FROM seller_balances WHERE store_id = ? FOR UPDATE'); $b->execute([$w['store_id']]);
            $bal = $b->fetch();
            $new = $bal['available_balance'] + $w['amount'];
            $pdo->prepare('UPDATE seller_balances SET available_balance=? WHERE store_id=?')->execute([$new, $w['store_id']]);
            ledger($w['store_id'], null, $id, 'refund', $w['amount'], $bal['available_balance'], $new, 'Withdrawal #' . $id . ' ditolak, dana dikembalikan');
            $pdo->prepare('UPDATE withdrawals SET status="rejected", processed_at=NOW() WHERE id=?')->execute([$id]);
            notify($w['owner_id'], 'Withdrawal ditolak', 'Penarikan ' . rupiah($w['amount']) . ' ditolak. Dana dikembalikan ke saldo.');
            adminLog('Reject withdrawal', 'Withdrawal #' . $id . ' ' . rupiah($w['amount']));
        } elseif ($action === 'paid' && $w['status'] === 'approved') {
            $pdo->prepare('UPDATE withdrawals SET status="paid", processed_at=NOW() WHERE id=?')->execute([$id]);
            adminLog('Withdrawal dibayar', 'Withdrawal #' . $id . ' ditandai sudah ditransfer');
        } else {
            throw new RuntimeException('Aksi tidak valid untuk status saat ini (mencegah proses ganda).');
        }
        $pdo->commit();
        return [true, 'Berhasil diproses.'];
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return [false, $e->getMessage()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logError('adminWithdrawalAction', $e);
        return [false, 'Terjadi kesalahan sistem.'];
    }
}
