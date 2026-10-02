<?php if ($layout === 'site'): ?>
</main>
<footer class="footer">
  <div class="container footer-grid">
    <div><strong>🎓 KampusMart</strong><p><?= e(setting('site_description', 'Marketplace khusus lingkungan kampus.')) ?></p></div>
    <div><strong>Kontak</strong><p><?= nl2br(e(setting('contact_info', 'admin@kampusmart.test'))) ?></p></div>
    <div><strong>Informasi</strong><p><?= nl2br(e(setting('policy', 'Transaksi aman dengan dana ditahan sampai barang diterima.'))) ?></p></div>
  </div>
  <div class="footer-bottom">© <?= date('Y') ?> KampusMart. Proyek simulasi kampus.</div>
</footer>
<?php elseif ($layout === 'seller' || $layout === 'admin'): ?>
    </div></div></div>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
