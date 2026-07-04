// Runs in the PAGE's own (MAIN) world, injected by popup.js via
// chrome.scripting.executeScript({ world: 'MAIN' }) — which bypasses the page
// CSP that blocks inline <script> injection. Its whole job is to do the two
// things the isolated-world content script (filler.js) cannot:
//   1. Hook ASP.NET's PageRequestManager and mirror async-postback state onto
//      <html> data-attributes that filler.js can read.
//   2. Trigger a click / postback on a page element on request — needed for the
//      procedures "اضافة" button, which is an <a href="javascript:...postback">
//      whose action only runs when clicked from page context.
// filler.js asks for a click via window.postMessage({ __hioClick: '<id>' }).
(function () {
  if (window.__hioInpageReady) return;
  window.__hioInpageReady = true;

  var root = document.documentElement;

  // ── Postback state bridge ───────────────────────────────────────────
  try {
    if (window.Sys && Sys.WebForms && Sys.WebForms.PageRequestManager) {
      var prm = Sys.WebForms.PageRequestManager.getInstance();
      root.setAttribute('data-hio-pb', 'idle');
      if (!root.getAttribute('data-hio-pbcount')) root.setAttribute('data-hio-pbcount', '0');
      prm.add_beginRequest(function () {
        root.setAttribute('data-hio-pb', 'busy');
      });
      prm.add_endRequest(function () {
        root.setAttribute('data-hio-pbcount', String((parseInt(root.getAttribute('data-hio-pbcount'), 10) || 0) + 1));
        root.setAttribute('data-hio-pb', 'idle');
      });
    }
  } catch (e) { /* bridge optional — filler.js falls back to fixed delays */ }

  // ── Click / postback on request ─────────────────────────────────────
  window.addEventListener('message', function (e) {
    if (e.source !== window || !e.data || e.data.__hioClick == null) return;
    var el = document.getElementById(e.data.__hioClick);
    if (el) el.click(); // for <a href="javascript:..."> this runs the postback
  });

  // Signal to filler.js (shared DOM) that the page-context helper is live.
  root.setAttribute('data-hio-bridge', 'ok');
})();
