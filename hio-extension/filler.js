(function () {
  if (document.getElementById('hio-fill-panel')) {
    document.getElementById('hio-fill-panel').style.display = 'block';
    return;
  }

  // Confirmed against the live portal: procedures/lab prices auto-fill from
  // HIO's own catalog when the service code is selected. Medications and
  // supplies NEVER auto-fill price/unit — the portal rejects the add with
  // "ادخل سعر اصغر او اضغط وحدة صرف" if they're left empty — so those two
  // fields are always set from our own catalog data instead.
  const BUCKETS = {
    procedure: {
      selectId: 'ContentPlaceHolder1_drpservice',
      mainSelId: 'ContentPlaceHolder1_radMasterClass',
      mainClassValue: '11', // اجور العمليات — value doesn't matter for coverage, just needs a change event
      qtyId: 'ContentPlaceHolder1_txtCount',
      priceId: 'ContentPlaceHolder1_txtservicePrice',
      addBtnId: 'ContentPlaceHolder1_btnAdd',
      msgId: 'ContentPlaceHolder1_lbmsg',
      gridId: 'ContentPlaceHolder1_grdextraservice',
      autoPrice: true,
      label: 'إجراء / تحليل',
    },
    local_medication: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      mainSelId: 'ContentPlaceHolder1_radDrugMain',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      gridId: 'ContentPlaceHolder1_grdExtraDrug',
      autoPrice: false,
      mainClassValue: '1',
      discountValue: '15', // محلى
      label: 'دواء محلى',
    },
    imported_medication: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      mainSelId: 'ContentPlaceHolder1_radDrugMain',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      gridId: 'ContentPlaceHolder1_grdExtraDrug',
      autoPrice: false,
      mainClassValue: '1',
      discountValue: '7', // مستورد
      label: 'دواء مستورد',
    },
    supply: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      mainSelId: 'ContentPlaceHolder1_radDrugMain',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      gridId: 'ContentPlaceHolder1_grdExtraDrug',
      autoPrice: false,
      mainClassValue: '2',
      discountValue: '0', // لا يوجد
      unitFallback: 'قطعة',
      label: 'مستلزم طبى',
    },
  };

  let queue = [];
  let idx = 0;
  let log = [];
  let paused = false;
  let running = false;
  let sessionExpired = false;
  // 'manual' = fill each item then wait for the operator to confirm/skip it
  // before clicking Add. 'automatic' = run the whole queue unattended (old
  // behaviour). Manual is the default per operator feedback — the portal run
  // used to require a per-item click, and switching that to fully automatic
  // by default surprised the operator, so it's opt-in now.
  let mode = 'manual';

  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  function buildQueue(data) {
    const q = [];
    (data.procedures || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'procedure' })));
    (data.local_medications || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'local_medication' })));
    (data.imported_medications || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'imported_medication' })));
    (data.supplies || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'supply' })));
    return q;
  }

  // ── ASP.NET async-postback bridge ────────────────────────────────────
  // The portal's item dropdowns (drpservice/drpdrug/radDrugMain/...) all have
  // AutoPostBack: selecting one fires a __doPostBack UpdatePanel round-trip
  // that takes up to ~5 seconds. Clicking "Add" while one is still in flight
  // makes the add silently fail.
  //
  // filler.js runs in the isolated content-script world, so it can't read the
  // page's Sys.WebForms.PageRequestManager or click a javascript:-href anchor
  // in page context — and the page/extension CSP blocks injecting an inline
  // <script> to do it. Instead popup.js injects inpage.js into the MAIN world
  // (via executeScript world:'MAIN', which bypasses CSP). inpage.js mirrors
  // postback state onto <html> data-attributes (data-hio-pb = busy|idle) and,
  // on window.postMessage({__hioClick: id}), clicks that element in page
  // context. data-hio-bridge = ok means inpage.js is live.
  let bridgeOk = false;
  async function waitForInpageBridge(timeoutMs) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
      if (document.documentElement.getAttribute('data-hio-bridge') === 'ok') {
        bridgeOk = true;
        return true;
      }
      await sleep(100);
    }
    return false; // inpage.js not ready — actionAndSettle falls back to delays
  }

  function pbState() {
    return document.documentElement.getAttribute('data-hio-pb') || 'idle';
  }

  // Ask inpage.js (MAIN world) to click an element in page context. Needed for
  // the procedures "اضافة" button, an <a href="javascript:...postback">: a
  // .click() from this isolated world doesn't run the javascript: href, so the
  // postback never fired and the procedure was never added (drugs worked
  // because their button is a plain <input type=submit>).
  function clickInPage(elementId) {
    window.postMessage({ __hioClick: elementId }, '*');
  }

  function findOption(select, code) {
    return Array.from(select.options).some((o) => o.value === String(code));
  }

  function gridHasCode(gridId, code) {
    const g = document.getElementById(gridId);
    if (!g) return false;
    return Array.from(g.rows).some((r) =>
      Array.from(r.querySelectorAll('td')).some((td) => td.textContent.trim() === String(code))
    );
  }

  // Confirm an add actually went through. Verified live: on success HIO both
  // (a) sets the section's status label to "تم الاضافة" and clears the form's
  // hidden id field, and (b) eventually shows the row in the result grid — BUT
  // the grid re-renders on a delayed/batched postback that can land many
  // seconds later (after the next item already started). So the label + the
  // form reset is the immediate per-item signal; the grid is a slower backstop.
  // We clear the label before clicking Add, so seeing it turn to "تم الاضافة"
  // means THIS add succeeded, not a stale message from a previous one.
  async function waitForAddConfirm(cfg, code, timeoutMs) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
      if (isSessionExpired()) return false;
      const msg = (document.getElementById(cfg.msgId)?.textContent || '').trim();
      if (/تم\s*الا?ضافة/.test(msg)) return true;
      if (gridHasCode(cfg.gridId, code)) return true;
      await sleep(200);
    }
    return gridHasCode(cfg.gridId, code);
  }

  // Run an action that triggers AutoPostBack(s), then wait until the page fully
  // settles. Confirmed live: ONE dropdown selection fires ~2 chained postbacks
  // and stays "busy" for ~6s, and the server-filled fields (e.g. price) are
  // only correct after the WHOLE chain finishes. So we wait for "busy" to
  // appear, then for "idle" to hold steady (past every chained postback) —
  // not just the first one. Falls back to a fixed delay without the bridge.
  // `abortable` lets a Stop request cut the wait short. Only pass it for waits
  // that happen BEFORE the Add is clicked — never for the Add's own postback,
  // so we never stop between "add clicked" and "add confirmed" (which could
  // leave a half-added row or cause a duplicate on resume).
  async function actionAndSettle(fn, timeoutMs, abortable) {
    if (!bridgeOk) {
      fn();
      await sleep(6000);
      return true;
    }
    fn();
    const start = Date.now();
    // 1. Wait for the first postback to start (page goes busy).
    let sawBusy = false;
    while (Date.now() - start < 2500) {
      if (pbState() === 'busy') { sawBusy = true; break; }
      if (isSessionExpired() || (abortable && paused)) return false;
      await sleep(80);
    }
    if (!sawBusy) return true; // no postback was triggered at all
    // 2. Wait for idle to hold ~700ms straight (past all chained postbacks).
    let idleSince = null;
    while (Date.now() - start < timeoutMs) {
      if (isSessionExpired() || (abortable && paused)) return false;
      if (pbState() === 'idle') {
        if (idleSince === null) idleSince = Date.now();
        else if (Date.now() - idleSince > 700) return true;
      } else {
        idleSince = null;
      }
      await sleep(100);
    }
    return false; // never settled
  }

  async function waitForValue(id, timeoutMs, abortable) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
      if (abortable && paused) return false;
      const el = document.getElementById(id);
      if (el && el.value) return true;
      await sleep(120);
    }
    return false;
  }

  function setInputValue(input, value) {
    input.value = String(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  // Make sure the item dropdown is populated (and, for drugs/supplies, that
  // radDrugMain reflects the correct category so the row is filed right).
  // drpservice/drpdrug are empty until their main-class change fires; also we
  // switch radDrugMain per bucket. Every such change is an AutoPostBack, so we
  // wait for each one. We only fire when needed (empty list, or wrong category).
  async function ensureMainClass(cfg) {
    const mainSel = cfg.mainSelId ? document.getElementById(cfg.mainSelId) : null;
    const select = document.getElementById(cfg.selectId);
    const populated = select && select.options.length > 10;
    const needsCategory = cfg.mainClassValue && mainSel && mainSel.value !== String(cfg.mainClassValue);

    if (populated && !needsCategory) return select;
    if (!mainSel) return select;

    await actionAndSettle(() => {
      if (cfg.mainClassValue) mainSel.value = String(cfg.mainClassValue);
      mainSel.dispatchEvent(new Event('change', { bubbles: true }));
    }, 15000, true);

    return document.getElementById(cfg.selectId);
  }

  function persist() {
    chrome.storage.local.set({ hioFillProgress: { idx, log, paused, mode } });
  }

  // ── Panel UI ─────────────────────────────────────────────────────────
  const style = document.createElement('style');
  style.textContent = `
    #hio-fill-panel { position: fixed; top: 70px; left: 12px; width: 320px; max-height: 80vh;
      background: #fff; border: 1px solid #d0d0d0; border-radius: 8px; box-shadow: 0 4px 18px rgba(0,0,0,.25);
      z-index: 999999; font-family: Tahoma, Arial, sans-serif; direction: rtl; font-size: 13px; overflow: hidden;
      display: flex; flex-direction: column; }
    #hio-fill-panel .hio-head { background: #1a3c6e; color: #fff; padding: 8px 10px; display: flex; justify-content: space-between; align-items: center; }
    #hio-fill-panel .hio-head b { font-size: 13px; }
    #hio-fill-panel .hio-close { cursor: pointer; background: none; border: none; color: #fff; font-size: 16px; line-height: 1; }
    #hio-fill-panel .hio-body { padding: 10px; overflow-y: auto; }
    #hio-fill-panel .hio-progress { color: #666; font-size: 11px; margin-bottom: 6px; }
    #hio-fill-panel .hio-item { background: #f4f6fa; border-radius: 6px; padding: 8px; margin-bottom: 8px; }
    #hio-fill-panel .hio-item .name { font-weight: bold; }
    #hio-fill-panel .hio-item .meta { color: #555; font-size: 11px; margin-top: 3px; }
    #hio-fill-panel .hio-warn { color: #b45309; }
    #hio-fill-panel .hio-ok { color: #15803d; }
    #hio-fill-panel .hio-err { color: #b91c1c; }
    #hio-fill-panel button.hio-btn { width: 100%; padding: 7px; margin-bottom: 6px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; }
    #hio-fill-panel .hio-start { background: #2563eb; color: #fff; }
    #hio-fill-panel .hio-stop { background: #b91c1c; color: #fff; }
    #hio-fill-panel .hio-skip { background: #e5e7eb; color: #333; }
    #hio-fill-panel button.hio-btn:disabled { opacity: .7; cursor: default; }
    #hio-fill-panel .hio-mode { display: flex; gap: 6px; margin-bottom: 8px; }
    #hio-fill-panel .hio-mode-btn { flex: 1; padding: 6px 4px; border: 1px solid #cbd5e1; border-radius: 5px;
      background: #fff; color: #444; font-size: 11px; cursor: pointer; }
    #hio-fill-panel .hio-mode-btn.active { background: #1a3c6e; border-color: #1a3c6e; color: #fff; font-weight: bold; }
    #hio-fill-panel .hio-mode-hint { color: #777; font-size: 10px; margin: -4px 0 8px; }
    #hio-fill-panel .hio-log { max-height: 200px; overflow-y: auto; border-top: 1px solid #eee; margin-top: 8px; padding-top: 6px; }
    #hio-fill-panel .hio-log div { font-size: 11px; padding: 2px 0; }
  `;
  document.head.appendChild(style);

  const panel = document.createElement('div');
  panel.id = 'hio-fill-panel';
  panel.innerHTML = `
    <div class="hio-head"><b>مساعد تعبئة HIO</b><button class="hio-close" id="hioClose">×</button></div>
    <div class="hio-body">
      <div class="hio-progress" id="hioProgress"></div>
      <div id="hioCurrent"></div>
      <div class="hio-log" id="hioLog"></div>
    </div>
  `;
  document.body.appendChild(panel);
  document.getElementById('hioClose').addEventListener('click', () => { panel.style.display = 'none'; });

  function renderLog() {
    const el = document.getElementById('hioLog');
    el.innerHTML = log
      .slice()
      .reverse()
      .map((l) => `<div class="hio-${l.status}">${l.status === 'ok' ? '✔' : l.status === 'skip' ? '⏭' : '⚠'} ${l.item.name} — ${l.message}</div>`)
      .join('');
  }

  function renderControls() {
    const box = document.getElementById('hioCurrent');
    if (sessionExpired) {
      box.innerHTML = `<div class="hio-item hio-err">انتهت صلاحية جلسة HIO أثناء التشغيل — الصفحة رجعت لتسجيل الدخول.<br>
        سجّل الدخول تانى فى HIO، ارجع لنفس الفاتورة، وابدأ تانى — البنود اللي خلصت فعلاً محفوظة ومش هتتكرر.</div>`;
      return;
    }
    if (idx >= queue.length) {
      const ok = log.filter((l) => l.status === 'ok').length;
      const warn = log.filter((l) => l.status !== 'ok').length;
      box.innerHTML = `<div class="hio-item hio-ok">تم الانتهاء من كل البنود.<br>تمت إضافة ${ok} بند فعليًا (تأكدنا من رسالة HIO)، وفيه ${warn} بند فشل أو محتاج مراجعة/إضافة يدوية (اتفصّلوا فى السجل تحت).<br><br>
        <b>راجع الجداول جوه صفحة HIO ثم اضغط "Submit" هناك يدويًا.</b></div>`;
      return;
    }
    if (!running) {
      const resuming = idx > 0;
      const startLabel = mode === 'manual'
        ? (resuming ? 'استكمال — بند بند' : '▶ بدء — بند بند (يدوي)')
        : (resuming ? '▶ استكمال تلقائيًا' : '▶ بدء تلقائي لكل البنود');
      box.innerHTML =
        (resuming ? `<div class="hio-item">تم الإيقاف عند البند ${idx} من ${queue.length}.</div>` : '') +
        `<div class="hio-mode">
           <button class="hio-mode-btn ${mode === 'manual' ? 'active' : ''}" id="hioModeManual">يدوي (افتراضي)</button>
           <button class="hio-mode-btn ${mode === 'automatic' ? 'active' : ''}" id="hioModeAuto">تلقائي</button>
         </div>
         <div class="hio-mode-hint">${mode === 'manual'
           ? 'هيوقّفلك على كل بند تراجعه وتضغط "إضافة" أو "تخطي" بنفسك.'
           : 'هيكمّل كل البنود على التوالي من غير ما يوقف يسأل.'}</div>
         <button class="hio-btn hio-start" id="hioStart">${startLabel}</button>`;
      document.getElementById('hioModeManual').addEventListener('click', () => { mode = 'manual'; persist(); renderControls(); });
      document.getElementById('hioModeAuto').addEventListener('click', () => { mode = 'automatic'; persist(); renderControls(); });
      document.getElementById('hioStart').addEventListener('click', () => {
        paused = false;
        persist();
        if (mode === 'manual') runManualStep();
        else runLoop();
      });
    } else if (mode === 'automatic') {
      // Manual mode manages #hioCurrent itself (setCurrent / renderManualConfirm)
      // while running, so only the automatic loop's Stop button belongs here.
      box.innerHTML = `<button class="hio-btn hio-stop" id="hioStop">■ إيقاف</button>`;
      document.getElementById('hioStop').addEventListener('click', (e) => {
        paused = true;
        persist();
        const btn = e.currentTarget;
        btn.disabled = true;
        btn.textContent = '⏳ جارٍ الإيقاف بعد إتمام البند الحالى...';
      });
    }
  }

  function updateProgress() {
    document.getElementById('hioProgress').textContent = `البند ${Math.min(idx + 1, queue.length)} من ${queue.length}`;
  }

  // The HIO portal times out the session after a while. Once that happens
  // every click just lands on nothing (redirected to ProviderLogin.aspx),
  // and every remaining item would otherwise get logged as a generic
  // failure. Detect it up front and stop the whole run instead.
  function isSessionExpired() {
    return /ProviderLogin/i.test(window.location.href) || !!document.getElementById('txtloginName');
  }

  function setCurrent(item, cfg, note) {
    document.getElementById('hioCurrent').innerHTML = `<div class="hio-item">
      <div class="name">${item.name}</div>
      <div class="meta">${cfg.label} — كود: ${item.code} — الكمية: ${item.qty}</div>
      <div class="meta">${note}</div></div>`;
  }

  // Fill an item's fields and stop right before "Add" — steps 1-4 of the old
  // combined processItem. Shared by both modes: automatic clicks Add itself
  // right after (see processItem below); manual shows a confirm/skip prompt
  // instead. Returns 'ready' (fields filled, waiting to commit),
  // 'auto_advanced' (nothing to confirm — already logged + idx moved on:
  // no code, code not in HIO's list, list failed to load, or price never
  // filled), or 'session_expired' / 'paused'.
  async function prepareItem(item, cfg) {
    if (isSessionExpired()) {
      return 'session_expired';
    }
    // Stop requested before this item even started — bail without touching it.
    if (paused) return 'paused';

    if (!item.code) {
      log.push({ status: 'warn', item, message: 'لا يوجد كود HIO لهذا الصنف فى نظامنا — أضِفه يدويًا' });
      idx++; persist(); renderLog(); updateProgress();
      return 'auto_advanced';
    }

    // 1. Make sure the catalog is loaded and the category is right (postback).
    setCurrent(item, cfg, 'جارٍ تحميل القائمة وضبط التصنيف...');
    const select = await ensureMainClass(cfg);
    if (isSessionExpired()) return 'session_expired';
    if (paused) return 'paused';
    if (!select || select.options.length <= 10) {
      log.push({ status: 'err', item, message: `قائمة ${cfg.label} لم تُحمَّل فى صفحة HIO — أعد تحميل الصفحة وجرب تانى` });
      idx++; persist(); renderLog(); updateProgress();
      return 'auto_advanced';
    }
    if (!findOption(select, item.code)) {
      log.push({ status: 'warn', item, message: `الكود ${item.code} غير موجود فى قائمة ${cfg.label} فى HIO — أضِفه يدويًا` });
      idx++; persist(); renderLog(); updateProgress();
      return 'auto_advanced';
    }

    // 2. Select the item — this fires ~2 chained AutoPostBacks (~6s total). WAIT
    //    for the whole chain to settle before touching anything, otherwise the
    //    price isn't filled yet and the Add below collides with the postback.
    setCurrent(item, cfg, 'جارٍ اختيار الصنف والانتظار...');
    const selected = await actionAndSettle(() => {
      select.value = String(item.code);
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }, 15000, true);
    if (isSessionExpired()) return 'session_expired';
    // Nothing has been added yet, so a stop here is safe to honour immediately.
    if (paused) return 'paused';
    if (!selected) {
      log.push({ status: 'err', item, message: 'لم تكتمل استجابة HIO بعد اختيار الصنف — أعد المحاولة' });
      idx++; persist(); renderLog(); updateProgress();
      return 'auto_advanced';
    }

    // 3. Price / unit / discount — set AFTER the select postback so they don't
    //    get wiped by it. (None of these fields auto-post-back.)
    if (cfg.autoPrice) {
      // HIO fills this itself, but only once the whole postback chain is done —
      // give it a little extra grace beyond settle before giving up.
      const gotPrice = await waitForValue(cfg.priceId, 3000, true);
      if (paused) return 'paused';
      if (!gotPrice) {
        log.push({ status: 'err', item, message: 'السعر لم يُعبَّأ من HIO — أضِفه يدويًا' });
        idx++; persist(); renderLog(); updateProgress();
        return 'auto_advanced';
      }
    } else {
      const priceEl = document.getElementById(cfg.priceId);
      if (priceEl) setInputValue(priceEl, item.unit_price);
      const unitEl = document.getElementById(cfg.unitId);
      if (unitEl) setInputValue(unitEl, item.unit || cfg.unitFallback || 'قطعة');
      const discSel = document.getElementById('ContentPlaceHolder1_drpdiscount');
      if (discSel && cfg.discountValue !== undefined) {
        discSel.value = String(cfg.discountValue);
        discSel.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    // 4. Quantity.
    const qtyEl = document.getElementById(cfg.qtyId);
    if (qtyEl) setInputValue(qtyEl, item.qty);
    await sleep(150);

    // Last safe stop point: nothing added yet. Past here we always finish the
    // add + confirmation so we never leave a half-added row.
    if (paused) return 'paused';

    return 'ready';
  }

  // Steps 5-6 of the old combined processItem: click Add, confirm, log. Always
  // runs to completion once called (not abortable) so a Stop/tab-close can
  // never leave a half-added row — matches the pre-existing behaviour.
  async function commitItem(item, cfg) {
    const priceShown = document.getElementById(cfg.priceId)?.value || '—';
    setCurrent(item, cfg, `جارٍ الإضافة... السعر: ${priceShown}`);

    // Clear the status label first (so "تم الاضافة" can only mean THIS add),
    // click Add, wait for the postback chain to settle, then confirm the add
    // actually went through before moving on to the next item.
    const msgEl = document.getElementById(cfg.msgId);
    if (msgEl) msgEl.textContent = '';
    await actionAndSettle(() => {
      clickInPage(cfg.addBtnId);
    }, 15000);
    if (isSessionExpired()) return 'session_expired';

    const success = await waitForAddConfirm(cfg, item.code, 12000);

    const resultMsg = (document.getElementById(cfg.msgId)?.textContent || '').trim();
    log.push({
      status: success ? 'ok' : 'err',
      item,
      message: success
        ? 'تمت الإضافة (ظهر البند فى الجدول)'
        : `لم يظهر البند فى جدول HIO بعد الإضافة${resultMsg ? ' — رسالة: "' + resultMsg + '"' : ''}`,
    });
    idx++;
    persist();
    renderLog();
    updateProgress();
    await sleep(500);
  }

  // Automatic mode: prepare then immediately commit, no operator step in between.
  async function processItem() {
    const item = queue[idx];
    const cfg = BUCKETS[item.bucket];
    const prep = await prepareItem(item, cfg);
    if (prep === 'ready') return commitItem(item, cfg);
    return prep;
  }

  async function runLoop() {
    running = true;
    renderControls();
    while (idx < queue.length && !paused) {
      const result = await processItem();
      if (result === 'session_expired') {
        sessionExpired = true;
        paused = true;
        persist();
        break;
      }
      if (result === 'paused') break; // Stop honoured at a safe point.
      if (idx < queue.length) renderControls();
    }
    running = false;
    renderControls();
  }

  // Manual mode: prepare one item, then wait for the operator to confirm or
  // skip it before touching the next. Items with nothing to confirm (no code,
  // code missing from HIO, etc.) are skipped automatically in either mode —
  // there's nothing for the operator to review in those cases.
  async function runManualStep() {
    running = true;
    while (idx < queue.length) {
      const item = queue[idx];
      const cfg = BUCKETS[item.bucket];
      const prep = await prepareItem(item, cfg);
      if (prep === 'session_expired') {
        sessionExpired = true;
        paused = true;
        persist();
        running = false;
        renderControls();
        return;
      }
      if (prep === 'paused') {
        running = false;
        renderControls();
        return;
      }
      if (prep === 'ready') {
        renderManualConfirm(item, cfg);
        return;
      }
      // 'auto_advanced' — nothing to confirm, loop to the next item.
    }
    running = false;
    renderControls();
  }

  function renderManualConfirm(item, cfg) {
    const priceShown = document.getElementById(cfg.priceId)?.value || '—';
    document.getElementById('hioCurrent').innerHTML = `<div class="hio-item">
        <div class="name">${item.name}</div>
        <div class="meta">${cfg.label} — كود: ${item.code} — الكمية: ${item.qty}</div>
        <div class="meta">السعر: ${priceShown}</div>
      </div>
      <button class="hio-btn hio-start" id="hioConfirm">✔ إضافة هذا البند</button>
      <button class="hio-btn hio-skip" id="hioSkip">⏭ تخطي بدون إضافة</button>`;

    document.getElementById('hioConfirm').addEventListener('click', async (e) => {
      e.currentTarget.disabled = true;
      document.getElementById('hioSkip').disabled = true;
      const res = await commitItem(item, cfg);
      if (res === 'session_expired') {
        sessionExpired = true;
        paused = true;
        persist();
        running = false;
        renderControls();
        return;
      }
      runManualStep();
    });
    document.getElementById('hioSkip').addEventListener('click', () => {
      log.push({ status: 'skip', item, message: 'تم التخطي بواسطة المستخدم' });
      idx++; persist(); renderLog(); updateProgress();
      runManualStep();
    });
  }

  async function init() {
    const stored = await chrome.storage.local.get(['hioExportData', 'hioFillProgress']);
    if (!stored.hioExportData) {
      alert('لا توجد بيانات فاتورة مُصدَّرة. افتح صفحة فاتورة المريض فى نظام المستشفى واضغط "تصدير" أولاً من الإكستنشن.');
      panel.remove();
      return;
    }
    // inpage.js (MAIN world) is injected by popup.js just before us; it does
    // the page-context work (postback bridge + clicking the Add button), so
    // wait for it. Without it, the Add can't be clicked at all — bail clearly.
    const bridgeReady = await waitForInpageBridge(6000);
    if (!bridgeReady) {
      document.getElementById('hioCurrent').innerHTML =
        '<div class="hio-item hio-err">تعذّر تحميل مُساعد الصفحة (inpage.js). أعد تحميل الإكستنشن من chrome://extensions ثم صفحة HIO وجرّب تانى.</div>';
      return;
    }
    queue = buildQueue(stored.hioExportData);
    if (stored.hioFillProgress) {
      idx = stored.hioFillProgress.idx || 0;
      log = stored.hioFillProgress.log || [];
      paused = !!stored.hioFillProgress.paused;
      mode = stored.hioFillProgress.mode === 'automatic' ? 'automatic' : 'manual';
    }
    updateProgress();
    renderLog();
    // Always wait for an explicit "بدء"/"استكمال" click — even to resume —
    // instead of auto-continuing, so opening the panel never starts adding
    // items to HIO on its own.
    renderControls();
  }

  init();
})();
