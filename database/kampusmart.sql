-- KampusMart - skema database + data demo
CREATE DATABASE IF NOT EXISTS kampusmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kampusmart;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','buyer') NOT NULL DEFAULT 'buyer',
  seller_enabled TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  label VARCHAR(50) NOT NULL,
  recipient VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(80) NOT NULL,
  province VARCHAR(80) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_addr_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(80) NOT NULL,
  province VARCHAR(80) NOT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_status (status),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  icon VARCHAR(10) DEFAULT '📦'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catatan: kolom "condition" adalah kata cadangan MySQL, jadi dinamai item_condition
CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  item_condition ENUM('Baru','Bekas') NOT NULL DEFAULT 'Bekas',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_prod_store (store_id), INDEX idx_prod_cat (category_id), INDEX idx_prod_name (name), INDEX idx_prod_status (status),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_img_prod (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  order_number VARCHAR(30) NOT NULL UNIQUE,
  address_id INT UNSIGNED DEFAULT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  payment_status ENUM('pending','paid','held','released','failed','refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('pending_payment','paid','processing','ready_for_handover','completed','cancelled','refunded') NOT NULL DEFAULT 'pending_payment',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order_user (user_id), INDEX idx_order_status (order_status),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (address_id) REFERENCES user_addresses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  store_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  quantity INT NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  INDEX idx_oi_order (order_id), INDEX idx_oi_store (store_id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','paid','held','released','failed','refunded') NOT NULL DEFAULT 'pending',
  payment_method VARCHAR(50) DEFAULT NULL,
  transaction_reference VARCHAR(50) DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pay_status (status),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id INT UNSIGNED NOT NULL,
  type VARCHAR(30) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  reference VARCHAR(50) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE seller_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id INT UNSIGNED NOT NULL UNIQUE,
  available_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  held_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE withdrawals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id INT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  bank_name VARCHAR(60) NOT NULL,
  account_number VARCHAR(30) NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME DEFAULT NULL,
  INDEX idx_wd_status (status),
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE balance_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  withdrawal_id INT UNSIGNED DEFAULT NULL,
  type ENUM('hold','release','withdrawal','refund','adjustment') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  balance_before DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bt_store (store_id),
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (product_id, user_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE review_replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  review_id INT UNSIGNED NOT NULL UNIQUE,
  store_id INT UNSIGNED NOT NULL,
  reply TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seller_id = id toko pemilik item (satu token per toko per order)
CREATE TABLE handover_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  seller_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  amount DECIMAL(14,2) NOT NULL,
  status ENUM('active','used','expired','cancelled') NOT NULL DEFAULT 'active',
  expires_at DATETIME DEFAULT NULL,
  scanned_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ht_order (order_id), INDEX idx_ht_seller (seller_id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE website_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_admin (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ================= DATA DEMO =================
INSERT INTO categories (id, name, icon) VALUES
 (1,'Buku','📚'),(2,'Jas Lab','🥼'),(3,'Alat Praktikum','🧪'),(4,'Kalkulator','🧮'),
 (5,'Perlengkapan Kos','🪑'),(6,'Elektronik','🔌'),(7,'Alat Tulis','✏️');

-- Password (bcrypt via password_hash): Admin123! / Buyer123! / Seller123!
INSERT INTO users (id, name, email, phone, address, password, role, seller_enabled, status) VALUES
 (1,'Administrator','admin@kampusmart.test','081200000001','Kampus Pusat','$2y$10$pu.EznMfxQKNq0p359GfFe2LJpGgjBtTV9KyEL3/bbGaQfkNnyr7K','admin',0,'active'),
 (2,'Andi Pratama','buyer@kampusmart.test','081200000002','Jl. Melati No. 12, Kampus A','$2y$10$fX2wrNSd3W.P5/E2RvLhxugpcayefd7uj9l0jo4KGaEPoIJZ0e3Iq','buyer',0,'active'),
 (3,'Sari Penjual','seller@kampusmart.test','081200000003','Jl. Mawar No. 5, Kampus B','$2y$10$P47afID0FcHlijLmMZyuKeKRkMlBnlGjJjIYkv6l2PQc4dJ6qKmpu','buyer',1,'active');

INSERT INTO user_addresses (user_id, label, recipient, phone, address, city, province, is_default) VALUES
 (2,'Kos','Andi Pratama','081200000002','Jl. Melati No. 12, Kampus A','Mataram','Nusa Tenggara Barat',1),
 (3,'Rumah','Sari Penjual','081200000003','Jl. Mawar No. 5, Kampus B','Mataram','Nusa Tenggara Barat',1);

INSERT INTO stores (id, user_id, name, description, phone, address, city, province, status) VALUES
 (1,3,'Toko Buku Kita','Menjual buku bekas, alat praktikum, dan perlengkapan kampus.','081200000003','Jl. Mawar No. 5, Kampus B','Mataram','Nusa Tenggara Barat','active');
INSERT INTO seller_balances (store_id) VALUES (1);

INSERT INTO products (store_id, category_id, name, description, price, stock, item_condition) VALUES
 (1,1,'Buku Fisika Dasar','Buku fisika dasar kondisi masih bagus, tanpa coretan. Cocok untuk semester 1-2.',35000,10,'Bekas'),
 (1,2,'Jas Lab Kimia','Jas lab putih ukuran L, bersih dan siap pakai.',75000,5,'Bekas'),
 (1,4,'Kalkulator Casio','Kalkulator scientific Casio, semua fungsi normal.',100000,6,'Bekas'),
 (1,5,'Lampu Belajar','Lampu belajar LED dengan kabel USB.',50000,12,'Baru'),
 (1,5,'Kursi Kos','Kursi plastik kuat untuk kamar kos.',60000,8,'Bekas');

INSERT INTO website_settings (setting_key, setting_value) VALUES
 ('site_title','KampusMart'),
 ('site_description','Marketplace khusus lingkungan kampus: jual beli barang mahasiswa dengan aman.'),
 ('banner_text','Temukan Barang Kampus yang Kamu Butuhkan'),
 ('policy','Dana pembeli ditahan (escrow) sampai barang diserahkan dan QR diverifikasi penjual.'),
 ('terms','Pengguna wajib memberikan data yang benar. Dilarang menjual barang ilegal atau berbahaya.'),
 ('contact_info','Email: admin@kampusmart.test\nWA: 0812-0000-0001');
