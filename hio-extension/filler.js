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
      qtyId: 'ContentPlaceHolder1_txtCount',
      priceId: 'ContentPlaceHolder1_txtservicePrice',
      addBtnId: 'ContentPlaceHolder1_btnAdd',
      msgId: 'ContentPlaceHolder1_lbmsg',
      useChosen: false,
      autoPrice: true,
      label: 'إجراء / تحليل',
    },
    local_medication: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      useChosen: true,
      autoPrice: false,
      mainClassValue: '1',
      discountValue: '15', // محلى
      label: 'دواء محلى',
    },
    imported_medication: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      useChosen: true,
      autoPrice: false,
      mainClassValue: '1',
      discountValue: '7', // مستورد
      label: 'دواء مستورد',
    },
    supply: {
      selectId: 'ContentPlaceHolder1_drpdrug',
      qtyId: 'ContentPlaceHolder1_txtDrugCount',
      priceId: 'ContentPlaceHolder1_txtprice',
      unitId: 'ContentPlaceHolder1_txtUnite',
      addBtnId: 'ContentPlaceHolder1_btndrugsave',
      msgId: 'ContentPlaceHolder1_lbmsgDrug',
      useChosen: true,
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

  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  function buildQueue(data) {
    const q = [];
    (data.procedures || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'procedure' })));
    (data.local_medications || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'local_medication' })));
    (data.imported_medications || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'imported_medication' })));
    (data.supplies || []).forEach((it) => q.push(Object.assign({}, it, { bucket: 'supply' })));
    return q;
  }

  function findOption(select, code) {
    return Array.from(select.options).some((o) => o.value === String(code));
  }

  function setSelectValue(select, code, useChosen) {
    select.value = String(code);
    if (useChosen && window.jQuery) {
      window.jQuery(select).trigger('chosen:updated').trigger('change');
    } else {
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function setInputValue(input, value) {
    input.value = String(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  async function waitForPriceOrTimeout(priceId, timeoutMs) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
      const el = document.getElementById(priceId);
      if (el && el.value) return true;
      await sleep(150);
    }
    return false;
  }

  function persist() {
    chrome.storage.local.set({ hioFillProgress: { idx, log, paused } });
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
    #hio-fill-panel .hio-pause { background: #e5e7eb; color: #333; }
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
    if (idx >= queue.length) {
      const ok = log.filter((l) => l.status === 'ok').length;
      const warn = log.filter((l) => l.status !== 'ok').length;
      box.innerHTML = `<div class="hio-item hio-ok">تم الانتهاء من كل البنود.<br>تمت إضافة ${ok} بند فعليًا (تأكدنا من رسالة HIO)، وفيه ${warn} بند فشل أو محتاج مراجعة/إضافة يدوية (اتفصّلوا فى السجل تحت).<br><br>
        <b>راجع الجداول جوه صفحة HIO ثم اضغط "Submit" هناك يدويًا.</b></div>`;
      return;
    }
    if (!running) {
      box.innerHTML = `<button class="hio-btn hio-start" id="hioStart">▶ ${paused ? 'استكمال' : 'بدء'} الإضافة التلقائية لكل البنود</button>`;
      document.getElementById('hioStart').addEventListener('click', () => {
        paused = false;
        persist();
        runLoop();
      });
    } else {
      box.innerHTML = `<button class="hio-btn hio-pause" id="hioPause">⏸ إيقاف مؤقت</button>`;
      document.getElementById('hioPause').addEventListener('click', () => {
        paused = true;
        persist();
      });
    }
  }

  function updateProgress() {
    document.getElementById('hioProgress').textContent = `البند ${Math.min(idx + 1, queue.length)} من ${queue.length}`;
  }

  async function processItem() {
    const item = queue[idx];
    const cfg = BUCKETS[item.bucket];

    if (!item.code) {
      log.push({ status: 'warn', item, message: 'لا يوجد كود HIO لهذا الصنف فى نظامنا — أضِفه يدويًا' });
      idx++; persist(); renderLog(); updateProgress();
      return;
    }

    if (cfg.mainClassValue) {
      const mainSel = document.getElementById('ContentPlaceHolder1_radDrugMain');
      if (mainSel && mainSel.value !== cfg.mainClassValue) {
        mainSel.value = cfg.mainClassValue;
        mainSel.dispatchEvent(new Event('change', { bubbles: true }));
        await sleep(1200);
      }
    }

    const select = document.getElementById(cfg.selectId);
    if (!select) {
      log.push({ status: 'err', item, message: 'تعذر العثور على عنصر الاختيار فى الصفحة' });
      idx++; persist(); renderLog(); updateProgress();
      return;
    }
    if (!findOption(select, item.code)) {
      log.push({ status: 'warn', item, message: `الكود ${item.code} غير موجود فى قائمة ${cfg.label} فى HIO — أضِفه يدويًا` });
      idx++; persist(); renderLog(); updateProgress();
      return;
    }

    setSelectValue(select, item.code, cfg.useChosen);

    if (cfg.autoPrice) {
      await waitForPriceOrTimeout(cfg.priceId, 2500);
    } else {
      // Medications/supplies never auto-fill — set from our own catalog data.
      await sleep(400);
      const priceEl = document.getElementById(cfg.priceId);
      if (priceEl) setInputValue(priceEl, item.unit_price);
      const unitEl = document.getElementById(cfg.unitId);
      if (unitEl) setInputValue(unitEl, item.unit || cfg.unitFallback || 'قطعة');
      const discSel = document.getElementById('ContentPlaceHolder1_drpdiscount');
      if (discSel && cfg.discountValue !== undefined) {
        setSelectValue(discSel, cfg.discountValue, true);
        await sleep(300);
      }
    }

    const qtyEl = document.getElementById(cfg.qtyId);
    if (qtyEl) setInputValue(qtyEl, item.qty);
    await sleep(200);

    const priceEl2 = document.getElementById(cfg.priceId);
    const price = priceEl2 ? priceEl2.value : '';

    document.getElementById('hioCurrent').innerHTML = `<div class="hio-item">
      <div class="name">${item.name}</div>
      <div class="meta">${cfg.label} — كود: ${item.code} — الكمية: ${item.qty}</div>
      <div class="meta">السعر: ${price || '—'}</div>
    </div>`;

    const msgEl = document.getElementById(cfg.msgId);
    if (msgEl) msgEl.textContent = ''; // clear stale message so we can tell it actually changed

    const addBtn = document.getElementById(cfg.addBtnId);
    if (addBtn) addBtn.click();
    await sleep(1200);

    const resultMsg = (msgEl && msgEl.textContent.trim()) || '';
    const success = /تم\s*الاضافة|تم\s*الإضافة/.test(resultMsg);

    log.push({
      status: success ? 'ok' : 'err',
      item,
      message: success ? 'تمت الإضافة (تأكدنا من رسالة HIO)' : `فشلت الإضافة — رسالة HIO: "${resultMsg || 'بدون رسالة'}"`,
    });
    idx++;
    persist();
    renderLog();
    updateProgress();
    await sleep(800);
  }

  async function runLoop() {
    running = true;
    renderControls();
    while (idx < queue.length && !paused) {
      await processItem();
      if (idx < queue.length) renderControls();
    }
    running = false;
    renderControls();
  }

  async function init() {
    const stored = await chrome.storage.local.get(['hioExportData', 'hioFillProgress']);
    if (!stored.hioExportData) {
      alert('لا توجد بيانات فاتورة مُصدَّرة. افتح صفحة فاتورة المريض فى نظام المستشفى واضغط "تصدير" أولاً من الإكستنشن.');
      panel.remove();
      return;
    }
    queue = buildQueue(stored.hioExportData);
    if (stored.hioFillProgress) {
      idx = stored.hioFillProgress.idx || 0;
      log = stored.hioFillProgress.log || [];
      paused = !!stored.hioFillProgress.paused;
    }
    updateProgress();
    renderLog();
    renderControls();
    if (!paused && idx < queue.length) {
      runLoop();
    }
  }

  init();
})();
