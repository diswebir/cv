document.addEventListener('DOMContentLoaded', function () {
  // ---------- sidebar (mobile) ----------
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebarBackdrop');
  if (menuBtn && sidebar) {
    function closeSide() {
      sidebar.classList.remove('open');
      if (backdrop) backdrop.classList.remove('show');
    }
    menuBtn.addEventListener('click', function () {
      sidebar.classList.add('open');
      if (backdrop) backdrop.classList.add('show');
    });
    if (backdrop) backdrop.addEventListener('click', closeSide);
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== menuBtn) closeSide();
    });
  }

  // ---------- toast ----------
  var toastEl = null;
  function showToast(msg) {
    if (!toastEl) {
      toastEl = document.createElement('div');
      toastEl.className = 'toast';
      document.body.appendChild(toastEl);
    }
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(function () { toastEl.classList.remove('show'); }, 1800);
  }

  // ---------- copy to clipboard ----------
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-btn, .js-copy');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    if (!text) return;
    function done() { showToast('کپی شد ✓'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text, done); });
    } else {
      fallbackCopy(text, done);
    }
  });

  function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (err) {}
    document.body.removeChild(ta);
  }

  // ---------- password visibility toggle ----------
  document.querySelectorAll('.pass-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var inp = document.getElementById(btn.getAttribute('data-target'));
      if (!inp) return;
      inp.type = inp.type === 'password' ? 'text' : 'password';
    });
  });

  // ---------- confirm modal ----------
  window.vcConfirm = function (message, onYes, onNo) {
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    // Security: build the DOM shell with innerHTML (static markup) but inject the
    // message via textContent to prevent XSS if a dynamic message is ever passed.
    backdrop.innerHTML =
      '<div class="modal-card">' +
      '<div class="modal-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8v5m0 3.5v.5M10.3 3.9 2.3 18a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></div>' +
      '<h3>تأیید</h3><p></p>' +
      '<div class="modal-actions">' +
      '<button type="button" class="btn btn-danger js-modal-no">انصراف</button>' +
      '<button type="button" class="btn btn-primary js-modal-yes">تأیید</button>' +
      '</div></div>';
    var msgEl = backdrop.querySelector('p');
    if (msgEl) msgEl.textContent = message;
    function close() { backdrop.remove(); }
    backdrop.querySelector('.js-modal-no').addEventListener('click', function () { close(); if (onNo) onNo(); });
    backdrop.querySelector('.js-modal-yes').addEventListener('click', function () { close(); if (onYes) onYes(); });
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
    document.body.appendChild(backdrop);
  };

  // ---------- card active toggle (switch) ----------
  function submitToggle(url, csrf) {
    var f = document.createElement('form');
    f.method = 'post';
    f.action = url;
    f.style.display = 'none';
    var i = document.createElement('input');
    i.type = 'hidden';
    i.name = 'csrf_token';
    i.value = csrf || '';
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
  }
  document.addEventListener('change', function (e) {
    var sw = e.target.closest('.js-card-toggle');
    if (!sw) return;
    var url = sw.getAttribute('data-url');
    var csrf = sw.getAttribute('data-csrf') || '';
    if (!url) return;
    var turningOff = !sw.checked;
    if (!turningOff) { submitToggle(url, csrf); return; }
    vcConfirm('کارت غیرفعال شود؟ کاربران با لینک آن دیگر به کارت دسترسی نخواهند داشت.', function () {
      submitToggle(url, csrf);
    }, function () { sw.checked = true; });
  });

  // ---------- confirm-on-submit ----------
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
});
