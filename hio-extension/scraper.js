(async function () {
  const dataEl = document.getElementById('hio-export-data');
  if (!dataEl) {
    return { error: 'لم يتم العثور على بيانات فاتورة فى هذه الصفحة. افتح صفحة فاتورة مريض فى نظام المستشفى أولاً.' };
  }

  let payload;
  try {
    payload = JSON.parse(dataEl.textContent);
  } catch (e) {
    return { error: 'تعذر قراءة بيانات الفاتورة (تنسيق غير صالح).' };
  }

  await chrome.storage.local.set({ hioExportData: payload, hioFillProgress: null });
  return { data: payload };
})();
