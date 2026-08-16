document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('cardForm');
  if (!form) return;

  // ---------- tab switching ----------
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.et'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.etab'));
  var prevBtn = document.getElementById('formPrevBtn');
  var nextBtn = document.getElementById('formNextBtn');
  var saveBtn = document.getElementById('formSaveBtn');
  var currentIdx = 0;

  function showTab(idx) {
    if (idx < 0 || idx >= tabs.length) return;
    currentIdx = idx;
    tabs.forEach(function (t, i) {
      t.classList.toggle('on', i === idx);
      t.setAttribute('aria-selected', i === idx ? 'true' : 'false');
    });
    panels.forEach(function (p) { p.hidden = true; });
    var panel = form.querySelector('.etab[data-panel="' + tabs[idx].getAttribute('data-tab') + '"]');
    if (panel) panel.hidden = false;
    var isLast = idx === tabs.length - 1;
    if (nextBtn) nextBtn.hidden = isLast;
    // Save button is always visible in the sticky save bar.
    if (prevBtn) prevBtn.hidden = idx === 0;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  tabs.forEach(function (tab, i) {
    tab.addEventListener('click', function () { showTab(i); });
    tab.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { showTab(Math.min(tabs.length - 1, i + 1)); tabs[i + 1] && tabs[i + 1].focus(); }
      if (e.key === 'ArrowRight') { showTab(Math.max(0, i - 1)); tabs[i - 1] && tabs[i - 1].focus(); }
    });
  });
  if (nextBtn) nextBtn.addEventListener('click', function () {
    if (!form.checkValidity()) { form.reportValidity(); return; }
    showTab(currentIdx + 1);
  });
  if (prevBtn) prevBtn.addEventListener('click', function () { showTab(currentIdx - 1); });
  showTab(0);

  // ---------- QR live preview ----------
  var preview = document.getElementById('qrPreview');
  if (preview) {
    var base = window.VC_QR_BASE || '';
    var cardUrl = window.VC_CARD_URL || '';
    var logoChk = document.getElementById('qrLogoChk');

    function qrParams(png) {
      var themeInp = form.querySelector('input[name="qr_theme"]:checked');
      var dotsInp = form.querySelector('input[name="qr_dots"]:checked');
      var theme = themeInp ? themeInp.value : 'classic';
      var dots = dotsInp ? dotsInp.value : 'square';
      var logo = logoChk && logoChk.checked ? '1' : '0';
      var p = 'data=' + encodeURIComponent(cardUrl) + '&theme=' + encodeURIComponent(theme) + '&dots=' + encodeURIComponent(dots) + '&logo=' + logo;
      if (png) p += '&px=14';
      return p;
    }
    function refreshPreview() {
      var p = qrParams(true);
      preview.src = base + '?' + p;
      var dlPng = document.getElementById('qrDlPng');
      var dlSvg = document.getElementById('qrDlSvg');
      if (dlPng) {
        dlPng.href = base + '?' + qrParams(false) + '&download=1&px=30';
        dlPng.setAttribute('download', 'qr-code.png');
      }
      if (dlSvg) {
        dlSvg.href = base + '?' + qrParams(false) + '&download=1&fmt=svg';
        dlSvg.setAttribute('download', 'qr-code.svg');
      }
    }
    form.addEventListener('change', function (e) {
      if (e.target && (e.target.name === 'qr_theme' || e.target.name === 'qr_dots' || e.target.id === 'qrLogoChk')) refreshPreview();
    });
    refreshPreview();
  }

  // ---------- custom fields ----------
  var cfRows = document.getElementById('cfRows');
  var cfAdd = document.getElementById('cfAdd');
  if (cfRows && cfAdd) {
    function esc(s) {
      return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function addRow(label, value) {
      var row = document.createElement('div');
      row.className = 'cf-row';
      row.innerHTML = '<input type="text" name="cf_label[]" placeholder="عنوان (مثلاً ساعات کاری)" value="' + esc(label) + '">'
        + '<input type="text" name="cf_value[]" placeholder="مقدار" value="' + esc(value) + '">'
        + '<button type="button" class="icon-btn icon-danger cf-remove" aria-label="حذف">×</button>';
      cfRows.appendChild(row);
    }
    cfAdd.addEventListener('click', function () { addRow('', ''); });
    cfRows.addEventListener('click', function (e) {
      var rm = e.target.closest('.cf-remove');
      if (rm) rm.closest('.cf-row').remove();
    });
  }

  // ---------- image upload preview + remove ----------
  document.querySelectorAll('.upload-box').forEach(function (box) {
    var input = box.querySelector('.ub-input');
    var btn = box.querySelector('.ub-btn');
    var previewEl = box.querySelector('.ub-preview');
    var rmInput = box.querySelector('.ub-rm');
    var rmBtn = box.querySelector('.ub-remove');
    var isLogo = box.getAttribute('data-for') === 'logo';
    if (!input || !btn) return;
    btn.addEventListener('click', function () { input.click(); });
    input.addEventListener('change', function () {
      var file = input.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (previewEl) {
          previewEl.innerHTML = '<img src="' + ev.target.result + '" alt="">';
          previewEl.classList.remove('empty');
        }
      };
      reader.readAsDataURL(file);
      if (rmInput) rmInput.value = '0';
      if (rmBtn) rmBtn.classList.add('hidden');
      if (isLogo) {
        var qrLogoChk = document.getElementById('qrLogoChk');
        if (qrLogoChk) qrLogoChk.disabled = false;
      }
    });
    if (rmBtn && rmInput) {
      rmBtn.addEventListener('click', function () {
        if (!window.confirm('این تصویر حذف شود؟')) return;
        rmInput.value = '1';
        input.value = '';
        if (previewEl) {
          previewEl.innerHTML = '';
          previewEl.classList.add('empty');
        }
        rmBtn.classList.add('hidden');
        if (isLogo) {
          var qrLogoChk = document.getElementById('qrLogoChk');
          if (qrLogoChk) { qrLogoChk.checked = false; qrLogoChk.disabled = true; }
        }
      });
    }
  });

  // ---------- get current location ----------
  var locBtn = document.getElementById('locBtn');
  if (locBtn) {
    var locIcon = locBtn.innerHTML;
    locBtn.addEventListener('click', function () {
      if (!navigator.geolocation) { alert('مرورگر شما موقعیت‌یابی را پشتیبانی نمی‌کند.'); return; }
      locBtn.disabled = true;
      locBtn.textContent = 'در حال دریافت...';
      navigator.geolocation.getCurrentPosition(function (pos) {
        var latInp = form.querySelector('input[name="map_lat"]');
        var lngInp = form.querySelector('input[name="map_lng"]');
        if (latInp) latInp.value = pos.coords.latitude.toFixed(7);
        if (lngInp) lngInp.value = pos.coords.longitude.toFixed(7);
        locBtn.disabled = false;
        locBtn.innerHTML = locIcon;
      }, function () {
        locBtn.disabled = false;
        locBtn.innerHTML = locIcon;
        alert('دریافت موقعیت ناموفق بود. لطفاً دسترسی مکان را فعال کنید.');
      }, { enableHighAccuracy: false, timeout: 10000 });
    });
  }

  // ---------- فعال/غیرفعال کردن کارت (با تأیید) ----------
  var activeChk = document.getElementById('activeChk');
  if (activeChk) {
    activeChk.addEventListener('change', function () {
      if (activeChk.checked) return;
      if (!window.confirm('کارت غیرفعال شود؟ وقتی غیرفعال باشد، لینک کوتاه و کد QR دیگر کار نمی‌کنند.')) {
        activeChk.checked = true;
      }
    });
  }
});
