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

                // Statut pénalité (texte lisible) — fourni par le controller
                $penStatut = trim((string) data_get($p, 'statut_penalite_calcule') ?: 'sans pénalité');

                // Montant de la pénalité (float)
                $penAmount = (float) (data_get($p, 'montant_penalites_inclus') ?? 0);

                // Type machine-friendly (pour filtres JS) : ex: RETARD_LEGER / RETARD_GRAVE / NONE
                $penType = data_get($p, 'statut_penalite_type') ?: 'NONE';

                // clé slug normalisée pour attribut data-penalite (ex: penalite_legere, penalite_grave, sans_penalite)
                $penKey = \Illuminate\Support\Str::of($penStatut)
                ->ascii() // retire accents
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->__toString();
                if ($penKey === '') $penKey = 'sans_penalite';

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
                    <td>
                        @if($penKey !== 'sans_penalite' && $penAmount > 0)
                        <span class="pen-badge"
                            style="display:inline-block;padding:.18rem .45rem;border-radius:.35rem;font-size:.85rem;">
                            {{ $penStatut }}
                        </span>
                        <small style="margin-left:.4rem;color:#666;">{{ number_format($penAmount,0,',',' ') }}
                            FCFA</small>

                        @if($penObj)
                        <button type="button" class="btn-pen-detail" style="margin-left:.45rem;cursor:pointer"
                            data-pen-id="{{ $penObj->id }}" data-pen-montant="{{ $penAmount }}"
                            data-pen-type="{{ e($penType) }}" data-pen-desc="{{ e($penObj->description ?? '') }}">
                            ⓘ
                        </button>
                        @endif
                        @else
                        <span style="color:#6c757d;font-size:.95rem;">sans pénalité</span>
                        @endif
                    </td>

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
        fChauf.value = ds.getAttribute('data-chauffeur') || '';
        fChaufId.value = ds.getAttribute('data-chauffeur-id') || '';
        fContratId.value = ds.getAttribute('data-contrat') || '';

        const moto = Number(ds.getAttribute('data-moto') || 0);
        const bat = Number(ds.getAttribute('data-batterie') || 0);
        fMoto.value = isNaN(moto) ? 0 : Math.max(0, Math.floor(moto));
        fBat.value = isNaN(bat) ? 0 : Math.max(0, Math.floor(bat));
        fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;

        // ⚠️ IMPORTANT : on lit les dates venant du CONTRAT (transmises dans les data-attrs)
        // et si elles sont absentes -> on LAISSE LES CHAMPS VIDES (pas de fallback).
        const dc = ds.getAttribute('data-date-concerne'); // '' si contrat NULL
        const dl = ds.getAttribute('data-date-limite'); // '' si contrat NULL

        fDate.value = (dc && dc.length) ? dc : '';
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





// ---- Filtre Penalite (remplace/ajoute)
(function(){
  const sel = document.getElementById('filter-statut-penalite');
  if (!sel) return;
  sel.addEventListener('change', () => {
    const val = (sel.value || '').toLowerCase();

    // mapping simple
    const map = {
      '': null,
      'penalite_all': 'all',
      'penalite_legere': 'penalite_legere',
      'penalite_grave': 'penalite_grave',
      'sans_penalite': 'sans_penalite',
      'payé': 'paye_filter',
      'impayé': 'impaye_filter'
    };

    const wanted = map[val] ?? val;

    document.querySelectorAll('.leases-table tbody tr').forEach(tr => {
      const rowPen = tr.dataset.penalite || 'sans_penalite';
      const rowStat = (tr.dataset.statut || '').toLowerCase();

      // filtre paiement si demandé
      if (wanted === 'paye_filter') {
        tr.style.display = (rowStat === 'payé') ? '' : 'none';
        return;
      }
      if (wanted === 'impaye_filter') {
        tr.style.display = (rowStat === 'impayé') ? '' : 'none';
        return;
      }

      // penalite_all => show all
      if (wanted === null || wanted === 'all') {
        tr.style.display = '';
        return;
      }

      // compare clé normalisée
      tr.style.display = (rowPen === wanted) ? '' : 'none';
    });
  });
})();

</script>







 /* =======================
     *    Enregistrement (paiement)
     * ======================= */

    /** Paiement du lease */
    public function pay(Request $request)
    {
        // Normaliser les noms venant du formulaire
        $request->merge([
            'methode_paiement'        => $request->input('methode_paiement') ?? $request->input('mode_paiement'),
            'date_paiement_concerne'  => $request->input('date_paiement_concerne') ?? $request->input('date_paiement'),
            'date_limite_paiement'    => $request->input('date_limite_paiement')   ?? $request->input('date_limite'),
            'notes'                   => $request->input('notes') ?? $request->input('note'),
        ]);

        // Validation
        $v = Validator::make($request->all(), [
            'contrat_id'              => ['required','integer','exists:contrats_contratchauffeur,id'],
            'montant_moto'            => ['nullable','numeric','min:0'],
            'montant_batterie'        => ['nullable','numeric','min:0'],
            'montant_total'           => ['nullable','numeric','min:0'],
            'methode_paiement'        => ['required','in:especes,mobile_money,autre'],
            'reference_transaction'   => ['nullable','string','max:100'],
            'date_paiement_concerne'  => ['date'],
            'date_limite_paiement'    => ['date','after_or_equal:date_paiement_concerne'],
            'notes'                   => ['nullable','string','max:500'],
        ], [
            'date_limite_paiement.after_or_equal' => 'La date limite doit être ≥ à la date concernée.',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        try {
            DB::transaction(function () use ($data) {
                // 1) Contrat
                /** @var \App\Models\ContratChauffeur $contrat */
                $contrat = ContratChauffeur::findOrFail($data['contrat_id']);

                // 2) Montants
                $moto = (float)($data['montant_moto'] ?? 0);
                $bat  = (float)($data['montant_batterie'] ?? 0);
                $tot  = isset($data['montant_total']) ? (float)$data['montant_total'] : ($moto + $bat);
                if (abs(($moto + $bat) - $tot) > 0.0001) {
                    $tot = $moto + $bat;
                }

                // 3) Références / temps
                $now   = Carbon::now();
                $heure = $now->format('H:i:s.u');
                $ref   = 'PL-'.$now->format('Ymd-His').'-'.Str::upper(Str::random(5));

                $statutGlobal  = $tot  > 0 ? 'PAYE' : 'IMPAYE';
                $statutMoto    = $moto > 0 ? 'PAYE' : 'IMPAYE';
                $statutBatt    = $bat  > 0 ? 'PAYE' : 'IMPAYE';
                $estPenalite   = $now->format('H:i') > '12:00';

                // 4) ID de l’employé connecté
                $enregistreParId = Auth::id();
                if (!$enregistreParId && Auth::guard('employe')->check()) {
                    $enregistreParId = Auth::guard('employe')->id();
                }

                // 5) Création du paiement
                PaiementLease::create([
                    'reference'                => $ref,
                    'montant_moto'             => $moto,
                    'montant_batterie'         => $bat,
                    'montant_total'            => $tot,

                    'date_paiement'            => $now->toDateString(),
                    'date_enregistrement'      => $now,
                    'heure_paiement'           => $heure,

                    'date_paiement_concerne'   => $data['date_paiement_concerne'],
                    'date_limite_paiement'     => $data['date_limite_paiement'],

                    'methode_paiement'         => $data['methode_paiement'] ?? null,
                    'reference_transaction'    => $data['reference_transaction'] ?? null,
                    'type_contrat'             => 'CHAUFFEUR',

                    'statut_paiement'          => $statutGlobal,
                    'statut_paiement_moto'     => $statutMoto,
                    'statut_paiement_batterie' => $statutBatt,

                    'est_penalite'             => $estPenalite,
                    'inclut_penalites'         => false,
                    'montant_penalites_inclus' => 0,

                    'contrat_chauffeur_id'     => $contrat->id,
                    'contrat_batterie_id'      => null,
                    'contrat_partenaire_id'    => null,

                    // Employé qui enregistre
                    'enregistre_par_id'        => $enregistreParId ?: null,

                    // Les swappeurs users_agences ne sont pas gérés ici
                    'user_agence_id'           => null,

                    'notes'                    => $data['notes'] ?? null,
                ]);

                // 6) Avancement du contrat
                $contrat->increment('montant_paye', $tot);
                $contrat->update([
                    'montant_restant' => max(0, (float)$contrat->montant_total - (float)$contrat->montant_paye),
                ]);

                // 7) Prochaines dates à partir des dates saisies
                $baseConcerned = Carbon::parse($data['date_paiement_concerne'])->startOfDay();
                $baseLimit     = Carbon::parse($data['date_limite_paiement'])->startOfDay();

                $hadGap = $baseLimit->gt($baseConcerned); // limite > concernée ?

                // Prochaine "concernée" = +1 jour ; dimanche -> lundi
                $nextConcerned = $baseConcerned->copy()->addDay();
                if ($nextConcerned->isSunday()) {
                    $nextConcerned->addDay();
                }

                // Prochaine "limite"
                if ($hadGap) {
                    $nextLimit = $nextConcerned->copy()->addDay();
                    if ($nextLimit->isSunday()) {
                        $nextLimit->addDay();
                    }
                } else {
                    $nextLimit = $nextConcerned->copy();
                }

                // Persister sur le contrat
                $contrat->date_paiement_concerne = $nextConcerned->toDateString();
                $contrat->date_limite_paiement   = $nextLimit->toDateString();
                $contrat->save();
            });

            return back()->with('success', 'Paiement enregistré avec succès.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Erreur base de données : '.$e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Une erreur est survenue lors du paiement : '.$e->getMessage())->withInput();
        }
    }




@endsection








<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Models\PaiementLease;
use App\Models\ContratChauffeur;
use App\Models\UserAgence;
use App\Models\ApplicationPenalite;

class LeaseController extends Controller
{
    /**
     * Map préchargée des pénalités pour la plage demandée.
     * Clef: "{contrat_id}|{yyyy-mm-dd}" => ApplicationPenalite
     */
    private array $penaltiesMap = [];

    public function index(Request $request)
    {
        // 1) Période (comme avant)
        [$start, $end, $dateMode, $label] = $this->resolvePeriod($request);

        // 2) Toutes les dates inclusives
        $dates = $this->datesInRange($start, $end);

        // 3) Précharger pénalités (comme avant)
        $this->penaltiesMap = $this->loadPenaltiesForRange($start, $end);

        // 4a) Buckets “CONCERNÉE” (une ligne contrat/jour → payé/impayé)
        $buckets = [];
        foreach ($dates as $d) {
            $buckets[$d] = $this->buildRowsForDate($d);
        }

        // 4b) Buckets “ENREGISTREMENT” (une ligne par paiement → doublons permis)
        $bucketsEnreg = [];
        foreach ($dates as $d) {
            $bucketsEnreg[$d] = $this->buildRowsForEnregDate($d);
        }

        // 5) Filtres dynamiques (stations/swappeurs) — dérivés des lignes chargées
        $collectAll = function(array $bucketMap) {
            return collect($bucketMap)->flatMap(fn($rows) => $rows); // Collection<row>
        };
        $allRows = $collectAll($buckets)->merge($collectAll($bucketsEnreg));

        $stations = $allRows
            ->map(function ($r) {
                if (optional($r->userAgence)->exists) {
                    return optional($r->userAgence->agence)->nom_agence ?? '—';
                }
                return 'Direction';
            })
            ->filter()->unique()->sort()->values()->all();

        $swappers = $allRows
            ->map(function ($r) {
                if (optional($r->userAgence)->exists) {
                    return trim(($r->userAgence->nom ?? '').' '.($r->userAgence->prenom ?? ''));
                }
                if (optional($r->enregistrePar)->exists) {
                    return trim(($r->enregistrePar->nom ?? '').' '.($r->enregistrePar->prenom ?? ''));
                }
                return null;
            })
            ->filter()->unique()->sort()->values()->all();

        $date = $start;

        return view('leases.index', compact(
            'buckets',       // concernée
            'bucketsEnreg',  // enregistrement
            'date',
            'dateMode',
            'label',
            'stations',
            'swappers'
        ));
    }

    /* =======================
     *   Chargement pénalités
     * ======================= */

    /**
     * Charge les pénalités non annulées entre $start et $end inclus.
     * Retourne une map ["{contrat_id}|{yyyy-mm-dd}" => ApplicationPenalite]
     */
    private function loadPenaltiesForRange(string $start, string $end): array
    {
        $rows = ApplicationPenalite::query()
            ->whereDate('date_paiement_manque', '>=', $start)
            ->whereDate('date_paiement_manque', '<=', $end)
            ->where(function ($q) {
                $q->whereNull('statut')
                  ->orWhereNotIn('statut', ['annulee', 'ANNULÉE', 'CANCELLED']);
            })
            ->get();

        $map = [];
        foreach ($rows as $p) {
            $date = $p->date_paiement_manque ? Carbon::parse($p->date_paiement_manque)->toDateString() : null;
            if (!$date) continue;
            $key = $p->contrat_chauffeur_id . '|' . $date;

            if (!isset($map[$key])) {
                $map[$key] = $p;
            } else {
                if (!empty($p->date_creation) && !empty($map[$key]->date_creation) && $p->date_creation > $map[$key]->date_creation) {
                    $map[$key] = $p;
                }
            }
        }

        return $map;
    }

    /**
     * Infos pénalité pour un contrat + date.
     */
    private function getPenaltyInfo(int $contratId, string $dateToCheck): array
    {
        $key = $contratId . '|' . $dateToCheck;
        $pen = $this->penaltiesMap[$key] ?? null;

        if (!$pen) {
            return ['label'=>'sans pénalité','type'=>'NONE','amount'=>0.0,'obj'=>null];
        }

        $typeRaw = strtoupper((string)($pen->type_penalite ?? ''));
        $amount  = (float)($pen->montant ?? 0);

        if (str_contains($typeRaw, 'GRAVE') || str_contains($typeRaw, 'GRAV') || $amount >= 5000) {
            return ['label'=>'pénalité grave','type'=>($typeRaw ?: 'RETARD_GRAVE'),'amount'=>$amount,'obj'=>$pen];
        }
        if (str_contains($typeRaw, 'LEGER') || $amount > 0) {
            return ['label'=>'pénalité légère','type'=>($typeRaw ?: 'RETARD_LEGER'),'amount'=>$amount,'obj'=>$pen];
        }
        return ['label'=>'sans pénalité','type'=>'NONE','amount'=>$amount,'obj'=>$pen];
    }

    /* =======================
     *        Helpers
     * ======================= */

    /** today|week|month|year|date|range -> [startDate, endDate, mode, label] */
    private function resolvePeriod(Request $request): array
    {
        $mode  = $request->get('date_mode', 'today');
        $today = Carbon::today();

        switch ($mode) {
            case 'week':
                $start = $today->copy()->startOfWeek(Carbon::MONDAY);
                $end   = $today->copy()->endOfWeek(Carbon::SUNDAY);
                break;

            case 'month':
                $start = $today->copy()->startOfMonth();
                $end   = $today->copy()->endOfMonth();
                break;

            case 'year':
                $start = $today->copy()->startOfYear();
                $end   = $today->copy()->endOfYear();
                break;

            case 'date': {
                $date = $request->get('date');
                try { $d = $date ? Carbon::parse($date) : $today; } catch (\Throwable $e) { $d = $today; }
                $start = $d->copy()->startOfDay();
                $end   = $d->copy()->endOfDay();
                break;
            }

            case 'range': {
                $s = $request->get('start_date');
                $e = $request->get('end_date');
                try { $start = $s ? Carbon::parse($s)->startOfDay() : $today->copy()->startOfDay(); } catch (\Throwable $ex) { $start = $today->copy()->startOfDay(); }
                try { $end   = $e ? Carbon::parse($e)->endOfDay()   : $start->copy()->endOfDay(); }   catch (\Throwable $ex) { $end   = $start->copy()->endOfDay(); }
                if ($end->lessThan($start)) [$start, $end] = [$end, $start];
                break;
            }

            case 'today':
            default:
                $start = $today->copy()->startOfDay();
                $end   = $today->copy()->endOfDay();
                break;
        }

        $label = $start->format('d/m/Y') . ($start->isSameDay($end) ? '' : ' - ' . $end->format('d/m/Y'));
        return [$start->toDateString(), $end->toDateString(), $mode, $label];
    }

    /** Liste de dates (YYYY-MM-DD) inclusives */
    private function datesInRange(string $start, string $end): array
    {
        $dates = [];
        $cur  = Carbon::parse($start)->startOfDay();
        $last = Carbon::parse($end)->startOfDay();
        while ($cur->lte($last)) {
            $dates[] = $cur->toDateString();
            $cur->addDay();
        }
        return $dates;
    }

    /**
     * Construit les lignes “CONCERNÉE” pour un jour (une ligne contrat/jour).
     */
    private function buildRowsForDate(string $date)
    {
        // Contrats actifs ce jour-là
        $contrats = $this->expectedContractsQuery($date)
            ->with([
                'association:id,validated_user_id,moto_valide_id',
                'association.validatedUser:id,user_unique_id,nom,prenom',
                'association.motosValide:id,vin,moto_unique_id',
            ])
            ->orderBy('id')
            ->get();

        // Paiements dont date_paiement_concerne == $date
        $paymentsByContrat = $this->paymentsForDate($date);

        return $contrats->map(function (ContratChauffeur $contrat) use ($paymentsByContrat, $date) {
            /** @var \App\Models\PaiementLease|null $payment */
            $payment = $this->pickPaymentForContrat($paymentsByContrat, $contrat->id);

            $row = new \stdClass();
            $row->contratChauffeur = $contrat;

            // Date à vérifier pour la pénalité
            $dateToCheck = ($payment && $payment->date_paiement_concerne)
                ? Carbon::parse($payment->date_paiement_concerne)->toDateString()
                : $date;

            $penInfo = $this->getPenaltyInfo($contrat->id, $dateToCheck);

            if ($payment) {
                // PAYÉ
                $row->statut_paiement   = 'PAYE';
                $row->montant_moto      = $payment->montant_moto;
                $row->montant_batterie  = $payment->montant_batterie;
                $row->montant_total     = $payment->montant_total;
                $row->date_paiement     = $payment->date_paiement;            // affichage enreg → OK
                $row->heure_paiement    = $payment->heure_paiement;
                $row->est_penalite      = (bool)($payment->est_penalite ?? false);

                $row->userAgence        = $payment->userAgence ?? null;
                $row->enregistrePar     = $payment->enregistrePar ?? null;

                $row->statut_penalite_calcule   = $penInfo['label'];
                $row->statut_penalite_type      = $penInfo['type'];
                $row->montant_penalites_inclus  = $penInfo['amount'];
                $row->penalite                  = $penInfo['obj'];

                $row->date_paiement_concerne = $payment->date_paiement_concerne
                    ? Carbon::parse($payment->date_paiement_concerne)->toDateString() : null;
                $row->date_limite_paiement   = $payment->date_limite_paiement
                    ? Carbon::parse($payment->date_limite_paiement)->toDateString()   : null;

                $row->paiement = $payment;
            } else {
                // IMPAYÉ
                $row->statut_paiement   = 'IMPAYE';
                $row->montant_moto      = null;
                $row->montant_batterie  = null;
                $row->montant_total     = null;
                $row->date_paiement     = null;   // <<< pas de date enreg pour impayé
                $row->heure_paiement    = null;
                $row->est_penalite      = false;

                $row->userAgence        = null;
                $row->enregistrePar     = null;

                $row->statut_penalite_calcule   = $penInfo['label'];
                $row->statut_penalite_type      = $penInfo['type'];
                $row->montant_penalites_inclus  = $penInfo['amount'];
                $row->penalite                  = $penInfo['obj'];

                // dates concernées/limite : on peut reprendre celles du contrat
                $row->date_paiement_concerne = $contrat->date_paiement_concerne ?? null;
                $row->date_limite_paiement   = $contrat->date_limite_paiement   ?? null;
            }

            return $row;
        });
    }

    /** Contrats actifs ce jour-là */
    private function expectedContractsQuery(string $date)
    {
        return ContratChauffeur::query()
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date);
    }

    /**
     * Paiements d’un jour (groupés par contrat) — filtrés par date_paiement_concerne
     */
    private function paymentsForDate(string $date)
    {
        $payments = PaiementLease::with([
                'userAgence:id,nom,prenom,id_agence',
                'userAgence.agence:id,nom_agence',
                'enregistrePar:id,nom,prenom',
            ])
            ->whereDate('date_paiement_concerne', $date)
            ->orderByDesc('date_enregistrement')
            ->get();

        return $payments->groupBy('contrat_chauffeur_id')->map(fn($g) => $g->first());
    }

    /** Récupérer un paiement par contrat (ou null) */
    private function pickPaymentForContrat($paymentsByContrat, int $contratId): ?PaiementLease
    {
        return $paymentsByContrat->get($contratId);
    }

    /* =======================
     *   Vue ENREGISTREMENT
     * ======================= */

    /** Paiements d’un jour par date_paiement (sans regrouper par contrat) */
    private function paymentsByEnregForDate(string $date)
    {
        return PaiementLease::with([
                'userAgence:id,nom,prenom,id_agence',
                'userAgence.agence:id,nom_agence',
                'enregistrePar:id,nom,prenom',
                'contratChauffeur:id,association_id,montant_engage,montant_engage_batterie,date_paiement_concerne,date_limite_paiement',
                'contratChauffeur.association:id,validated_user_id,moto_valide_id',
                'contratChauffeur.association.validatedUser:id,user_unique_id,nom,prenom',
                'contratChauffeur.association.motosValide:id,vin,moto_unique_id',
            ])
            ->whereDate('date_paiement', $date)
            ->orderBy('heure_paiement')
            ->get();
    }

    /** Construit les lignes “enregistrement” (une ligne par paiement) */
    private function buildRowsForEnregDate(string $date)
    {
        $payments = $this->paymentsByEnregForDate($date);

        return $payments->map(function (PaiementLease $p) use ($date) {
            $row = new \stdClass();

            $row->contratChauffeur = $p->contratChauffeur;
            $row->statut_paiement  = strtoupper((string)($p->statut_paiement ?? '')) ?: 'PAYE';
            $row->montant_moto     = $p->montant_moto;
            $row->montant_batterie = $p->montant_batterie;
            $row->montant_total    = $p->montant_total;

            $row->date_paiement    = $p->date_paiement;     // affichage enreg
            $row->heure_paiement   = $p->heure_paiement;

            $row->userAgence       = $p->userAgence;
            $row->enregistrePar    = $p->enregistrePar;

            $row->date_paiement_concerne = $p->date_paiement_concerne;
            $row->date_limite_paiement   = $p->date_limite_paiement;

            $dateToCheck = $p->date_paiement_concerne
                ? Carbon::parse($p->date_paiement_concerne)->toDateString()
                : $date;

            $penInfo = $this->getPenaltyInfo((int)$p->contrat_chauffeur_id, $dateToCheck);
            $row->statut_penalite_calcule   = $penInfo['label'];
            $row->statut_penalite_type      = $penInfo['type'];
            $row->montant_penalites_inclus  = $penInfo['amount'];
            $row->penalite                  = $penInfo['obj'];

            $row->paiement = $p;
            $row->row_kind = 'enreg';
            return $row;
        });
    }

    // (Optionnel) données pour la modale
    public function getPaymentModalData(int $contratId)
    {
        $contrat = ContratChauffeur::findOrFail($contratId);

        return view('leases.modal_payment', [
            'contrat' => $contrat,
            'date_paiement_concerne' => $contrat->date_paiement_concerne ?? '',
            'date_limite_paiement'   => $contrat->date_limite_paiement ?? '',
        ]);
    }

    /* =======================
     *    Enregistrement (paiement)
     * ======================= */

    /** Paiement du lease */
    public function pay(Request $request)
    {
        // Normaliser les champs
        $request->merge([
            'methode_paiement'        => $request->input('methode_paiement') ?? $request->input('mode_paiement'),
            'date_paiement_concerne'  => $request->input('date_paiement_concerne') ?? $request->input('date_paiement'),
            'date_limite_paiement'    => $request->input('date_limite_paiement')   ?? $request->input('date_limite'),
            'notes'                   => $request->input('notes') ?? $request->input('note'),
        ]);

        $v = Validator::make($request->all(), [
            'contrat_id'              => ['required','integer','exists:contrats_contratchauffeur,id'],
            'montant_moto'            => ['nullable','numeric','min:0'],
            'montant_batterie'        => ['nullable','numeric','min:0'],
            'montant_total'           => ['nullable','numeric','min:0'],
            'methode_paiement'        => ['required','in:especes,mobile_money,autre'],
            'reference_transaction'   => ['nullable','string','max:100'],
            'date_paiement_concerne'  => ['date'],
            'date_limite_paiement'    => ['date','after_or_equal:date_paiement_concerne'],
            'notes'                   => ['nullable','string','max:500'],
        ], [
            'date_limite_paiement.after_or_equal' => 'La date limite doit être ≥ à la date concernée.',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        try {
            DB::transaction(function () use ($data) {
                /** @var ContratChauffeur $contrat */
                $contrat = ContratChauffeur::findOrFail($data['contrat_id']);

                $moto = (float)($data['montant_moto'] ?? 0);
                $bat  = (float)($data['montant_batterie'] ?? 0);
                $tot  = isset($data['montant_total']) ? (float)$data['montant_total'] : ($moto + $bat);
                if (abs(($moto + $bat) - $tot) > 0.0001) $tot = $moto + $bat;

                $now   = Carbon::now();
                $heure = $now->format('H:i:s.u');
                $ref   = 'PL-'.$now->format('Ymd-His').'-'.Str::upper(Str::random(5));

                $statutGlobal  = $tot  > 0 ? 'PAYE' : 'IMPAYE';
                $statutMoto    = $moto > 0 ? 'PAYE' : 'IMPAYE';
                $statutBatt    = $bat  > 0 ? 'PAYE' : 'IMPAYE';
                $estPenalite   = $now->format('H:i') > '12:00';

                $enregistreParId = Auth::id();
                if (!$enregistreParId && Auth::guard('employe')->check()) {
                    $enregistreParId = Auth::guard('employe')->id();
                }

                PaiementLease::create([
                    'reference'                => $ref,
                    'montant_moto'             => $moto,
                    'montant_batterie'         => $bat,
                    'montant_total'            => $tot,

                    'date_paiement'            => $now->toDateString(),
                    'date_enregistrement'      => $now,
                    'heure_paiement'           => $heure,

                    'date_paiement_concerne'   => $data['date_paiement_concerne'],
                    'date_limite_paiement'     => $data['date_limite_paiement'],

                    'methode_paiement'         => $data['methode_paiement'] ?? null,
                    'reference_transaction'    => $data['reference_transaction'] ?? null,
                    'type_contrat'             => 'CHAUFFEUR',

                    'statut_paiement'          => $statutGlobal,
                    'statut_paiement_moto'     => $statutMoto,
                    'statut_paiement_batterie' => $statutBatt,

                    'est_penalite'             => $estPenalite,
                    'inclut_penalites'         => false,
                    'montant_penalites_inclus' => 0,

                    'contrat_chauffeur_id'     => $contrat->id,
                    'contrat_batterie_id'      => null,
                    'contrat_partenaire_id'    => null,

                    'enregistre_par_id'        => $enregistreParId ?: null,
                    'user_agence_id'           => null,

                    'notes'                    => $data['notes'] ?? null,
                ]);

                // Avancement du contrat
                $contrat->increment('montant_paye', $tot);
                $contrat->update([
                    'montant_restant' => max(0, (float)$contrat->montant_total - (float)$contrat->montant_paye),
                ]);

                // Prochaines dates
                $baseConcerned = Carbon::parse($data['date_paiement_concerne'])->startOfDay();
                $baseLimit     = Carbon::parse($data['date_limite_paiement'])->startOfDay();
                $hadGap        = $baseLimit->gt($baseConcerned);

                $nextConcerned = $baseConcerned->copy()->addDay();
                if ($nextConcerned->isSunday()) $nextConcerned->addDay();

                if ($hadGap) {
                    $nextLimit = $nextConcerned->copy()->addDay();
                    if ($nextLimit->isSunday()) $nextLimit->addDay();
                } else {
                    $nextLimit = $nextConcerned->copy();
                }

                $contrat->date_paiement_concerne = $nextConcerned->toDateString();
                $contrat->date_limite_paiement   = $nextLimit->toDateString();
                $contrat->save();
            });

            return back()->with('success', 'Paiement enregistré avec succès.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Erreur base de données : '.$e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Une erreur est survenue lors du paiement : '.$e->getMessage())->withInput();
        }
    }
}










@extends('layouts.app')

@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="container">
    <!-- En-tête -->
    <div class="page-header">
        <h1 class="page-title">Gestion des Leases</h1>
        <div class="date-badge" id="date-badge">
            {{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    <div id="flash-stack" style="position:fixed; right:1rem; top:1rem; z-index: 2000; display:flex; flex-direction:column; gap:.5rem;">
        @if (session('success'))
            <div class="flash flash-success" role="alert" aria-live="assertive">
                <strong>✅ Succès :</strong> {{ session('success') }}
                <button type="button" class="flash-close" aria-label="Fermer">✕</button>
            </div>
        @endif

        @if (session('error'))
            <div class="flash flash-error" role="alert" aria-live="assertive">
                <strong>⚠️ Erreur :</strong> {{ session('error') }}
                <button type="button" class="flash-close" aria-label="Fermer">✕</button>
            </div>
        @endif

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

        @if (session('info'))
            <div class="flash flash-info" role="status" aria-live="polite">
                <strong>ℹ️ Info :</strong> {{ session('info') }}
                <button type="button" class="flash-close" aria-label="Fermer">✕</button>
            </div>
        @endif
    </div>

    <!-- Stats -->
    <div class="stats-grid" id="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-value">
                <span id="stat-count-total">0</span>
                <small class="text-muted">(payés: <span id="stat-count-payes">0</span>, impayés: <span id="stat-count-impayes">0</span>)</small>
            </div>
            <div class="stat-label">Nombre de Leases Payés ou impayé</div>
            <div class="stat-date" id="stat-date-1">{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><span id="stat-sum-leases">0</span> FCFA</div>
            <div class="stat-label">Montant Leases payé ou impayé</div>
            <div class="stat-date" id="stat-date-2">{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-value">
                <span id="stat-count-penalites">0</span>
                <small class="text-muted">(légères: <span id="stat-count-pen-leg">0</span>, graves: <span id="stat-count-pen-gra">0</span>)</small>
            </div>
            <div class="stat-label">Nombre de Pénalités</div>
            <div class="stat-date" id="stat-date-3">{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><span id="stat-sum-penalites">0</span> FCFA</div>
            <div class="stat-label">Montant Pénalités</div>
            <div class="stat-date" id="stat-date-4">{{ $label ?? \Illuminate\Support\Carbon::parse($date ?? now())->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Contrôles (UN SEUL BLOC) -->
    <div class="controls">
        <div class="search-box">
            <input id="global-search" type="text" class="search-input" placeholder="Rechercher un swap...">
            <button class="search-btn" type="button">🔍</button>
        </div>
        <div class="filters">
            <select id="filter-statut-penalite" class="filter-select">
                <option value="">Tous les Statuts</option>
                <option value="penalite_all">Toutes les pénalités</option>
                <option value="penalite_legere">Pénalités légères</option>
                <option value="penalite_grave">Pénalités graves</option>
                <option value="sans_penalite">Sans pénalité</option>
                <option value="payé">Payé</option>
                <option value="impayé">Impayé</option>
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

            <!-- Période unique : today/week/month/year/range => ENREG | date => CONCERNÉE -->
            <select id="filter-periode" class="filter-select">
                <option value="today" {{ ($dateMode ?? 'today')==='today' ? 'selected' : '' }}>Aujourd'hui</option>
                <option value="week"  {{ ($dateMode ?? '')==='week'  ? 'selected' : '' }}>Cette Semaine</option>
                <option value="month" {{ ($dateMode ?? '')==='month' ? 'selected' : '' }}>Ce Mois</option>
                <option value="year"  {{ ($dateMode ?? '')==='year'  ? 'selected' : '' }}>Cette Année</option>
                <option value="date"  {{ ($dateMode ?? '')==='date'  ? 'selected' : '' }}>Date spécifique (concernée)</option>
                <option value="range" {{ ($dateMode ?? '')==='range' ? 'selected' : '' }}>Plage de dates (enreg.)</option>
            </select>

            <!-- Inputs dynamiques -->
            <input id="input-date"  type="date" class="filter-select" style="display:none; width:auto;" value="{{ request('date', \Illuminate\Support\Carbon::parse($date ?? now())->toDateString()) }}">
            <input id="input-start" type="date" class="filter-select" style="display:none; width:auto;" value="{{ request('start_date') }}">
            <input id="input-end"   type="date" class="filter-select" style="display:none; width:auto;" value="{{ request('end_date') }}">
        </div>
    </div>

    <!-- Boutons d'export -->
    <div class="export-buttons">
        <button class="export-btn export-excel">📊 Exporter Excel</button>
        <button class="export-btn export-pdf">📄 Exporter PDF</button>
        <button class="export-btn export-csv">📋 Exporter CSV</button>
    </div>

    <!-- ========================= -->
    <!-- TABLEAU UNIQUE (tbody mix)-->
    <!-- ========================= -->
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
                    <th>Date Heure Enregistrement</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php $hasAnyRow = false; @endphp

                {{-- LIGNES ENREG (paiements saisis) --}}
                @foreach(array_reverse($bucketsEnreg ?? [], true) as $theDate => $rows)
                    @foreach($rows as $p)
                        @php
                            $u = optional(optional(optional($p->contratChauffeur)->association)->validatedUser);
                            $m = optional(optional(optional($p->contratChauffeur)->association)->motosValide);

                            $userId   = $u->user_unique_id ?? '—';
                            $userName = $u ? trim(($u->nom ?? '').' '.($u->prenom ?? '')) : '—';
                            $motoId   = $m->moto_unique_id ?? '—';
                            $vin      = $m->vin ?? '—';

                            $montantMoto     = is_null($p->montant_moto) ? null : (float)$p->montant_moto;
                            $montantBatterie = is_null($p->montant_batterie) ? null : (float)$p->montant_batterie;
                            $montantTotal    = is_null($p->montant_total) ? null : (float)$p->montant_total;

                            $station = optional($p->userAgence)->exists
                                ? (optional($p->userAgence->agence)->nom_agence ?? '—')
                                : 'Direction';

                            $statutUpper = strtoupper((string)($p->statut_paiement ?? 'PAYE'));
                            $statutAff   = $statutUpper === 'IMPAYE' ? 'impayé' : 'payé';

                            $penStatut = trim((string) data_get($p, 'statut_penalite_calcule') ?: 'sans pénalité');
                            $penAmount = (float) (data_get($p, 'montant_penalites_inclus') ?? 0);
                            $penType   = data_get($p, 'statut_penalite_type') ?: 'NONE';
                            $penObj    = data_get($p, 'penalite') ?? null;

                            $penKey = Str::of($penStatut)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/','_')->trim('_')->__toString() ?: 'sans_penalite';

                            $dateConcernPay = ($d = data_get($p,'date_paiement_concerne')) ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
                            $dateLimitePay  = ($d = data_get($p,'date_limite_paiement'))   ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';

                            $enregISO   = $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->toDateString() : '';
                            $concernISO = $p->date_paiement_concerne ? \Carbon\Carbon::parse($p->date_paiement_concerne)->toDateString() : '';

                            $dateStr  = $enregISO ? \Carbon\Carbon::parse($enregISO)->format('d/m/Y') : '—';
                            $heureStr = !empty($p->heure_paiement) ? substr($p->heure_paiement,0,5) : '—';

                            $swappeur = optional($p->userAgence)->exists
                                ? trim(($p->userAgence->nom ?? '').' '.($p->userAgence->prenom ?? '')).' (Agence)'
                                : (optional($p->enregistrePar)->exists
                                    ? trim(($p->enregistrePar->nom ?? '').' '.($p->enregistrePar->prenom ?? '')).' (Employé)'
                                    : '—');

                            $hasAnyRow = true;
                        @endphp
                        <tr class="row-enreg"
                            data-kind="enreg"
                            data-enreg-date="{{ $enregISO }}"
                            data-concern-date="{{ $concernISO }}"
                            data-statut="{{ $statutAff }}"
                            data-paiement-status="{{ strtolower($statutAff) }}"
                            data-penalite="{{ $penKey }}" data-penalite-type="{{ $penType }}"
                            data-pen-id="{{ $penObj->id ?? '' }}"
                            data-total="{{ $montantTotal ?? 0 }}" data-pen-amount="{{ $penAmount }}"
                            data-station="{{ $station }}"
                            data-swappeur="{{ Str::of($swappeur)->lower() }}"
                            data-search="{{ Str::of($userId.' '.$userName.' '.$motoId.' '.$vin.' '.$station.' '.$statutAff.' '.$penStatut.' '.$swappeur)->lower() }}">
                            <td>{{ $userId }}</td>
                            <td>{{ $userName }}</td>
                            <td>{{ $motoId }}</td>
                            <td>{{ $vin }}</td>
                            <td>{{ is_null($montantMoto) ? '—' : number_format($montantMoto,0,',',' ') }} FCFA</td>
                            <td>{{ is_null($montantBatterie) ? '—' : number_format($montantBatterie,0,',',' ') }} FCFA</td>
                            <td class="fw-bold">{{ is_null($montantTotal) ? '—' : number_format($montantTotal,0,',',' ') }} FCFA</td>
                            <td>{{ $dateConcernPay }}</td>
                            <td>{{ $dateLimitePay }}</td>
                            <td>{{ $station }}</td>
                            <td>{{ $statutAff }}</td>
                            <td>
                                @if($penKey !== 'sans_penalite' && $penAmount > 0)
                                    <span class="pen-badge" style="display:inline-block;padding:.18rem .45rem;border-radius:.35rem;font-size:.85rem;">{{ $penStatut }}</span>
                                    <small style="margin-left:.4rem;color:#666;">{{ number_format($penAmount,0,',',' ') }} FCFA</small>
                                    @if($penObj)
                                        <button type="button" class="btn-pen-detail" style="margin-left:.45rem;cursor:pointer"
                                                data-pen-id="{{ $penObj->id }}" data-pen-montant="{{ $penAmount }}"
                                                data-pen-type="{{ e($penType) }}" data-pen-desc="{{ e($penObj->description ?? '') }}">ⓘ</button>
                                    @endif
                                @else
                                    <span style="color:#6c757d;font-size:.95rem;">sans pénalité</span>
                                @endif
                            </td>
                            <td>{{ $swappeur }}</td>
                            <td>{{ $dateStr }} {{ $heureStr }}</td>
                            <td>
                                <button type="button" class="btn-pay-lease"
                                    data-contrat="{{ optional($p->contratChauffeur)->id }}"
                                    data-chauffeur="{{ $userName }}"
                                    data-chauffeur-id="{{ $u->id ?? '' }}"
                                    data-moto="{{ (float) optional($p->contratChauffeur)->montant_engage ?? 0 }}"
                                    data-batterie="{{ (float) optional($p->contratChauffeur)->montant_engage_batterie ?? 0 }}"
                                    data-date-concerne-contrat="{{ optional($p->contratChauffeur)->date_paiement_concerne ?? '' }}"
                                    data-date-limite-contrat="{{ optional($p->contratChauffeur)->date_limite_paiement ?? '' }}">
                                    💳 Payer
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endforeach

                {{-- LIGNES CONCERNÉE (contrats attendus) --}}
                @foreach(array_reverse($buckets ?? [], true) as $theDate => $rows)
                    @foreach($rows as $p)
                        @php
                            $u = optional(optional(optional($p->contratChauffeur)->association)->validatedUser);
                            $m = optional(optional(optional($p->contratChauffeur)->association)->motosValide);

                            $userId   = $u->user_unique_id ?? '—';
                            $userName = $u ? trim(($u->nom ?? '').' '.($u->prenom ?? '')) : '—';
                            $motoId   = $m->moto_unique_id ?? '—';
                            $vin      = $m->vin ?? '—';

                            $montantMoto     = is_null($p->montant_moto) ? null : (float)$p->montant_moto;
                            $montantBatterie = is_null($p->montant_batterie) ? null : (float)$p->montant_batterie;
                            $montantTotal    = is_null($p->montant_total) ? null : (float)$p->montant_total;

                            $station = optional($p->userAgence)->exists
                                ? (optional($p->userAgence->agence)->nom_agence ?? '—')
                                : 'Direction';

                            $statutUpper = strtoupper((string) ($p->statut_paiement ?? ''));
                            $statutAff = $statutUpper === 'PAYE' ? 'payé' : ($statutUpper === 'IMPAYE' ? 'impayé' : 'impayé');

                            $penStatut = trim((string) data_get($p, 'statut_penalite_calcule') ?: 'sans pénalité');
                            $penAmount = (float) (data_get($p, 'montant_penalites_inclus') ?? 0);
                            $penType   = data_get($p, 'statut_penalite_type') ?: 'NONE';
                            $penObj    = data_get($p, 'penalite') ?? null;

                            $penKey = Str::of($penStatut)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/','_')->trim('_')->__toString() ?: 'sans_penalite';

                            $dcRaw = data_get($p, 'date_paiement_concerne');
                            $dlRaw = data_get($p, 'date_limite_paiement');

                            $dateConcernPay = $dcRaw ? \Carbon\Carbon::parse($dcRaw)->format('d/m/Y') : \Carbon\Carbon::parse($theDate)->format('d/m/Y');
                            $dateLimitePay  = $dlRaw ? \Carbon\Carbon::parse($dlRaw)->format('d/m/Y') : '—';

                            $concernISO = $dcRaw ? \Carbon\Carbon::parse($dcRaw)->toDateString() : \Carbon\Carbon::parse($theDate)->toDateString();

                            $dateStr  = !empty($p->date_paiement) ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : '—';
                            $heureStr = !empty($p->heure_paiement) ? substr($p->heure_paiement,0,5) : '—';

                            $swappeur = optional($p->userAgence)->exists
                                ? trim(($p->userAgence->nom ?? '').' '.($p->userAgence->prenom ?? '')).' (Agence)'
                                : (optional($p->enregistrePar)->exists
                                    ? trim(($p->enregistrePar->nom ?? '').' '.($p->enregistrePar->prenom ?? '')).' (Employé)'
                                    : '—');

                            $hasAnyRow = true;
                        @endphp
                        <tr class="row-concern"
                            data-kind="concern"
                            data-enreg-date=""
                            data-concern-date="{{ $concernISO }}"
                            data-statut="{{ $statutAff }}"
                            data-paiement-status="{{ strtolower($statutAff) }}"
                            data-penalite="{{ $penKey }}" data-penalite-type="{{ $penType }}"
                            data-pen-id="{{ $penObj->id ?? '' }}"
                            data-total="{{ $montantTotal ?? 0 }}" data-pen-amount="{{ $penAmount }}"
                            data-station="{{ $station }}"
                            data-swappeur="{{ Str::of($swappeur)->lower() }}"
                            data-search="{{ Str::of($userId.' '.$userName.' '.$motoId.' '.$vin.' '.$station.' '.$statutAff.' '.$penStatut.' '.$swappeur)->lower() }}">
                            <td>{{ $userId }}</td>
                            <td>{{ $userName }}</td>
                            <td>{{ $motoId }}</td>
                            <td>{{ $vin }}</td>
                            <td>{{ is_null($montantMoto) ? '—' : number_format($montantMoto,0,',',' ') }} FCFA</td>
                            <td>{{ is_null($montantBatterie) ? '—' : number_format($montantBatterie,0,',',' ') }} FCFA</td>
                            <td class="fw-bold">{{ is_null($montantTotal) ? '—' : number_format($montantTotal,0,',',' ') }} FCFA</td>
                            <td>{{ $dateConcernPay }}</td>
                            <td>{{ $dateLimitePay }}</td>
                            <td>{{ $station }}</td>
                            <td>{{ $statutAff }}</td>
                            <td>
                                @if($penKey !== 'sans_penalite' && $penAmount > 0)
                                    <span class="pen-badge" style="display:inline-block;padding:.18rem .45rem;border-radius:.35rem;font-size:.85rem;">{{ $penStatut }}</span>
                                    <small style="margin-left:.4rem;color:#666;">{{ number_format($penAmount,0,',',' ') }} FCFA</small>
                                    @if($penObj)
                                        <button type="button" class="btn-pen-detail" style="margin-left:.45rem;cursor:pointer"
                                                data-pen-id="{{ $penObj->id }}" data-pen-montant="{{ $penAmount }}"
                                                data-pen-type="{{ e($penType) }}" data-pen-desc="{{ e($penObj->description ?? '') }}">ⓘ</button>
                                    @endif
                                @else
                                    <span style="color:#6c757d;font-size:.95rem;">sans pénalité</span>
                                @endif
                            </td>
                            <td>{{ $swappeur }}</td>
                            <td>{{ $dateStr }} {{ $heureStr }}</td>
                            <td>
                                <button type="button" class="btn-pay-lease"
                                    data-contrat="{{ optional($p->contratChauffeur)->id }}"
                                    data-chauffeur="{{ $userName }}"
                                    data-chauffeur-id="{{ $u->id ?? '' }}"
                                    data-moto="{{ (float) optional($p->contratChauffeur)->montant_engage ?? 0 }}"
                                    data-batterie="{{ (float) optional($p->contratChauffeur)->montant_engage_batterie ?? 0 }}"
                                    data-date-concerne-contrat="{{ optional($p->contratChauffeur)->date_paiement_concerne ?? '' }}"
                                    data-date-limite-contrat="{{ optional($p->contratChauffeur)->date_limite_paiement ?? '' }}">
                                    💳 Payer
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endforeach

                @if(!$hasAnyRow)
                    <tr><td colspan="15" class="text-center text-muted py-4">Aucune donnée sur la période.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- MODALE PAIEMENT LEASE -->
<div id="leasePayModal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:1050;">
    <div class="modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    <div class="modal-panel" role="dialog" aria-modal="true" style="position:relative;max-width:720px;margin:5vh auto;background:var(--bg-card); color:var(--text-primary);border:1px solid var(--border-color); border-radius:.75rem;box-shadow:var(--shadow);">
        <div style="display:flex;align-items:center;justify-content:space-between; padding:1rem 1.25rem;border-bottom:1px solid var(--border-color)">
            <h3 style="margin:0;font-size:1.1rem;">💳 Enregistrer un paiement de lease</h3>
            <button type="button" id="leasePayClose" style="background:none;border:1px solid var(--border-color); color:var(--text-primary);padding:.35rem .6rem;border-radius:.35rem;cursor:pointer">✕</button>
        </div>

        <form id="leasePayForm" method="POST" action="{{ route('leases.pay') }}">
            @csrf
            <div style="padding:1rem 1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;position:relative">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Chauffeur</label>
                    <input type="text" id="pay_chauffeur" name="chauffeur_label" autocomplete="off" placeholder="Tapez pour rechercher…" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);" />
                    <input type="hidden" id="pay_chauffeur_id" name="chauffeur_id">
                    <input type="hidden" id="pay_contrat_id" name="contrat_id">
                    <div id="chauffeur_suggest" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:10; background:var(--bg-card);border:1px solid var(--border-color); border-top:none;border-radius:0 0 .35rem .35rem;max-height:220px;overflow:auto"></div>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Montant Moto (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_moto" name="montant_moto" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Montant Batterie (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_batterie" name="montant_batterie" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Total (FCFA)</label>
                    <input type="number" min="0" step="1" id="pay_total" name="montant_total" readonly style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-secondary);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Moyen de paiement</label>
                    <select id="pay_mode" name="methode_paiement" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                        <option value="especes">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Paiement concerné</label>
                    <input type="date" id="pay_date" name="date_paiement_concerne" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Date limite</label>
                    <input type="date" id="pay_deadline" name="date_limite_paiement" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);">
                </div>

                <div style="grid-column:1/-1">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Note (optionnel)</label>
                    <textarea name="note" rows="2" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color); border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);"></textarea>
                </div>
            </div>

            <div style="padding:1rem 1.25rem;border-top:1px solid var(--border-color);display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" id="leasePayCancel" style="background:var(--bg-card);color:var(--text-primary); border:1px solid var(--border-color);padding:.55rem .9rem;border-radius:.35rem;cursor:pointer">Annuler</button>
                <button type="submit" style="background:var(--accent-green);color:#fff;border:none; padding:.55rem 1rem;border-radius:.35rem;cursor:pointer;">Enregistrer le paiement</button>
            </div>
        </form>
    </div>
</div>

{{-- ======================= --}}
{{-- JS: modal + filtres    --}}
{{-- ======================= --}}
<script>
(function() {
    const $  = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    // ---- MODAL refs
    const modal     = $('#leasePayModal');
    const backdrop  = modal?.querySelector('.modal-backdrop');
    const btnClose  = $('#leasePayClose');
    const btnCancel = $('#leasePayCancel');

    const fForm     = $('#leasePayForm');
    const fChauf    = $('#pay_chauffeur');
    const fChaufId  = $('#pay_chauffeur_id');
    const fContratId= $('#pay_contrat_id');
    const fMoto     = $('#pay_moto');
    const fBat      = $('#pay_batterie');
    const fTotal    = $('#pay_total');
    const fDate     = $('#pay_date');
    const fDeadline = $('#pay_deadline');
    const suggestBox= $('#chauffeur_suggest');

    // Construire la liste chauffeurs (fallback DOM)
    const driverList = [];
    $$('.leases-table tbody tr').forEach(tr => {
        const chauffeur = (tr.children[1]?.textContent || '').trim();
        if (!chauffeur || chauffeur === '—') return;
        const btn  = tr.querySelector('.btn-pay-lease');
        const contrat_id = btn?.dataset.contrat || '';
        const moto = Number(btn?.dataset.moto || 0);
        const batt = Number(btn?.dataset.batterie || 0);
        const id   = btn?.dataset.chauffeurId || '';
        const key  = chauffeur + '|' + contrat_id;
        if (!driverList.find(d => (d.name + '|' + d.contrat_id) === key)) {
            driverList.push({ id, name: chauffeur, contrat_id, montant_moto: moto, montant_batterie: batt });
        }
    });

    const openModal  = () => { modal.style.display = 'block'; document.body.style.overflow = 'hidden'; };
    const closeModal = () => { modal.style.display = 'none';  document.body.style.overflow = '';       hideSuggest(); };
    const hideSuggest= () => { if (suggestBox) { suggestBox.style.display='none'; suggestBox.innerHTML=''; } };
    const showSuggest= (items) => {
        if (!suggestBox) return;
        if (!items.length) return hideSuggest();
        suggestBox.innerHTML = items.map(it =>
            `<div class="sug-item" data-id="${it.id||''}" data-contrat="${it.contrat_id||''}"
                  data-moto="${it.montant_moto||0}" data-batterie="${it.montant_batterie||0}"
                  style="padding:.5rem .75rem;cursor:pointer;border-top:1px solid var(--border-color)">${it.name}</div>`
        ).join('');
        suggestBox.style.display = 'block';
    };

    const fillFromDataset = (ds) => {
        fChauf.value    = ds.getAttribute('data-chauffeur') || '';
        fChaufId.value  = ds.getAttribute('data-chauffeur-id') || '';
        fContratId.value= ds.getAttribute('data-contrat') || '';
        fMoto.value     = Number(ds.getAttribute('data-moto') || 0);
        fBat.value      = Number(ds.getAttribute('data-batterie') || 0);
        fTotal.value    = (Number(fMoto.value || 0) + Number(fBat.value || 0));
        fDate.value     = ds.getAttribute('data-date-concerne-contrat') || '';
        fDeadline.value = ds.getAttribute('data-date-limite-contrat') || '';
    };

    const fillFromDriver = (d) => {
        fChauf.value = d.name || '';
        fChaufId.value = d.id || '';
        fContratId.value = d.contrat_id || '';
        fMoto.value = Math.max(0, Math.floor(d.montant_moto || 0));
        fBat.value  = Math.max(0, Math.floor(d.montant_batterie || 0));
        fTotal.value= (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;
    };

    const recalcTotal = () => {
        const a = Number(fMoto.value || 0), b = Number(fBat.value || 0);
        fTotal.value = Math.max(0, Math.floor(a + b));
    };

    $$('.btn-pay-lease').forEach(btn => {
        btn.addEventListener('click', () => {
            fillFromDataset(btn);
            openModal();
            setTimeout(() => fChauf.focus(), 50);
        });
    });

    btnClose?.addEventListener('click', closeModal);
    btnCancel?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.style.display === 'block') closeModal(); });

    fChauf.addEventListener('input', () => {
        const q = (fChauf.value || '').toLowerCase();
        const items = (!q) ? [] : driverList.filter(d => d.name.toLowerCase().includes(q)).slice(0, 12);
        showSuggest(items);
    });
    fChauf.addEventListener('blur', () => setTimeout(hideSuggest, 150));
    suggestBox?.addEventListener('click', (ev) => {
        const it = ev.target.closest('.sug-item'); if (!it) return;
        fillFromDriver({
            id: it.dataset.id || '', contrat_id: it.dataset.contrat || '',
            name: it.textContent.trim(),
            montant_moto: Number(it.dataset.moto || 0),
            montant_batterie: Number(it.dataset.batterie || 0)
        });
        hideSuggest();
    });

    fMoto.addEventListener('input', recalcTotal);
    fBat.addEventListener('input', recalcTotal);

    fForm.addEventListener('submit', (e) => {
        if (!fContratId.value) { e.preventDefault(); alert('Contrat introuvable pour ce chauffeur.'); return; }
        if (!fChaufId.value && !fChauf.value) { e.preventDefault(); alert('Veuillez sélectionner un chauffeur.'); return; }
    });
})();
</script>

<script>
// === Filtres (un seul bloc) ===
document.addEventListener('DOMContentLoaded', () => {
  const selPenalty = document.getElementById('filter-statut-penalite');
  const selStation = document.getElementById('filter-station');
  const selSwap    = document.getElementById('filter-swappeur');

  const selPeriod  = document.getElementById('filter-periode'); // today|week|month|year|date|range
  const inDate     = document.getElementById('input-date');
  const inStart    = document.getElementById('input-start');
  const inEnd      = document.getElementById('input-end');

  const rowsEnreg   = Array.from(document.querySelectorAll('tr.row-enreg'));
  const rowsConcern = Array.from(document.querySelectorAll('tr.row-concern'));

  // Stats
  const statTotal  = document.getElementById('stat-count-total');
  const statPayes  = document.getElementById('stat-count-payes');
  const statImpayes= document.getElementById('stat-count-impayes');
  const statPenT   = document.getElementById('stat-count-penalites');
  const statPenL   = document.getElementById('stat-count-pen-leg');
  const statPenG   = document.getElementById('stat-count-pen-gra');
  const statSumLea = document.getElementById('stat-sum-leases');
  const statSumPen = document.getElementById('stat-sum-penalites');

  // Search
  const searchInput = document.getElementById('global-search');

  const at0=(d)=> d ? new Date(d.getFullYear(),d.getMonth(),d.getDate()) : null;
  function parseISO(iso){ if(!iso) return null; const p=String(iso).split('-'); if(p.length!==3) return null; const d=new Date(+p[0],+p[1]-1,+p[2]); return isNaN(d)?null:at0(d); }
  function weekBounds(today){ const first=new Date(today); const weekday=today.getDay(); // 0=Dim
    const diff = (weekday===0 ? -6 : 1 - weekday); // Lundi début
    first.setDate(today.getDate()+diff);
    const last=new Date(first); last.setDate(first.getDate()+6); return [at0(first),at0(last)];
  }
  function dateMatchesEnreg(d, mode, one, from, to){
    if(!d) return false;
    const today=at0(new Date()); const y=today.getFullYear(); const m=today.getMonth(); const [wStart,wEnd]=weekBounds(today);
    switch(mode){
      case 'today': return d.getTime()===today.getTime();
      case 'week':  return d>=wStart && d<=wEnd;
      case 'month': return d.getMonth()===m && d.getFullYear()===y;
      case 'year':  return d.getFullYear()===y;
      case 'range':
        if (from && d<from) return false;
        if (to   && d>to)   return false;
        return true;
      default: return true;
    }
  }
  function penaltyMatches(row, value){
    if(!value) return true;
    if(value==='penalite_all'){ return (parseFloat(row.dataset.penAmount)||0)>0; }
    if(value==='penalite_legere' || value==='penalite_grave' || value==='sans_penalite'){ return row.dataset.penalite===value; }
    if(value==='payé' || value==='impayé'){ return row.dataset.paiementStatus===value; }
    return true;
  }
  function stationMatches(row, value){ return !value || (row.dataset.station||'')===value; }
  function swappeurMatches(row, value){ return !value || (row.dataset.swappeur||'').includes((value||'').toLowerCase()); }
  function textMatches(row, q){ if(!q) return true; const hay=(row.dataset.search||''); return hay.includes(q.toLowerCase()); }

  function toggleInputsFor(mode){
    inDate.style.display  = 'none';
    inStart.style.display = 'none';
    inEnd.style.display   = 'none';
    if(mode==='date'){ inDate.style.display='inline-block'; }
    if(mode==='range'){ inStart.style.display='inline-block'; inEnd.style.display='inline-block'; }
  }

  function applyFilters(){
    const mode = selPeriod.value;
    const one  = at0(parseISO(inDate.value));
    const from = at0(parseISO(inStart.value));
    const to   = at0(parseISO(inEnd.value));
    const q    = (searchInput?.value || '').trim().toLowerCase();

    let total=0, payes=0, impayes=0, penT=0, penL=0, penG=0, sumLease=0, sumPen=0;

    // VISIBILITÉ : modes ENREG (enreg only)
    rowsEnreg.forEach(row=>{
      let show = penaltyMatches(row, selPenalty.value)
              && stationMatches(row, selStation.value)
              && swappeurMatches(row, selSwap.value)
              && textMatches(row, q);

      if(mode==='date'){
        // cacher enreg quand "Date spécifique" (mode concernée)
        show = false;
      }else{
        const dEn = parseISO(row.dataset.enregDate||'');
        show = show && dateMatchesEnreg(dEn, mode, one, from, to);
      }
      row.style.display = show ? '' : 'none';
      if(show){
        total++;
        const st=(row.dataset.paiementStatus||'').toLowerCase();
        if(st==='payé' || st==='paye') payes++; else if(st==='impayé' || st==='impaye') impayes++;
        const penKey=row.dataset.penalite;
        const penAmt=parseFloat(row.dataset.penAmount)||0;
        if(penAmt>0){ penT++; if(penKey==='penalite_legere') penL++; if(penKey==='penalite_grave') penG++; }
        sumLease += parseFloat(row.dataset.total||0) || 0;
        sumPen   += penAmt;
      }
    });

    // VISIBILITÉ : mode CONCERNÉE (concern only si mode=date)
    rowsConcern.forEach(row=>{
      let show = penaltyMatches(row, selPenalty.value)
              && stationMatches(row, selStation.value)
              && swappeurMatches(row, selSwap.value)
              && textMatches(row, q);

      if(mode==='date'){
        const dCo = parseISO(row.dataset.concernDate||'');
        show = show && !!one && dCo && (dCo.getTime()===one.getTime());
      }else{
        // cacher concern quand on n'est pas en "date"
        show = false;
      }
      row.style.display = show ? '' : 'none';
      if(show){
        total++;
        const st=(row.dataset.paiementStatus||'').toLowerCase();
        if(st==='payé' || st==='paye') payes++; else if(st==='impayé' || st==='impaye') impayes++;
        const penKey=row.dataset.penalite;
        const penAmt=parseFloat(row.dataset.penAmount)||0;
        if(penAmt>0){ penT++; if(penKey==='penalite_legere') penL++; if(penKey==='penalite_grave') penG++; }
        sumLease += parseFloat(row.dataset.total||0) || 0;
        sumPen   += penAmt;
      }
    });

    // MAJ stats
    if(statTotal)  statTotal.textContent  = total;
    if(statPayes)  statPayes.textContent  = payes;
    if(statImpayes)statImpayes.textContent= impayes;
    if(statPenT)   statPenT.textContent   = penT;
    if(statPenL)   statPenL.textContent   = penL;
    if(statPenG)   statPenG.textContent   = penG;
    if(statSumLea) statSumLea.textContent = Math.round(sumLease).toLocaleString('fr-FR');
    if(statSumPen) statSumPen.textContent = Math.round(sumPen).toLocaleString('fr-FR');
  }

  [selPenalty, selStation, selSwap].forEach(el => el.addEventListener('change', applyFilters));
  selPeriod.addEventListener('change', ()=>{ toggleInputsFor(selPeriod.value); applyFilters(); });

  if(searchInput){
    searchInput.addEventListener('input', applyFilters);
  }

  inDate.addEventListener('input',  applyFilters);
  inStart.addEventListener('input', applyFilters);
  inEnd.addEventListener('input',   applyFilters);

  // Init
  toggleInputsFor(selPeriod.value);
  applyFilters();
});
</script>
@endsection
