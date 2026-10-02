# KampusMart

Marketplace khusus lingkungan kampus (buku bekas, jas lab, alat praktikum, kalkulator, perlengkapan kos, dll.).
Dibuat dengan **PHP Native + MySQL (PDO) + HTML/CSS/Vanilla JS**, tanpa framework.

## Fitur
- **Satu akun, dua mode**: register → pembeli. "Buka Toko" → disetujui admin → `seller_enabled = 1` → bisa "Beralih ke Toko" tanpa login ulang.
- Pencarian & filter produk (kategori, harga, lokasi, kondisi, rating, toko) + sorting, semua memakai prepared statement.
- Keranjang per **user** (`carts.user_id`), checkout, pembayaran simulasi, **escrow (dana HELD)**, **QR serah terima**, saldo & penarikan dana.
- Validasi **self-purchase** di backend PHP (tambah cart, beli sekarang, checkout).
- Review hanya untuk pembeli yang pesanannya sudah selesai; penjual bisa membalas.
- Panel admin: pengguna, toko (approve/reject/suspend), kategori, transaksi, penarikan, pengaturan website, log.
- Keamanan: `password_hash`, PDO prepared statements, CSRF token, `session_regenerate_id(true)`, `htmlspecialchars`, validasi upload (MIME + ukuran + nama acak), kontrol akses per peran, error dicatat ke `logs/app.log`.

## Struktur folder
```
kampusmart/
├── index.php, login.php, register.php, logout.php
├── config/        config.php, database.php
├── includes/      header, footer, navbar, functions, auth*, csrf, services (logika transaksi), product_form/validate
├── assets/        css/ (style, auth, buyer, seller, admin), js/app.js
├── uploads/       products/ stores/ users/   (script PHP diblokir lewat .htaccess)
├── logs/app.log
├── buyer/  seller/  admin/
└── database/kampusmart.sql
```

## Instalasi

### WAMPP
1. Install WAMPP.
2. Salin folder `kampusmart` ke `C:\Wampp\www\kampusmart\`.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/kampusmart.sql` → **Go**.
   (File SQL sudah berisi `CREATE DATABASE kampusmart`, jadi tidak perlu membuat database manual.)
4. Cek `config/database.php` (default: host `localhost`, user `root`, password kosong).
5. Buka `http://localhost/kampusmart/`.

### Laragon
1. Salin folder ke `C:\laragon\www\kampusmart\`, klik **Start All**.
2. Import SQL lewat HeidiSQL/phpMyAdmin (sama seperti di atas).
3. Buka `http://localhost/kampusmart/` (atau `http://kampusmart.test` jika auto virtual host aktif).

### Membuat database via terminal (opsional)
```
mysql -u root -p < database/kampusmart.sql
```

## Akun demo
| Peran | Email | Password |
|---|---|---|
| Admin | admin@kampusmart.test | Admin123! |
| Pembeli | buyer@kampusmart.test | Buyer123! |
| Penjual (juga bisa belanja) | seller@kampusmart.test | Seller123! |

## Alur pembayaran (escrow)
1. Pembeli checkout → order `pending_payment`, payment `pending`, stok dikurangi (dengan `SELECT ... FOR UPDATE`).
2. Di `payment.php` klik **Simulasikan Pembayaran** → payment `held`, order `ready_for_handover`.
3. Dana masuk `seller_balances.held_balance` (bukan available) dan dicatat di `balance_transactions` (type `hold`).
4. Token acak `bin2hex(random_bytes(32))` dibuat di `handover_tokens`.

## Alur QR serah terima
- Pembeli membuka **QR Serah Terima** (`buyer/handover.php`). Isi QR: `KAMPUSMART-HANDOVER-{TOKEN}`.
- Penjual membuka **Scan QR** (`seller/scan-handover.php`): kamera (jika browser mendukung `BarcodeDetector`, mis. Chrome/Edge Android & desktop) atau **input token manual**.
- Backend memverifikasi: token valid, belum dipakai, belum kedaluwarsa (7 hari), milik toko penjual, payment `held`, order belum selesai, toko aktif, saldo tertahan cukup.
- Lalu dalam satu transaksi: token `used`, `held_balance -= amount`, `available_balance += amount`, log saldo & payment, order `completed` + payment `released` (jika semua toko sudah serah terima).
- **Catatan gambar QR**: gambar dibuat lewat layanan `api.qrserver.com` (butuh internet). Tanpa internet, halaman menampilkan **kode teks** yang bisa diinput manual oleh penjual.

## Multi-toko (implementasi)
Satu keranjang/order boleh berisi produk beberapa toko. Pilihan yang paling sederhana tetapi benar:
- `orders` = order induk milik pembeli; `order_items.store_id` menandai toko pemilik item.
- Saat pembayaran, sistem membuat **satu `handover_tokens` per toko** (kolom `seller_id` = id toko, kolom `amount` = subtotal toko itu) dan menahan dana **per toko**.
- Penjual hanya bisa memverifikasi token tokonya sendiri, sehingga hanya bagian dananya yang dilepas.
- Order menjadi `processing` setelah sebagian toko selesai, dan `completed` (payment `released`) setelah **semua** token `used`.

## Alur withdrawal
1. Penjual mengajukan penarikan (`seller/withdrawals.php`): saldo tersedia **langsung dikurangi** dalam transaksi (dikunci `FOR UPDATE`) sehingga tidak bisa ditarik dua kali.
2. Admin **Approve** → `approved` → **Tandai Dibayar** → `paid`; atau **Reject** → saldo dikembalikan (log `refund`).
3. Aksi hanya sah dari status `pending`/`approved`, jadi tidak bisa diproses ganda.

## Penjelasan peran
- **Pembeli**: cari produk, keranjang, checkout, bayar (simulasi), lihat QR, riwayat, profil, alamat, buka toko, beri ulasan.
- **Penjual**: dashboard, CRUD produk, pesanan masuk, riwayat penjualan, saldo, penarikan, pengaturan toko, ulasan, scan QR.
- **Admin**: dashboard, kelola pengguna/toko/kategori, pantau transaksi, setujui penarikan, pengaturan website, log.

## Catatan teknis
- Kolom `products.condition` dinamai **`item_condition`** karena `CONDITION` adalah kata cadangan MySQL.
- Status penarikan tambahan tidak ada; `paid` di penarikan berarti "sudah ditransfer".
- Mengubah `APP_ENV` di `config/config.php` menjadi `production` mematikan `display_errors`.

## Troubleshooting
- **Halaman kosong / error database**: pastikan MySQL menyala dan SQL sudah diimport; cek `config/database.php` dan `logs/app.log`.
- **Login gagal**: pastikan memakai akun demo persis (huruf besar/kecil) dan tabel `users` terisi.
- **Upload gagal**: pastikan folder `uploads/` dapat ditulis; ukuran maksimal 2 MB; cek `upload_max_filesize` di php.ini.
- **CSS tidak muncul**: akses lewat `http://localhost/kampusmart/`, bukan membuka file langsung. `BASE_URL` dideteksi otomatis.
- **Kamera scan tidak jalan**: kamera browser butuh HTTPS atau `localhost`; gunakan input token manual sebagai alternatif.
- **QR tidak tampil**: butuh internet untuk gambar QR; gunakan kode teks di bawahnya.
