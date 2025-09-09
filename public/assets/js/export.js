// === Collecte des blocs visibles (par date) ===
(function(){
  const $  = (s,c=document)=>c.querySelector(s);
  const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

  function getVisibleBlocks(){
    const blocks = [];
    document.querySelectorAll('.date-section').forEach(sec=>{
      const dateLabel = (sec.textContent || '').trim();
      const tableWrap = sec.nextElementSibling; // .table-container
      const table = tableWrap ? tableWrap.querySelector('table.table') : null;
      if (!table) return;

      const headers = Array.from(table.querySelectorAll('thead th')).map(th=>th.textContent.trim());
      const rows = [];
      table.querySelectorAll('tbody tr').forEach(tr=>{
        if (tr.style.display === 'none') return; // respecte filtres
        const cols = Array.from(tr.querySelectorAll('td')).map(td =>
          td.textContent.replace(/\s+\n\s+/g,' ').trim()
        );
        rows.push(cols);
      });
      blocks.push({ date: dateLabel, headers, rows });
    });
    return blocks;
  }

  // === EXCEL (HTML .xls) ===
  function exportExcelHTML(filename){
    const blocks = getVisibleBlocks();

    function escapeHtml(s){
      return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    const sections = blocks.map(b=>{
      if (!b.rows.length) return '';
      const thead = `<thead><tr>${b.headers.map(h=>`<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>`;
      const tbody = `<tbody>${
        b.rows.map(r=>`<tr>${r.map(c=>`<td>${escapeHtml(c)}</td>`).join('')}</tr>`).join('')
      }</tbody>`;
      return `
        <h3 style="margin:16px 0 6px;font-size:14px;">Date : ${escapeHtml(b.date)}</h3>
        <table border="1" cellspacing="0" cellpadding="4">${thead}${tbody}</table>
      `;
    }).join('') || '<div>Aucune ligne visible à exporter.</div>';

    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8" />
<title>Leases</title>
<style>
  body{font-family:Arial,sans-serif}
  table{border-collapse:collapse;margin-bottom:12px}
  th,td{font-size:12px}
  th{background:#f2f2f2}
</style></head><body>${sections}</body></html>`;

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

  // === CSV (avec BOM UTF-8, séparateur ; pour Excel FR) ===
  function exportCSV(filename){
    const blocks = getVisibleBlocks();
    const sep = ';';

    function csvEscape(val){
      const s = (val ?? '').toString().replace(/"/g,'""');
      return `"${s}"`;
    }

    const lines = [];
    blocks.forEach(b=>{
      if (!b.rows.length) return;
      // sous-titre de date (ligne vide + "Date: …")
      lines.push(`"Date"${sep}${csvEscape(b.date)}`);
      lines.push(b.headers.map(csvEscape).join(sep));
      b.rows.forEach(r => lines.push(r.map(csvEscape).join(sep)));
      lines.push(''); // espace entre sections
    });

    const csv = lines.join('\r\n');
    const BOM = '\uFEFF'; // UTF-8 BOM pour accents dans Excel
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

  // === PDF (impression navigateur) ===
  function exportPDF(){
    // On ouvre une fenêtre avec uniquement les sections & tables visibles
    const blocks = getVisibleBlocks();

    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Leases</title>
<style>
  body{font-family:Arial,sans-serif;margin:20px}
  h2{margin:0 0 6px 0}
  h3{margin:16px 0 6px 0}
  table{width:100%;border-collapse:collapse;margin-bottom:14px}
  th,td{border:1px solid #ccc;padding:6px 8px;font-size:12px}
  th{background:#f5f5f5}
  .meta{margin-bottom:10px;color:#666;font-size:12px}
</style></head><body>
  <h2>Export Leases</h2>
  <div class="meta">{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
  ${
    blocks.map(b=>{
      if (!b.rows.length) return '';
      return `
        <h3>Date : ${b.date}</h3>
        <table>
          <thead><tr>${b.headers.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
          <tbody>${
            b.rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join('')}</tr>`).join('')
          }</tbody>
        </table>`;
    }).join('') || '<div>Aucune ligne visible à exporter.</div>'
  }
</body></html>`;

    const w = window.open('', '_blank');
    if (!w) { alert('Veuillez autoriser les pop-ups pour l’export PDF.'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    // petit délai pour laisser le rendu se faire
    setTimeout(()=>{ w.print(); }, 250);
  }

  // === Branche les boutons ===
  const btnExcel = document.querySelector('.export-btn.export-excel');
  const btnCSV   = document.querySelector('.export-btn.export-csv');
  const btnPDF   = document.querySelector('.export-btn.export-pdf');

  btnExcel && btnExcel.addEventListener('click', ()=> exportExcelHTML('leases'));
  btnCSV   && btnCSV.addEventListener('click',   ()=> exportCSV('leases'));
  btnPDF   && btnPDF.addEventListener('click',   exportPDF);

  // Expose si tu veux réutiliser ailleurs
  window.exportExcelHTML = exportExcelHTML;
  window.exportCSV = exportCSV;
  window.exportPDF = exportPDF;
})();