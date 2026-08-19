document.addEventListener('DOMContentLoaded', function () {
  var ui = window.VCUi || { showToast: function (m) { console.log(m); }, copyText: function (t, d) { d(); } };
  function showToast(msg) { ui.showToast(msg); }
  function copyText(text, done) { ui.copyText(text, done); }

  // ---------- copy link ----------
  document.querySelectorAll('.js-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy') || '';
      try { text = JSON.parse(text); } catch (err) {}
      copyText(text, function () {
        showToast('لینک کپی شد ✓');
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '✓ کپی شد';
        btn.style.background = 'var(--green)';
        setTimeout(function() { btn.innerHTML = originalHtml; btn.style.background = ''; }, 1500);
      });
    });
  });

  // ---------- native share ----------
  document.querySelectorAll('.js-share').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-url');
      try { url = JSON.parse(url); } catch (err) {}
      if (navigator.share) {
        navigator.share({ title: document.title, url: url }).catch(function () {});
      } else {
        copyText(url, function () { showToast('لینک کپی شد ✓'); });
      }
    });
  });
});
