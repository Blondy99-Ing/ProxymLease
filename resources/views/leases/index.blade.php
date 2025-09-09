@extends('layouts.app')

@section('content')
<!-- Contenu principal -->
<div class="container">
    <!-- En-tête de page -->
    <div class="page-header">
        <h1 class="page-title">Gestion des Leases</h1>
        <div class="date-badge" id="date-badge">
            {{-- Affiche la période (ex: 02/09/2025 - 05/09/2025) --}}
            {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}
        </div>
    </div>


    <!-- bloc de affichage message de succes ou d'erreur -->

    {{-- FLASH MESSAGES --}}
    <div id="flash-stack"
        style="position:fixed; right:1rem; top:1rem; z-index: 2000; display:flex; flex-direction:column; gap:.5rem;">
        {{-- Succès --}}
        @if (session('success'))
        <div class="flash flash-success" role="alert" aria-live="assertive">
            <strong>✅ Succès :</strong> {{ session('success') }}
            <button type="button" class="flash-close" aria-label="Fermer">✕</button>
        </div>
        @endif

        {{-- Erreur directe (ex: ->with('error', '...')) --}}
        @if (session('error'))
        <div class="flash flash-error" role="alert" aria-live="assertive">
            <strong>⚠️ Erreur :</strong> {{ session('error') }}
            <button type="button" class="flash-close" aria-label="Fermer">✕</button>
        </div>
        @endif

        {{-- Erreurs de validation --}}
        @if ($errors->any())
        <div class="flash flash-error" role="alert" aria-live="assertive">
            <strong>⚠️ Erreurs :</strong>
            <ul style="margin:.35rem 0 0 .95rem; padding:0;">
                @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="flash-close" aria-label="Fermer">✕</button>
        </div>
        @endif

        {{-- Info (optionnel) --}}
        @if (session('info'))
        <div class="flash flash-info" role="status" aria-live="polite">
            <strong>ℹ️ Info :</strong> {{ session('info') }}
            <button type="button" class="flash-close" aria-label="Fermer">✕</button>
        </div>
        @endif
    </div>



    <!-- Statistiques -->
    <div class="stats-grid" id="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-value">
                <span id="stat-count-total">0</span>
                <small class="text-muted">(payés: <span id="stat-count-payes">0</span>, impayés: <span
                        id="stat-count-impayes">0</span>)</small>
            </div>
            <div class="stat-label">Nombre de Leases Payés ou impayé</div>
            <div class="stat-date" id="stat-date-1">
                {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><span id="stat-sum-leases">0</span> FCFA</div>
            <div class="stat-label">Montant Leases payé ou impayé</div>
            <div class="stat-date" id="stat-date-2">
                {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-value">
                <span id="stat-count-penalites">0</span>
                <small class="text-muted">(légères: <span id="stat-count-pen-leg">0</span>, graves: <span
                        id="stat-count-pen-gra">0</span>)</small>
            </div>
            <div class="stat-label">Nombre de Pénalités</div>
            <div class="stat-date" id="stat-date-3">
                {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><span id="stat-sum-penalites">0</span> FCFA</div>
            <div class="stat-label">Montant Pénalités</div>
            <div class="stat-date" id="stat-date-4">
                {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Contrôles -->
    <div class="controls">
        <div class="search-box">
            <input id="global-search" type="text" class="search-input" placeholder="Rechercher un swap...">
            <button class="search-btn" type="button">🔍</button>
        </div>
        <div class="filters">
            <select id="filter-statut-penalite" class="filter-select">
                <option value="">Tous les Statuts</option>
                <option value="penalite_all">Penalités</option>
                <option value="penalite_legere">Penalités Legère</option>
                <option value="penalite_grave">Penalités Grave</option>
                <option value="sans_penalite">Sans Penalités</option>
                <option value="payé">payé</option>
                <option value="impayé">impayé</option>
            </select>

            <select id="filter-station" class="filter-select">
                <option value="">Toutes les stations</option>
                @foreach(($stations ?? []) as $st)
                <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>

            <select id="filter-swappeur" class="filter-select">
                <option value="">Tous les swappeurs</option>
                @foreach(($swappers ?? []) as $sw)
                <option value="{{ $sw }}">{{ $sw }}</option>
                @endforeach
            </select>

            <select id="filter-periode" class="filter-select">
                <option value="today" {{ ($dateMode ?? 'today')==='today' ? 'selected' : '' }}>Aujourd'hui</option>
                <option value="week" {{ ($dateMode ?? '')==='week'  ? 'selected' : '' }}>Cette Semaine</option>
                <option value="month" {{ ($dateMode ?? '')==='month' ? 'selected' : '' }}>Ce Mois</option>
                <option value="year" {{ ($dateMode ?? '')==='year'  ? 'selected' : '' }}>Cette Année</option>
                <option value="date" {{ ($dateMode ?? '')==='date'  ? 'selected' : '' }}>Date Specifique</option>
                <option value="range" {{ ($dateMode ?? '')==='range' ? 'selected' : '' }}>Plage de Date</option>
            </select>

            {{-- Inputs date dynamiques (cachés par défaut, style inchangé) --}}
            <input id="input-date" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('date', \Illuminate\Support\Carbon::parse($date ?? now())->toDateString()) }}">
            <input id="input-start" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('start_date') }}">
            <input id="input-end" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('end_date') }}">
        </div>
    </div>

    <!-- Boutons d'export -->
    <div class="export-buttons">
        <button class="export-btn export-excel">📊 Exporter Excel</button>
        <button class="export-btn export-pdf">📄 Exporter PDF</button>
        <button class="export-btn export-csv">📋 Exporter CSV</button>
    </div>

    {{-- ====== DUPLICATION PAR DATE ====== --}}
    @php use Illuminate\Support\Str; @endphp

    @forelse(($buckets ?? []) as $theDate => $rows)
    <!-- Section date -->
    <div class="date-section">{{ \Illuminate\Support\Carbon::parse($theDate)->format('d/m/Y') }}</div>

    <!-- Table des données (une par jour) -->
    <div class="table-container">
        <table class="table leases-table">
            <thead>
                <tr>
                    <th>ID Utilisateur</th>
                    <th>Nom Utilisateur</th>
                    <th>ID Moto</th>
                    <th>VIN Moto</th>
                    <th>Montant Moto</th>
                    <th>Montant Batterie</th>
                    <th>Montant Total</th>
                    <th>Date concernée (paiement)</th>
                    <th>Date limite (paiement)</th>
                    <th>Station</th>
                    <th>Statut</th>
                    <th>Statut pénalité</th>
                    <th>Swappeur</th>
                    <th>Date Heure Swap</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $p)
                @php
                // --- Relations / infos affichées
                $u = optional(optional(optional($p->contratChauffeur)->association)->validatedUser);
                $m = optional(optional(optional($p->contratChauffeur)->association)->motosValide);
                $userId = $u->user_unique_id ?? '—';
                $userName = $u ? trim(($u->nom ?? '').' '.($u->prenom ?? '')) : '—';
                $motoId = $m->moto_unique_id ?? '—';
                $vin = $m->vin ?? '—';

                // --- Montants
                $montantMoto = is_null($p->montant_moto) ? null : (float)$p->montant_moto;
                $montantBatterie = is_null($p->montant_batterie) ? null : (float)$p->montant_batterie;
                $montantTotal = is_null($p->montant_total) ? null : (float)$p->montant_total;

                // --- Station / Swappeur
                $station = optional($p->userAgence)->exists
                ? (optional($p->userAgence->agence)->nom_agence ?? '—')
                : 'Direction';

                $statutUpper = strtoupper((string) ($p->statut_paiement ?? ''));
                $statutAff = $statutUpper === 'PAYE' ? 'payé' : ($statutUpper === 'IMPAYE' ? 'impayé' : '—');

                $penStatut = (string) ($p->statut_penalite_calcule ?? 'sans pénalité');
                $penAmount = (float) ($p->montant_penalites_inclus ?? 0);

                // --- Date/heure affichées (colonne 'Date Heure Swap')
                $dateStr = !empty($p->date_paiement)
                ? \Illuminate\Support\Carbon::parse($p->date_paiement)->format('d/m/Y')
                : \Illuminate\Support\Carbon::parse($theDate)->format('d/m/Y');

                $heureStr = !empty($p->heure_paiement) ? substr($p->heure_paiement, 0, 5) : '—';

                $swappeur = optional($p->userAgence)->exists
                ? trim(($p->userAgence->nom ?? '').' '.($p->userAgence->prenom ?? '')).' (Agence)'
                : (optional($p->enregistrePar)->exists
                ? trim(($p->enregistrePar->nom ?? '').' '.($p->enregistrePar->prenom ?? '')).' (Employé)'
                : '—');

                // ================================================================
                // ✅ DATES DU PAIEMENT (UNIQUEMENT) — définies AVANT usage
                // ================================================================
                $dcRaw = data_get($p, 'date_paiement_concerne'); // string|null
                $dlRaw = data_get($p, 'date_limite_paiement'); // string|null

                // Pour affichage tableau (format FR)
                $dateConcernPay = $dcRaw ? \Illuminate\Support\Carbon::parse($dcRaw)->format('d/m/Y') : '—';
                $dateLimitePay = $dlRaw ? \Illuminate\Support\Carbon::parse($dlRaw)->format('d/m/Y') : '—';

                // Pour data-attributes (format ISO yyyy-mm-dd)
                $dataDateConcerne = $dcRaw ? \Illuminate\Support\Carbon::parse($dcRaw)->toDateString() : '';
                $dataDateLimite = $dlRaw ? \Illuminate\Support\Carbon::parse($dlRaw)->toDateString() : '';

                // Date "de la ligne" (paiement existant ou bucket)
                $dataDateLigne = !empty($p->date_paiement)
                ? \Illuminate\Support\Carbon::parse($p->date_paiement)->toDateString()
                : \Illuminate\Support\Carbon::parse($theDate)->toDateString();
                @endphp


                @php
                // --- (tu gardes tes calculs existants...)

                // DATES DU PAIEMENT POUR L'AFFICHAGE (inchangé)
                $dcRawPay = data_get($p, 'date_paiement_concerne'); // string|null
                $dlRawPay = data_get($p, 'date_limite_paiement'); // string|null
                $dateConcernPay = $dcRawPay ? \Illuminate\Support\Carbon::parse($dcRawPay)->format('d/m/Y') : '—';
                $dateLimitePay = $dlRawPay ? \Illuminate\Support\Carbon::parse($dlRawPay)->format('d/m/Y') : '—';

                // ⚠️ NOUVEAU : DATES DU CONTRAT (pour pré-remplir le FORMULAIRE)
                $dcContratRaw = data_get($p, 'contratChauffeur.date_paiement_concerne');
                $dlContratRaw = data_get($p, 'contratChauffeur.date_limite_paiement');

                $dataDateConcerne = $dcContratRaw ? \Illuminate\Support\Carbon::parse($dcContratRaw)->toDateString() :
                '';
                $dataDateLimite = $dlContratRaw ? \Illuminate\Support\Carbon::parse($dlContratRaw)->toDateString() : '';

                // Date de la ligne (paiement existant ou bucket) -> fallback si contrat vide
                $dataDateLigne = !empty($p->date_paiement)
                ? \Illuminate\Support\Carbon::parse($p->date_paiement)->toDateString()
                : \Illuminate\Support\Carbon::parse($theDate)->toDateString();
                @endphp


                <tr data-search="{{ \Illuminate\Support\Str::of($userId.' '.$userName.' '.$motoId.' '.$vin.' '.$station.' '.$statutAff.' '.$penStatut.' '.$swappeur)->lower() }}"
                    data-statut="{{ $statutAff }}" data-penalite="{{ $penStatut }}" data-station="{{ $station }}"
                    data-swappeur="{{ \Illuminate\Support\Str::of($swappeur)->lower() }}"
                    data-total="{{ $montantTotal ?? 0 }}" data-pen-amount="{{ $penAmount }}">
                    <td>{{ $userId }}</td>
                    <td>{{ $userName }}</td>
                    <td>{{ $motoId }}</td>
                    <td>{{ $vin }}</td>
                    <td>{{ is_null($montantMoto) ? '—' : number_format($montantMoto, 0, ',', ' ') . ' FCFA' }}</td>
                    <td>{{ is_null($montantBatterie) ? '—' : number_format($montantBatterie, 0, ',', ' ') . ' FCFA' }}
                    </td>
                    <td class="fw-bold">
                        {{ is_null($montantTotal) ? '—' : number_format($montantTotal, 0, ',', ' ') . ' FCFA' }}</td>

                    {{-- ✅ Dates d’AFFICHAGE : seulement celles du paiement --}}
                    <td>{{ $dateConcernPay }}</td>
                    <td>{{ $dateLimitePay }}</td>

                    <td>{{ $station }}</td>
                    <td>{{ $statutAff }}</td>
                    <td>{{ $penStatut }}</td>
                    <td>{{ $swappeur }}</td>
                    <td>{{ $dateStr }} {{ $heureStr }}</td>

                    <td>
                        {{-- Bouton payer : on passe dates PAIEMENT (ou vides), + date de la ligne --}}
                        <button type="button" class="btn-pay-lease"
                            data-contrat="{{ optional($p->contratChauffeur)->id }}" data-chauffeur="{{ $userName }}"
                            data-chauffeur-id="{{ $u->id ?? '' }}"
                            data-moto="{{ (float) optional($p->contratChauffeur)->montant_engage ?? 0 }}"
                            data-batterie="{{ (float) optional($p->contratChauffeur)->montant_engage_batterie ?? 0 }}"
                            data-total="{{ is_null($montantTotal) ? 0 : $montantTotal }}"
                            {{-- ✅ on fournit les DATES DU CONTRAT au formulaire --}} data-date="{{ $dataDateLigne }}"
                            data-date-concerne="{{ $dataDateConcerne }}" data-date-limite="{{ $dataDateLimite }}"
                            style="padding:.4rem .75rem;border:1px solid var(--border-color);border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);cursor:pointer">
                            💳 Payer
                        </button>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center text-muted py-4">Aucun lease pour cette date.</td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>
    @empty
    <div class="date-section">{{ $label ?? '' }}</div>
    <div class="table-container">
        <div class="text-center text-muted py-4">Aucune donnée sur la période.</div>
    </div>
    @endforelse
</div>

{{-- JS: filtres instantanés + stats globales + gestion période (auto submit) --}}






<!-- MODALE PAIEMENT LEASE -->
<div id="leasePayModal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:1050;">
    <div class="modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>

    <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;max-width:720px;margin:5vh auto;background:var(--bg-card);
                color:var(--text-primary);border:1px solid var(--border-color);
                border-radius:.75rem;box-shadow:var(--shadow);">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:1rem 1.25rem;border-bottom:1px solid var(--border-color)">
            <h3 style="margin:0;font-size:1.1rem;">💳 Enregistrer un paiement de lease</h3>
            <button type="button" id="leasePayClose"
                style="background:none;border:1px solid var(--border-color);
                           color:var(--text-primary);padding:.35rem .6rem;border-radius:.35rem;cursor:pointer">✕</button>
        </div>

        <form id="leasePayForm" method="POST" action="{{ route('leases.pay') }}">
            @csrf
            <div style="padding:1rem 1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <!-- CHAUFFEUR (autocomplete) -->
                <div style="grid-column:1/-1;position:relative">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Chauffeur</label>
                    <input type="text" id="pay_chauffeur" name="chauffeur_label" autocomplete="off"
                        placeholder="Tapez pour rechercher…" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);" />
                    <input type="hidden" id="pay_chauffeur_id" name="chauffeur_id">
                    <input type="hidden" id="pay_contrat_id" name="contrat_id">

                    <!-- suggestions -->
                    <div id="chauffeur_suggest" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:10;
                                background:var(--bg-card);border:1px solid var(--border-color);
                                border-top:none;border-radius:0 0 .35rem .35rem;max-height:220px;overflow:auto"></div>
                </div>

                <!-- MONTANTS -->
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Montant Moto (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_moto" name="montant_moto" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Montant Batterie (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_batterie" name="montant_batterie" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Total (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_total" name="montant_total" readonly style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-secondary);color:var(--text-primary);">
                </div>
                <div style="">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Moyen de paiement</label>
                    <select id="pay_mode" name="methode_paiement" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                   border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                        <option value="especes">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>


                <!-- DATES -->
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Paiement concerné</label>
                    <input type="date" id="pay_date" name="date_paiement_concerne" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Date limite</label>
                    <input type="date" id="pay_deadline" name="date_limite_paiement" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                  border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>


                <!-- NOTE LIBRE (optionnelle, côté backend tu peux ignorer si tu veux) -->
                <div style="grid-column:1/-1">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Note (optionnel)</label>
                    <textarea name="note" rows="2"
                        style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                                     border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);"></textarea>
                </div>

            </div>

            <div
                style="padding:1rem 1.25rem;border-top:1px solid var(--border-color);display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" id="leasePayCancel"
                    style="background:var(--bg-card);color:var(--text-primary);
                               border:1px solid var(--border-color);padding:.55rem .9rem;border-radius:.35rem;cursor:pointer">
                    Annuler
                </button>
                <button type="submit" style="background:var(--accent-green);color:#fff;border:none;
                               padding:.55rem 1rem;border-radius:.35rem;cursor:pointer;">
                    Enregistrer le paiement
                </button>
            </div>
        </form>
    </div>
</div>


<script>
(function() {
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    // ---- MODAL refs
    const modal = $('#leasePayModal');
    const backdrop = modal?.querySelector('.modal-backdrop');
    const btnClose = $('#leasePayClose');
    const btnCancel = $('#leasePayCancel');

    const fForm = $('#leasePayForm');
    const fChauf = $('#pay_chauffeur');
    const fChaufId = $('#pay_chauffeur_id');
    const fContratId = $('#pay_contrat_id');
    const fMoto = $('#pay_moto');
    const fBat = $('#pay_batterie');
    const fTotal = $('#pay_total');
    const fDate = $('#pay_date');
    const fDeadline = $('#pay_deadline');
    const suggestBox = $('#chauffeur_suggest');







    // ---- Construire la liste chauffeurs (fallback DOM)
    // Format: { id, name, contrat_id, montant_moto, montant_batterie }
    const driverList = [];
    $$('.leases-table tbody tr').forEach(tr => {
        const chauffeur = (tr.children[1]?.textContent || '').trim(); // Nom Utilisateur
        if (!chauffeur || chauffeur === '—') return;
        const btn = tr.querySelector('.btn-pay-lease');
        const contrat_id = btn?.dataset.contrat || '';
        const moto = Number(btn?.dataset.moto || 0);
        const batt = Number(btn?.dataset.batterie || 0);
        const id = btn?.dataset.chauffeurId || '';
        // éviter doublons (clé name+contrat)
        const key = chauffeur + '|' + contrat_id;
        if (!driverList.find(d => (d.name + '|' + d.contrat_id) === key)) {
            driverList.push({
                id,
                name: chauffeur,
                contrat_id,
                montant_moto: moto,
                montant_batterie: batt
            });
        }
    });

    // ---- helpers
    const openModal = () => {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    const closeModal = () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        hideSuggest();
    };

    const hideSuggest = () => {
        if (suggestBox) {
            suggestBox.style.display = 'none';
            suggestBox.innerHTML = '';
        }
    };
    const showSuggest = (items) => {
        if (!suggestBox) return;
        if (!items.length) {
            hideSuggest();
            return;
        }
        suggestBox.innerHTML = items.map(it =>
            `<div class="sug-item" data-id="${it.id||''}" data-contrat="${it.contrat_id||''}"
            data-moto="${it.montant_moto||0}" data-batterie="${it.montant_batterie||0}"
            style="padding:.5rem .75rem;cursor:pointer;border-top:1px solid var(--border-color)">
         ${it.name}
       </div>`
        ).join('');
        suggestBox.style.display = 'block';
    };

   const fillFromDataset = (ds) => {
  fChauf.value   = ds.getAttribute('data-chauffeur') || '';
  fChaufId.value = ds.getAttribute('data-chauffeur-id') || '';
  fContratId.value = ds.getAttribute('data-contrat') || '';

  const moto = Number(ds.getAttribute('data-moto') || 0);
  const bat  = Number(ds.getAttribute('data-batterie') || 0);
  fMoto.value = isNaN(moto) ? 0 : Math.max(0, Math.floor(moto));
  fBat.value  = isNaN(bat)  ? 0 : Math.max(0, Math.floor(bat));
  fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;

  // ⚠️ IMPORTANT : on lit les dates venant du CONTRAT (transmises dans les data-attrs)
  // et si elles sont absentes -> on LAISSE LES CHAMPS VIDES (pas de fallback).
  const dc = ds.getAttribute('data-date-concerne'); // '' si contrat NULL
  const dl = ds.getAttribute('data-date-limite');   // '' si contrat NULL

  fDate.value     = (dc && dc.length) ? dc : '';
  fDeadline.value = (dl && dl.length) ? dl : '';
};


    const fillFromDriver = (d) => {
        fChauf.value = d.name || '';
        fChaufId.value = d.id || '';
        fContratId.value = d.contrat_id || '';
        fMoto.value = Math.max(0, Math.floor(d.montant_moto || 0));
        fBat.value = Math.max(0, Math.floor(d.montant_batterie || 0));
        fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;
    };

    const recalcTotal = () => {
        const a = Number(fMoto.value || 0);
        const b = Number(fBat.value || 0);
        fTotal.value = Math.max(0, Math.floor(a + b));
    };

    // ---- Listeners ouverture modale
    $$('.btn-pay-lease').forEach(btn => {
        btn.addEventListener('click', () => {
            fillFromDataset(btn);
            openModal();
            setTimeout(() => fChauf.focus(), 50);
        });
    });

    // ---- Fermer
    btnClose?.addEventListener('click', closeModal);
    btnCancel?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });

    // ---- Autocomplete chauffeur
    fChauf.addEventListener('input', () => {
        const q = (fChauf.value || '').toLowerCase();
        const items = (!q) ? [] :
            driverList.filter(d => d.name.toLowerCase().includes(q)).slice(0, 12);
        showSuggest(items);
    });
    fChauf.addEventListener('blur', () => setTimeout(hideSuggest, 150));

    suggestBox?.addEventListener('click', (ev) => {
        const it = ev.target.closest('.sug-item');
        if (!it) return;
        const d = {
            id: it.dataset.id || '',
            contrat_id: it.dataset.contrat || '',
            name: it.textContent.trim(),
            montant_moto: Number(it.dataset.moto || 0),
            montant_batterie: Number(it.dataset.batterie || 0),
        };
        fillFromDriver(d);
        hideSuggest();
    });

    // ---- Recalcul total si user personnalise
    fMoto.addEventListener('input', recalcTotal);
    fBat.addEventListener('input', recalcTotal);

    // ---- Sanity submit minimal (le backend validera)
    fForm.addEventListener('submit', (e) => {
        if (!fContratId.value) {
            e.preventDefault();
            alert('Contrat introuvable pour ce chauffeur.');
            return;
        }
        if (!fChaufId.value && !fChauf.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un chauffeur.');
            return;
        }
    });
})();
</script>












@endsection