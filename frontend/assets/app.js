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
