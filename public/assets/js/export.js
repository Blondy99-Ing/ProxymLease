
(function () {
  const $  = (s,c=document)=>c.querySelector(s);
  const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

  // --- Collecte "flat" : 1 seul dataset, avec une colonne Date en 1ère colonne
  function collectVisibleFlat() {
    const rows = [];
    let headers = null;

    $$('.date-section').forEach(sec => {
      const dateLabel = (sec.textContent || '').trim();
      const tableWrap = sec.nextElementSibling; // .table-container
      const table = tableWrap ? tableWrap.querySelector('table.table') : null;
      if (!table) return;

      const localHeaders = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());

      // On fige les en-têtes une seule fois, et on préfixe par "Date"
      if (!headers) headers = ['Date', ...localHeaders];

      table.querySelectorAll('tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return; // respecte filtres
        const cols = Array.from(tr.querySelectorAll('td')).map(td =>
          td.textContent.replace(/\s+\n\s+/g, ' ').trim()
        );
        rows.push([dateLabel, ...cols]);
      });
    });

    return { headers: headers || [], rows };
  }

  // --- EXCEL (HTML .xls) : 1 seul tableau, prêt à retraiter
  function exportExcelHTML(filename = 'leases') {
    const { headers, rows } = collectVisibleFlat();

    function escapeHtml(s) {
      return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    const thead = `<thead><tr>${headers.map(h=>`<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>`;
    const tbody = `<tbody>${
      rows.map(r=>`<tr>${r.map(c=>`<td>${escapeHtml(c)}</td>`).join('')}</tr>`).join('')
    }</tbody>`;

    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8" />
<title>Leases export</title>
<style>
  body{font-family:Arial,sans-serif}
  table{border-collapse:collapse;width:100%}
  th,td{border:1px solid #ccc;padding:6px 8px;font-size:12px}
  th{background:#f2f2f2}
  caption{font-weight:bold;margin-bottom:8px}
</style></head><body>
  <table>${thead}${tbody}</table>
</body></html>`;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.xls') ? filename : (filename + '.xls');
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  // --- CSV (BOM + ; comme séparateur) : 1 seul tableau
  function exportCSV(filename = 'leases') {
    const { headers, rows } = collectVisibleFlat();
    const sep = ';';

    function csvEscape(val) {
      const s = (val ?? '').toString().replace(/"/g,'""');
      return `"${s}"`;
    }

    const lines = [];
    if (headers.length) lines.push(headers.map(csvEscape).join(sep));
    rows.forEach(r => lines.push(r.map(csvEscape).join(sep)));

    const csv = lines.join('\r\n');
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.csv') ? filename : (filename + '.csv');
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  // --- PDF (fenêtre d’impression) : 1 seul tableau, un titre, la période
  function exportPDF() {
    const { headers, rows } = collectVisibleFlat();

    // petit garde-fou
    const hasData = headers.length && rows.length;

    const title = 'Export Leases';
    const period = `{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}`;

    const tableHead = headers.map(h=>`<th>${h}</th>`).join('');
    const tableBody = rows.map(r => `<tr>${r.map(c=>`<td>${c}</td>`).join('')}</tr>`).join('');

    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>${title}</title>
<style>
  @media print {
    @page { size: A4 landscape; margin: 12mm; }
  }
  body{font-family:Arial,sans-serif;margin:16px 18px}
  h1{margin:0 0 2px 0;font-size:18px}
  .meta{margin:0 0 12px 0;color:#555;font-size:12px}
  table{width:100%;border-collapse:collapse}
  th,td{border:1px solid #bbb;padding:6px 7px;font-size:11.5px;vertical-align:top}
  th{background:#f5f5f5}
  .empty{color:#666;font-style:italic;margin-top:8px}
</style></head><body>
  <h1>${title}</h1>
  <div class="meta">Période : ${period}</div>
  ${
    hasData
      ? `<table>
           <thead><tr>${tableHead}</tr></thead>
           <tbody>${tableBody}</tbody>
         </table>`
      : `<div class="empty">Aucune ligne visible à exporter.</div>`
  }
</body></html>`;

    const w = window.open('', '_blank');
    if (!w) { alert('Veuillez autoriser les pop-ups pour l’export PDF.'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(()=>{ w.print(); }, 250);
  }

  // Branchements
  $('.export-btn.export-excel')?.addEventListener('click', ()=> exportExcelHTML('leases'));
  $('.export-btn.export-csv')  ?.addEventListener('click', ()=> exportCSV('leases'));
  $('.export-btn.export-pdf')  ?.addEventListener('click',  exportPDF);

  // Expose (si besoin ailleurs)
  window.exportExcelHTML = exportExcelHTML;
  window.exportCSV       = exportCSV;
  window.exportPDF       = exportPDF;
})();

