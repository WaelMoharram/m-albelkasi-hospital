const dataBox   = document.getElementById('dataBox');
const btnExport = document.getElementById('btnExport');
const btnFill   = document.getElementById('btnFill');
const btnClear  = document.getElementById('btnClear');
const statusEl  = document.getElementById('status');

function setStatus(text) {
  statusEl.textContent = text;
}

function renderData(data) {
  if (!data) {
    dataBox.innerHTML = '<p class="muted">لا توجد بيانات فاتورة مُصدَّرة بعد.</p>';
    return;
  }
  dataBox.innerHTML =
    '<p><strong>' + data.patient_name + '</strong> — فاتورة #' + data.invoice_id + '</p>' +
    '<p class="muted">صُدِّرت: ' + new Date(data.exported_at).toLocaleString('ar-EG') + '</p>' +
    '<p>إجراءات/تحاليل: ' + data.procedures.length +
    ' — أدوية محلية: ' + data.local_medications.length +
    ' — أدوية مستوردة: ' + data.imported_medications.length +
    ' — مستلزمات: ' + data.supplies.length + '</p>';
}

chrome.storage.local.get(['hioExportData'], (res) => renderData(res.hioExportData));

async function getActiveTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  return tab;
}

btnExport.addEventListener('click', async () => {
  setStatus('جارٍ التصدير...');
  const tab = await getActiveTab();
  try {
    const [{ result }] = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      files: ['scraper.js'],
    });
    if (result && result.error) {
      setStatus('خطأ: ' + result.error);
    } else if (result && result.data) {
      renderData(result.data);
      setStatus('تم التصدير بنجاح.');
    }
  } catch (e) {
    setStatus('تعذر تشغيل التصدير: ' + e.message);
  }
});

btnFill.addEventListener('click', async () => {
  setStatus('جارٍ فتح لوحة المساعدة فى صفحة HIO...');
  const tab = await getActiveTab();
  try {
    // inpage.js runs in the page's MAIN world. Injecting it via executeScript
    // with world:'MAIN' bypasses the page/extension CSP that blocks inline
    // <script> injection — it's how we run code in page context (bridge +
    // clicking the anchor "اضافة" button) without an inline script.
    await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      world: 'MAIN',
      files: ['inpage.js'],
    });
    // filler.js runs in the isolated world (needs chrome.storage); it talks to
    // inpage.js via window.postMessage / shared DOM attributes.
    await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      files: ['filler.js'],
    });
    setStatus('تم فتح اللوحة — راجع صفحة HIO.');
    window.close();
  } catch (e) {
    setStatus('تعذر التشغيل هنا. تأكد أنك فاتح صفحة HIO: ' + e.message);
  }
});

btnClear.addEventListener('click', () => {
  chrome.storage.local.remove(['hioExportData', 'hioFillProgress'], () => {
    renderData(null);
    setStatus('تم مسح البيانات.');
  });
});
