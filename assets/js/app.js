document.addEventListener('DOMContentLoaded', function () {
  var ui = window.VCUi || { showToast: function (m) { console.log(m); }, copyText: function (t, d) { d(); } };

  // ---------- sidebar (mobile) ----------
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebarBackdrop');
  if (menuBtn && sidebar) {
    function closeSide() {
      sidebar.classList.remove('open');
      menuBtn.setAttribute('aria-expanded', 'false');
      if (backdrop) backdrop.classList.remove('show');
    }
    function openSide() {
      sidebar.classList.add('open');
      menuBtn.setAttribute('aria-expanded', 'true');
      if (backdrop) backdrop.classList.add('show');
    }
    menuBtn.addEventListener('click', openSide);
    menuBtn.addEventListener('touchstart', function (e) {
      e.preventDefault();
      openSide();
    }, { passive: false });
    if (backdrop) {
      backdrop.addEventListener('click', closeSide);
      backdrop.addEventListener('touchstart', function (e) {
        e.preventDefault();
        closeSide();
      }, { passive: false });
    }
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== menuBtn) closeSide();
    });
  }

  // ---------- toast + copy (shared via ui.js) ----------
  function showToast(msg) { ui.showToast(msg); }

  // ---------- copy to clipboard ----------
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-btn, .js-copy');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    if (!text) return;
    try { text = JSON.parse(text); } catch (err) {}
    ui.copyText(text, function () { showToast('کپی شد ✓'); });
  });

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
    function trapFocus(element) {
      var focusable = element.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      element.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      });
    }
    trapFocus(backdrop);
    backdrop.querySelector('.js-modal-no').addEventListener('click', function () { close(); if (onNo) onNo(); });
    backdrop.querySelector('.js-modal-yes').addEventListener('click', function () { close(); if (onYes) onYes(); });
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
    document.body.appendChild(backdrop);
    backdrop.querySelector('.js-modal-no').focus();
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
    var csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
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
    if (!msg) return;
    if (form.getAttribute('data-confirming') === '1') { form.removeAttribute('data-confirming'); return; }
    e.preventDefault();
    vcConfirm(msg, function () {
      form.setAttribute('data-confirming', '1');
      form.submit();
    });
  });
});

  // ---------- lightbox for images ----------
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-lightbox]');
    if (!trigger) return;
    var src = trigger.getAttribute('data-lightbox');
    if (!src) return;
    var box = document.createElement('div');
    box.className = 'lightbox-backdrop';
    box.innerHTML = '<button type="button" class="lightbox-close" aria-label="بستن">×</button><img src="' + src + '" alt="">';
    function closeLb() { box.remove(); }
    box.querySelector('.lightbox-close').addEventListener('click', closeLb);
    box.addEventListener('click', function (ev) { if (ev.target === box) closeLb(); });
    document.addEventListener('keydown', function esc(ev) {
      if (ev.key === 'Escape') { closeLb(); document.removeEventListener('keydown', esc); }
    });
    document.body.appendChild(box);
    box.querySelector('.lightbox-close').focus();
  });

  // ---------- Password Reset Modal ----------
  var resetPwdModal = document.getElementById('resetPwdModal');
  if (resetPwdModal) {
    var modalCard = resetPwdModal.querySelector('.modal-card');
    var userNameEl = resetPwdModal.querySelector('#resetPwdUserName');
    var form = resetPwdModal.querySelector('#resetPwdForm');
    var closeBtn = resetPwdModal.querySelector('.js-modal-close');

    function openResetModal(btn) {
      var userName = btn.getAttribute('data-user-name');
      var url = btn.getAttribute('data-url');
      userNameEl.textContent = userName;
      form.action = url;
      resetPwdModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      resetPwdModal.querySelector('input[name="password"]').focus();
      trapFocus(modalCard);
    }
    function closeResetModal() {
      resetPwdModal.style.display = 'none';
      document.body.style.overflow = '';
      form.reset();
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.js-reset-pwd');
      if (btn) {
        openResetModal(btn);
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', closeResetModal);
    }
    resetPwdModal.addEventListener('click', function (e) {
      if (e.target === resetPwdModal) closeResetModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && resetPwdModal.style.display === 'flex') closeResetModal();
    });
  }

  // ---------- password strength meter ----------
  document.querySelectorAll('[data-strength]').forEach(function (inp) {
    var barSel = inp.getAttribute('data-strength-bar');
    var txtSel = inp.getAttribute('data-strength-text');
    var bar = barSel ? document.querySelector(barSel) : null;
    var txt = txtSel ? document.querySelector(txtSel) : null;
    if (!bar && !txt) return;
    inp.addEventListener('input', function () {
      var v = inp.value;
      var score = 0;
      if (v === '') {
        score = 0;
      } else if (v.length < 6) {
        score = 1; // خیلی ضعیف
      } else if (/^\d+$/.test(v)) {
        score = 1; // فقط عدد = ضعیف
      } else if (/[A-Za-z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v)) {
        score = v.length >= 10 ? 5 : 4; // حروف + عدد + کاراکتر خاص = عالی/قوی
      } else if (/[A-Za-z]/.test(v) && /\d/.test(v)) {
        score = 3; // حروف + عدد = خوب
      } else {
        score = 2; // فقط حروف = متوسط
      }
      var labels = ['', 'خیلی ضعیف', 'ضعیف', 'متوسط', 'خوب', 'قوی', 'عالی'];
      var colors = ['', '#ef4444', '#ef4444', '#f59e0b', '#22c55e', '#16a34a', '#059669'];
      var pct = v === '' ? 0 : (score === 0 ? 0 : (score / 5) * 100);
      if (bar) { bar.style.width = pct + '%'; bar.style.background = colors[score]; }
      if (txt) txt.textContent = v === '' ? '' : labels[score];
    });
  });

  // ---------- beforeunload guard for card editor ----------
  var guardForm = document.getElementById('cardForm');
  if (guardForm) {
    var dirty = false;
    guardForm.addEventListener('input', function () { dirty = true; });
    guardForm.addEventListener('change', function () { dirty = true; });
    guardForm.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  }
