/* KampusMart - Vanilla JS */
document.addEventListener('DOMContentLoaded', function () {
  // Menu mobile (navbar / sidebar)
  var menuBtn = document.getElementById('menuBtn');
  if (menuBtn) menuBtn.addEventListener('click', function () {
    var t = document.getElementById('sidebar') || document.getElementById('navLinks');
    if (t) t.classList.toggle('open');
  });
  // Toast hilang otomatis
  document.querySelectorAll('.toast').forEach(function (t) {
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 4500);
    t.addEventListener('click', function () { t.remove(); });
  });
  // Toggle password
  document.querySelectorAll('[data-toggle-pass]').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = b.parentElement.querySelector('input');
      i.type = i.type === 'password' ? 'text' : 'password';
    });
  });
  // Konfirmasi hapus / aksi penting
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  // Modal
  document.querySelectorAll('[data-modal-open]').forEach(function (b) {
    b.addEventListener('click', function () { document.getElementById(b.dataset.modalOpen).classList.add('open'); });
  });
  document.querySelectorAll('.modal').forEach(function (m) {
    m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-modal-close')) m.classList.remove('open'); });
  });
  // Preview gambar
  document.querySelectorAll('input[data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var img = document.getElementById(inp.dataset.preview);
      if (inp.files[0] && img) { img.src = URL.createObjectURL(inp.files[0]); img.style.display = 'block'; }
    });
  });
  // Tombol quantity (+/-) ; data-autosubmit => kirim form otomatis (keranjang)
  document.querySelectorAll('.qty').forEach(function (q) {
    var input = q.querySelector('input'), max = parseInt(input.max || '999', 10);
    q.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        var v = parseInt(input.value || '1', 10) + (b.dataset.d === '+' ? 1 : -1);
        input.value = Math.max(1, Math.min(max, v));
        if (input.dataset.autosubmit) input.form.submit();
      });
    });
  });
  // Scanner QR (BarcodeDetector bawaan browser; jika tidak ada -> input manual)
  var video = document.getElementById('scanVideo'), tokenInput = document.getElementById('tokenInput');
  var startBtn = document.getElementById('startScan'), note = document.getElementById('scanNote');
  if (startBtn) {
    if (!('BarcodeDetector' in window) || !navigator.mediaDevices) {
      startBtn.style.display = 'none';
      if (note) note.textContent = 'Kamera/scanner tidak didukung browser ini. Gunakan input token manual di bawah.';
    } else {
      startBtn.addEventListener('click', async function () {
        try {
          var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
          video.srcObject = stream; video.style.display = 'block'; await video.play();
          var det = new BarcodeDetector({ formats: ['qr_code'] });
          var timer = setInterval(async function () {
            var codes = await det.detect(video);
            if (codes.length) {
              clearInterval(timer); stream.getTracks().forEach(function (t) { t.stop(); });
              tokenInput.value = codes[0].rawValue; tokenInput.form.submit();
            }
          }, 500);
        } catch (err) { if (note) note.textContent = 'Kamera tidak dapat dibuka. Gunakan input token manual.'; }
      });
    }
  }
});
