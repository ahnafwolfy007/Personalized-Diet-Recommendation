// frontend/assets/app.js
// Shared client helpers, loaded synchronously in <head> on every authenticated
// page so they are available before any inline page script runs.
//
// 1) Auto-attaches the CSRF token to same-origin mutating fetch() requests, so
//    individual pages don't need to remember to include it.
// 2) Provides escapeHtml() for safely rendering user-supplied data into HTML.

(function () {
  'use strict';

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  var nativeFetch = window.fetch.bind(window);
  window.fetch = function (input, init) {
    init = init || {};
    var method = (init.method || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD') {
      var token = csrfToken();
      if (token) {
        var headers = new Headers(init.headers || {});
        if (!headers.has('X-CSRF-Token')) {
          headers.set('X-CSRF-Token', token);
        }
        init.headers = headers;
      }
    }
    return nativeFetch(input, init);
  };

  window.escapeHtml = function (value) {
    if (value === null || value === undefined) return '';
    return String(value).replace(/[&<>"']/g, function (ch) {
      switch (ch) {
        case '&': return '&amp;';
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '"': return '&quot;';
        default:  return '&#39;';
      }
    });
  };

  // Non-blocking toast notifications. type: 'success' | 'error' | 'info'.
  window.showToast = function (message, type) {
    type = type || 'info';
    var region = document.getElementById('toast-region');
    if (!region) {
      region = document.createElement('div');
      region.id = 'toast-region';
      region.className = 'toast-region';
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'false');
      document.body.appendChild(region);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.textContent = message; // textContent keeps it XSS-safe
    region.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('show'); });
    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
    }, 3500);
  };

  // Reusable "give a reason" modal. Resolves through onConfirm(reason, done),
  // where calling done() closes the modal. Used for unassignment rationale.
  window.showReasonModal = function (opts) {
    opts = opts || {};
    var overlay = document.createElement('div');
    overlay.className = 'modal-overlay open';
    overlay.style.zIndex = '1200';
    overlay.innerHTML =
      '<div class="modal-content" style="max-width:480px;">' +
        '<div class="modal-header"><h3>' + (opts.title || 'Provide a reason') + '</h3>' +
          '<button type="button" class="modal-close" data-close>&times;</button></div>' +
        '<div class="modal-body">' +
          '<p class="text-sm text-gray mb-4">' + (opts.label || 'A reason is required and will be shared with the other person.') + '</p>' +
          '<textarea id="reason-modal-text" rows="4" placeholder="' + (opts.placeholder || 'Type your reason…') + '"></textarea>' +
          '<div class="flex gap-3 mt-4">' +
            '<button type="button" class="btn btn-primary" data-confirm>' + (opts.confirmText || 'Submit') + '</button>' +
            '<button type="button" class="btn btn-secondary" data-close>Cancel</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);

    var textarea = overlay.querySelector('#reason-modal-text');
    textarea.focus();

    function close() { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay || e.target.hasAttribute('data-close')) close();
    });
    overlay.querySelector('[data-confirm]').addEventListener('click', function () {
      var reason = textarea.value.trim();
      if (!reason) { textarea.focus(); return; }
      if (typeof opts.onConfirm === 'function') opts.onConfirm(reason, close);
    });
  };

  // Renders a dismissible banner at the top of .main-content if the current user
  // was unassigned by their counterpart (shows the recorded reason).
  window.initRemovalNotice = function () {
    fetch('../backend/get_removal_notice.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success || !data.notice) return;
        var n = data.notice;
        var who = n.initiator_role === 'patient' ? 'patient' : 'dietitian';
        var host = document.querySelector('.main-content .max-w-1200, .main-content .container-narrow, .main-content > div');
        if (!host) return;
        var banner = document.createElement('div');
        banner.className = 'removal-notice';
        banner.innerHTML =
          '<div><strong>Assignment ended.</strong> Your ' + who + ' ' +
          window.escapeHtml(n.initiator_name) + ' ended the assignment on ' + window.escapeHtml(n.created_at) +
          '.<div class="removal-reason">Reason: ' + window.escapeHtml(n.reason) + '</div></div>' +
          '<button type="button" class="btn btn-secondary btn-sm" data-dismiss>Dismiss</button>';
        host.insertBefore(banner, host.firstChild);
        banner.querySelector('[data-dismiss]').addEventListener('click', function () {
          var fd = new FormData();
          fd.append('removal_id', n.removal_id);
          fetch('../backend/get_removal_notice.php', { method: 'POST', body: fd });
          banner.parentNode.removeChild(banner);
        });
      })
      .catch(function () {});
  };

  // Show/hide password toggle: any button.pw-toggle with data-target="<input id>".
  document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.pw-toggle') : null;
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-target'));
    if (!input) return;
    var reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    btn.classList.toggle('is-on', reveal);
    btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
  });

  // Mobile sidebar drawer: wire the hamburger toggle + overlay when present.
  document.addEventListener('DOMContentLoaded', function () {
    var toggle  = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!toggle || !sidebar) return;

    function setOpen(open) {
      sidebar.classList.toggle('open', open);
      if (overlay) overlay.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
      setOpen(!sidebar.classList.contains('open'));
    });
    if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  });
})();
