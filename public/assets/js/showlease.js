(function(){
    const $ = (s, c=document) => c.querySelector(s);
    const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));

    // Tous les tableaux (un par jour)
    const tables = $$('.leases-table');
    const allRows = tables.flatMap(t => $$('.table tbody tr', t.parentElement ? t.parentElement : document) );

    const searchInput = $('#global-search');
    const fStatPen    = $('#filter-statut-penalite');
    const fStation    = $('#filter-station');
    const fSwappeur   = $('#filter-swappeur');
    const fPeriode    = $('#filter-periode');
    const inputDate   = $('#input-date');
    const inputStart  = $('#input-start');
    const inputEnd    = $('#input-end');

    const statCountTotal   = $('#stat-count-total');
    const statCountPayes   = $('#stat-count-payes');
    const statCountImpayes = $('#stat-count-impayes');
    const statSumLeases    = $('#stat-sum-leases');
    const statCountPen     = $('#stat-count-penalites');
    const statCountPenLeg  = $('#stat-count-pen-leg');
    const statCountPenGra  = $('#stat-count-pen-gra');
    const statSumPen       = $('#stat-sum-penalites');

    function normalize(s){ return (s||'').toString().toLowerCase(); }

    function matchStatutPenalite(row){
        const val = fStatPen.value;
        if (!val) return true;
        const st  = row.dataset.statut;
        const pen = row.dataset.penalite;
        if (val === 'payé'   && st === 'payé')   return true;
        if (val === 'impayé' && st === 'impayé') return true;
        if (val === 'sans_penalite'   && pen === 'sans pénalité')   return true;
        if (val === 'penalite_legere' && pen === 'pénalité légère') return true;
        if (val === 'penalite_grave'  && pen === 'pénalité grave')  return true;
        if (val === 'penalite_all' && (pen === 'pénalité légère' || pen === 'pénalité grave')) return true;
        return false;
    }
    const matchStation = (row) => !fStation.value || row.dataset.station === fStation.value;
    const matchSwappeur= (row) => !normalize(fSwappeur.value) || normalize(row.dataset.swappeur).includes(normalize(fSwappeur.value));
    const matchSearch  = (row) => !normalize(searchInput.value) || normalize(row.dataset.search).includes(normalize(searchInput.value));

    function formatFCFA(n){ return Number(n||0).toLocaleString('fr-FR', { maximumFractionDigits: 0 }); }

    function applyFiltersAndUpdateStats(){
        let visible=0, payes=0, impayes=0, sumLeases=0, penLeg=0, penGra=0, sumPen=0;

        allRows.forEach(row=>{
            const show = matchSearch(row) && matchStatutPenalite(row) && matchStation(row) && matchSwappeur(row);
            row.style.display = show ? '' : 'none';
            if(!show) return;

            visible++;
            if (row.dataset.statut === 'payé')   payes++;
            if (row.dataset.statut === 'impayé') impayes++;
            sumLeases += Number(row.dataset.total || 0);

            if (row.dataset.penalite === 'pénalité légère') penLeg++;
            if (row.dataset.penalite === 'pénalité grave')  penGra++;
            sumPen += Number(row.dataset.penAmount || 0);
        });

        statCountTotal.textContent   = visible;
        statCountPayes.textContent   = payes;
        statCountImpayes.textContent = impayes;
        statSumLeases.textContent    = formatFCFA(sumLeases);
        statCountPen.textContent     = penLeg + penGra;
        statCountPenLeg.textContent  = penLeg;
        statCountPenGra.textContent  = penGra;
        statSumPen.textContent       = formatFCFA(sumPen);
    }

    // debounce sur la recherche
    let t=null; const onSearch=()=>{ clearTimeout(t); t=setTimeout(applyFiltersAndUpdateStats,200); };

    searchInput.addEventListener('input', onSearch);
    fStatPen.addEventListener('change', applyFiltersAndUpdateStats);
    fStation.addEventListener('change', applyFiltersAndUpdateStats);
    fSwappeur.addEventListener('change', applyFiltersAndUpdateStats);

    // Gestion UI Date: affiche datepicker(s) correct(s) + autosubmit
    function syncDateInputsVisibility(){
        const mode = fPeriode.value;
        inputDate.style.display  = (mode==='date')  ? '' : 'none';
        inputStart.style.display = (mode==='range') ? '' : 'none';
        inputEnd.style.display   = (mode==='range') ? '' : 'none';
    }
    syncDateInputsVisibility();

    function submitWithParams(params){
        const url = new URL(window.location.href);
        url.searchParams.set('date_mode', params.date_mode || 'today');
        if (params.date)       url.searchParams.set('date', params.date);       else url.searchParams.delete('date');
        if (params.start_date) url.searchParams.set('start_date', params.start_date); else url.searchParams.delete('start_date');
        if (params.end_date)   url.searchParams.set('end_date', params.end_date);     else url.searchParams.delete('end_date');
        window.location.assign(url.toString());
    }

    fPeriode.addEventListener('change', ()=>{
        syncDateInputsVisibility();
        const mode = fPeriode.value;
        if (mode==='today' || mode==='week' || mode==='month' || mode==='year'){
            submitWithParams({ date_mode: mode });
        }
    });

    inputDate.addEventListener('change', ()=>{
        if (inputDate.value) submitWithParams({ date_mode:'date', date: inputDate.value });
    });

    function trySubmitRange(){
        if (inputStart.value && inputEnd.value){
            submitWithParams({ date_mode:'range', start_date: inputStart.value, end_date: inputEnd.value });
        }
    }
    inputStart.addEventListener('change', trySubmitRange);
    inputEnd.addEventListener('change', trySubmitRange);

    // init
    applyFiltersAndUpdateStats();
})();











// remplir les colonnes 
const fillFromDataset = (ds) => {
  fChauf.value   = ds.getAttribute('data-chauffeur') || '';
  fChaufId.value = ds.getAttribute('data-chauffeur-id') || '';
  fContratId.value = ds.getAttribute('data-contrat') || '';

  const moto = Number(ds.getAttribute('data-moto') || 0);
  const bat  = Number(ds.getAttribute('data-batterie') || 0);
  fMoto.value = isNaN(moto) ? 0 : Math.max(0, Math.floor(moto));
  fBat.value  = isNaN(bat)  ? 0 : Math.max(0, Math.floor(bat));
  fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;

  // 🔽 Priorité : dates du CONTRAT > fallback (date de la ligne) > aujourd'hui
  const dc = ds.getAttribute('data-date-concerne'); // du contrat
  const dl = ds.getAttribute('data-date-limite');   // du contrat
  const rowDate  = ds.getAttribute('data-date');    // de la ligne (paiement du jour)
  const todayStr = new Date().toISOString().slice(0,10);

  fDate.value     = (dc && dc.length) ? dc : (rowDate || todayStr);
  fDeadline.value = (dl && dl.length) ? dl : (rowDate || todayStr);
};
