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
    <div id="flash-stack"
        style="position:fixed; right:1rem; top:1rem; z-index: 2000; display:flex; flex-direction:column; gap:.5rem;">
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
                <option value="week" {{ ($dateMode ?? '')==='week'  ? 'selected' : '' }}>Cette Semaine</option>
                <option value="month" {{ ($dateMode ?? '')==='month' ? 'selected' : '' }}>Ce Mois</option>
                <option value="year" {{ ($dateMode ?? '')==='year'  ? 'selected' : '' }}>Cette Année</option>
                <option value="date" {{ ($dateMode ?? '')==='date'  ? 'selected' : '' }}>Date spécifique (concernée)
                </option>
                <option value="range" {{ ($dateMode ?? '')==='range' ? 'selected' : '' }}>Plage de dates (enreg.)
                </option>
            </select>

            <!-- Inputs dynamiques -->
            <input id="input-date" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('date', \Illuminate\Support\Carbon::parse($date ?? now())->toDateString()) }}">
            <input id="input-start" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('start_date') }}">
            <input id="input-end" type="date" class="filter-select" style="display:none; width:auto;"
                value="{{ request('end_date') }}">
        </div>

        <!-- Bouton global pour ouvrir la modale et choisir un contrat -->
        <div style="margin-left:auto">
            <button id="btn-new-pay" type="button" class="export-btn" style="padding:.55rem .9rem;">💳 Nouveau
                paiement</button>
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

                $userId = $u->user_unique_id ?? '—';
                $userName = $u ? trim(($u->nom ?? '').' '.($u->prenom ?? '')) : '—';
                $motoId = $m->moto_unique_id ?? '—';
                $vin = $m->vin ?? '—';

                $montantMoto = is_null($p->montant_moto) ? null : (float)$p->montant_moto;
                $montantBatterie = is_null($p->montant_batterie) ? null : (float)$p->montant_batterie;
                $montantTotal = is_null($p->montant_total) ? null : (float)$p->montant_total;

                $station = optional($p->userAgence)->exists
                ? (optional($p->userAgence->agence)->nom_agence ?? '—')
                : 'Direction';

                $statutUpper = strtoupper((string)($p->statut_paiement ?? 'PAYE'));
                $statutAff = $statutUpper === 'IMPAYE' ? 'impayé' : 'payé';

                $penStatut = trim((string) data_get($p, 'statut_penalite_calcule') ?: 'sans pénalité');
                $penAmount = (float) (data_get($p, 'montant_penalites_inclus') ?? 0);
                $penType = data_get($p, 'statut_penalite_type') ?: 'NONE';
                $penObj = data_get($p, 'penalite') ?? null;

                $penKey =
                Str::of($penStatut)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/','_')->trim('_')->__toString() ?:
                'sans_penalite';

                $dateConcernPay = ($d = data_get($p,'date_paiement_concerne')) ?
                \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
                $dateLimitePay = ($d = data_get($p,'date_limite_paiement')) ? \Carbon\Carbon::parse($d)->format('d/m/Y')
                : '—';

                $enregISO = $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->toDateString() : '';
                $concernISO = $p->date_paiement_concerne ?
                \Carbon\Carbon::parse($p->date_paiement_concerne)->toDateString() : '';

                $dateStr = $enregISO ? \Carbon\Carbon::parse($enregISO)->format('d/m/Y') : '—';
                $heureStr = !empty($p->heure_paiement) ? substr($p->heure_paiement,0,5) : '—';

                $swappeur = optional($p->userAgence)->exists
                ? trim(($p->userAgence->nom ?? '').' '.($p->userAgence->prenom ?? '')).' (Agence)'
                : (optional($p->enregistrePar)->exists
                ? trim(($p->enregistrePar->nom ?? '').' '.($p->enregistrePar->prenom ?? '')).' (Employé)'
                : '—');

                $contratId = optional($p->contratChauffeur)->id;
                $contratMotoDef = (float) optional($p->contratChauffeur)->montant_engage ?? 0;
                $contratBattDef = (float) optional($p->contratChauffeur)->montant_engage_batterie ?? 0;
                $contratDateCon = optional($p->contratChauffeur)->date_paiement_concerne ?? '';
                $contratDateLim = optional($p->contratChauffeur)->date_limite_paiement ?? '';
                $chauffId = $u->id ?? '';
                $hasAnyRow = true;
                @endphp
                <tr class="row-enreg" data-kind="enreg" data-enreg-date="{{ $enregISO }}"
                    data-concern-date="{{ $concernISO }}" data-statut="{{ $statutAff }}"
                    data-paiement-status="{{ strtolower($statutAff) }}" data-penalite="{{ $penKey }}"
                    data-penalite-type="{{ $penType }}" data-pen-id="{{ $penObj->id ?? '' }}"
                    data-total="{{ $montantTotal ?? 0 }}" data-pen-amount="{{ $penAmount }}"
                    data-station="{{ $station }}" data-swappeur="{{ Str::of($swappeur)->lower() }}"
                    data-contrat-id="{{ $contratId }}" data-contrat-moto="{{ $contratMotoDef }}"
                    data-contrat-batterie="{{ $contratBattDef }}" data-contrat-date-concerne="{{ $contratDateCon }}"
                    data-contrat-date-limite="{{ $contratDateLim }}" data-chauffeur-id="{{ $chauffId }}"
                    data-chauffeur-name="{{ $userName }}"
                    data-search="{{ Str::of($userId.' '.$userName.' '.$motoId.' '.$vin.' '.$station.' '.$statutAff.' '.$penStatut.' '.$swappeur)->lower() }}">
                    <td>{{ $userId }}</td>
                    <td>{{ $userName }}</td>
                    <td>{{ $motoId }}</td>
                    <td>{{ $vin }}</td>
                    <td>{{ is_null($montantMoto) ? '—' : number_format($montantMoto,0,',',' ') }} FCFA</td>
                    <td>{{ is_null($montantBatterie) ? '—' : number_format($montantBatterie,0,',',' ') }} FCFA</td>
                    <td class="fw-bold">{{ is_null($montantTotal) ? '—' : number_format($montantTotal,0,',',' ') }} FCFA
                    </td>
                    <td>{{ $dateConcernPay }}</td>
                    <td>{{ $dateLimitePay }}</td>
                    <td>{{ $station }}</td>
                    <td>{{ $statutAff }}</td>
                    <td>
                        @if($penKey !== 'sans_penalite' && $penAmount > 0)
                        <span class="pen-badge"
                            style="display:inline-block;padding:.18rem .45rem;border-radius:.35rem;font-size:.85rem;">{{ $penStatut }}</span>
                        <small style="margin-left:.4rem;color:#666;">{{ number_format($penAmount,0,',',' ') }}
                            FCFA</small>
                        @if($penObj)
                        <button type="button" class="btn-pen-detail" style="margin-left:.45rem;cursor:pointer"
                            data-pen-id="{{ $penObj->id }}" data-pen-montant="{{ $penAmount }}"
                            data-pen-type="{{ e($penType) }}"
                            data-pen-desc="{{ e($penObj->description ?? '') }}">ⓘ</button>
                        @endif
                        @else
                        <span style="color:#6c757d;font-size:.95rem;">sans pénalité</span>
                        @endif
                    </td>
                    <td>{{ $swappeur }}</td>
                    <td>{{ $dateStr }} {{ $heureStr }}</td>
                    <td>
                        <button type="button" class="btn-pay-lease" data-contrat="{{ $contratId }}"
                            data-chauffeur="{{ $userName }}" data-chauffeur-id="{{ $chauffId }}"
                            data-moto="{{ $contratMotoDef }}" data-batterie="{{ $contratBattDef }}"
                            data-date-concerne-contrat="{{ $contratDateCon }}"
                            data-date-limite-contrat="{{ $contratDateLim }}">
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

                $userId = $u->user_unique_id ?? '—';
                $userName = $u ? trim(($u->nom ?? '').' '.($u->prenom ?? '')) : '—';
                $motoId = $m->moto_unique_id ?? '—';
                $vin = $m->vin ?? '—';

                $montantMoto = is_null($p->montant_moto) ? null : (float)$p->montant_moto;
                $montantBatterie = is_null($p->montant_batterie) ? null : (float)$p->montant_batterie;
                $montantTotal = is_null($p->montant_total) ? null : (float)$p->montant_total;

                $station = optional($p->userAgence)->exists
                ? (optional($p->userAgence->agence)->nom_agence ?? '—')
                : 'Direction';

                $statutUpper = strtoupper((string) ($p->statut_paiement ?? ''));
                $statutAff = $statutUpper === 'PAYE' ? 'payé' : ($statutUpper === 'IMPAYE' ? 'impayé' : 'impayé');

                $penStatut = trim((string) data_get($p, 'statut_penalite_calcule') ?: 'sans pénalité');
                $penAmount = (float) (data_get($p, 'montant_penalites_inclus') ?? 0);
                $penType = data_get($p, 'statut_penalite_type') ?: 'NONE';
                $penObj = data_get($p, 'penalite') ?? null;

                $penKey =
                Str::of($penStatut)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/','_')->trim('_')->__toString() ?:
                'sans_penalite';

                $dcRaw = data_get($p, 'date_paiement_concerne');
                $dlRaw = data_get($p, 'date_limite_paiement');

                $dateConcernPay = $dcRaw ? \Carbon\Carbon::parse($dcRaw)->format('d/m/Y') :
                \Carbon\Carbon::parse($theDate)->format('d/m/Y');
                $dateLimitePay = $dlRaw ? \Carbon\Carbon::parse($dlRaw)->format('d/m/Y') : '—';

                $concernISO = $dcRaw ? \Carbon\Carbon::parse($dcRaw)->toDateString() :
                \Carbon\Carbon::parse($theDate)->toDateString();

                $dateStr = !empty($p->date_paiement) ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : '—';
                $heureStr = !empty($p->heure_paiement) ? substr($p->heure_paiement,0,5) : '—';

                $swappeur = optional($p->userAgence)->exists
                ? trim(($p->userAgence->nom ?? '').' '.($p->userAgence->prenom ?? '')).' (Agence)'
                : (optional($p->enregistrePar)->exists
                ? trim(($p->enregistrePar->nom ?? '').' '.($p->enregistrePar->prenom ?? '')).' (Employé)'
                : '—');

                $contratId = optional($p->contratChauffeur)->id;
                $contratMotoDef = (float) optional($p->contratChauffeur)->montant_engage ?? 0;
                $contratBattDef = (float) optional($p->contratChauffeur)->montant_engage_batterie ?? 0;
                $contratDateCon = optional($p->contratChauffeur)->date_paiement_concerne ?? '';
                $contratDateLim = optional($p->contratChauffeur)->date_limite_paiement ?? '';
                $chauffId = $u->id ?? '';
                $hasAnyRow = true;
                @endphp
                <tr class="row-concern" data-kind="concern" data-enreg-date="" data-concern-date="{{ $concernISO }}"
                    data-statut="{{ $statutAff }}" data-paiement-status="{{ strtolower($statutAff) }}"
                    data-penalite="{{ $penKey }}" data-penalite-type="{{ $penType }}"
                    data-pen-id="{{ $penObj->id ?? '' }}" data-total="{{ $montantTotal ?? 0 }}"
                    data-pen-amount="{{ $penAmount }}" data-station="{{ $station }}"
                    data-swappeur="{{ Str::of($swappeur)->lower() }}" data-contrat-id="{{ $contratId }}"
                    data-contrat-moto="{{ $contratMotoDef }}" data-contrat-batterie="{{ $contratBattDef }}"
                    data-contrat-date-concerne="{{ $contratDateCon }}" data-contrat-date-limite="{{ $contratDateLim }}"
                    data-chauffeur-id="{{ $chauffId }}" data-chauffeur-name="{{ $userName }}"
                    data-search="{{ Str::of($userId.' '.$userName.' '.$motoId.' '.$vin.' '.$station.' '.$statutAff.' '.$penStatut.' '.$swappeur)->lower() }}">
                    <td>{{ $userId }}</td>
                    <td>{{ $userName }}</td>
                    <td>{{ $motoId }}</td>
                    <td>{{ $vin }}</td>
                    <td>{{ is_null($montantMoto) ? '—' : number_format($montantMoto,0,',',' ') }} FCFA</td>
                    <td>{{ is_null($montantBatterie) ? '—' : number_format($montantBatterie,0,',',' ') }} FCFA</td>
                    <td class="fw-bold">{{ is_null($montantTotal) ? '—' : number_format($montantTotal,0,',',' ') }} FCFA
                    </td>
                    <td>{{ $dateConcernPay }}</td>
                    <td>{{ $dateLimitePay }}</td>
                    <td>{{ $station }}</td>
                    <td>{{ $statutAff }}</td>
                    <td>
                        @if($penKey !== 'sans_penalite' && $penAmount > 0)
                        <span class="pen-badge"
                            style="display:inline-block;padding:.18rem .45rem;border-radius:.35rem;font-size:.85rem;">{{ $penStatut }}</span>
                        <small style="margin-left:.4rem;color:#666;">{{ number_format($penAmount,0,',',' ') }}
                            FCFA</small>
                        @if($penObj)
                        <button type="button" class="btn-pen-detail" style="margin-left:.45rem;cursor:pointer"
                            data-pen-id="{{ $penObj->id }}" data-pen-montant="{{ $penAmount }}"
                            data-pen-type="{{ e($penType) }}"
                            data-pen-desc="{{ e($penObj->description ?? '') }}">ⓘ</button>
                        @endif
                        @else
                        <span style="color:#6c757d;font-size:.95rem;">sans pénalité</span>
                        @endif
                    </td>
                    <td>{{ $swappeur }}</td>
                    <td>{{ $dateStr }} {{ $heureStr }}</td>
                    <td>
                        <button type="button" class="btn-pay-lease" data-contrat="{{ $contratId }}"
                            data-chauffeur="{{ $userName }}" data-chauffeur-id="{{ $chauffId }}"
                            data-moto="{{ $contratMotoDef }}" data-batterie="{{ $contratBattDef }}"
                            data-date-concerne-contrat="{{ $contratDateCon }}"
                            data-date-limite-contrat="{{ $contratDateLim }}">
                            💳 Payer
                        </button>
                    </td>
                </tr>
                @endforeach
                @endforeach

                @if(!$hasAnyRow)
                <tr>
                    <td colspan="15" class="text-center text-muted py-4">Aucune donnée sur la période.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- MODALE PAIEMENT LEASE -->
<div id="leasePayModal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:1050;">
    <div class="modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    <div class="modal-panel" role="dialog" aria-modal="true"
        style="position:relative;max-width:720px;margin:5vh auto;background:var(--bg-card); color:var(--text-primary);border:1px solid var(--border-color); border-radius:.75rem;box-shadow:var(--shadow);">
        <div
            style="display:flex;align-items:center;justify-content:space-between; padding:1rem 1.25rem;border-bottom:1px solid var(--border-color)">
            <h3 style="margin:0;font-size:1.1rem;">💳 Enregistrer un paiement de lease</h3>
            <button type="button" id="leasePayClose"
                style="background:none;border:1px solid var(--border-color); color:var(--text-primary);padding:.35rem .6rem;border-radius:.35rem;cursor:pointer">✕</button>
        </div>

        <form id="leasePayForm" method="POST" action="{{ route('leases.pay') }}">
            @csrf
            <div style="padding:1rem 1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

                <!-- NOUVEAU: CONTRAT (autocomplete) -->
                <div style="grid-column:1/-1;position:relative">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Contrat</label>
                    <input type="text" id="pay_contrat_label" autocomplete="off"
                        placeholder="Tapez ID contrat / chauffeur / VIN…" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                           border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);" />
                    <input type="hidden" id="pay_contrat_id" name="contrat_id">
                    <div id="contrat_suggest" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:10;
                         background:var(--bg-card);border:1px solid var(--border-color);
                         border-top:none;border-radius:0 0 .35rem .35rem;max-height:260px;overflow:auto"></div>
                </div>

                <!-- CHAUFFEUR (rempli auto, éditable si besoin) -->
                <div style="grid-column:1/-1;position:relative">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Chauffeur</label>
                    <input type="text" id="pay_chauffeur" name="chauffeur_label" autocomplete="off"
                        placeholder="Nom du chauffeur…" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
                              border-radius:.35rem;background:var(--bg-card);color:var(--text-primary);" />
                    <input type="hidden" id="pay_chauffeur_id" name="chauffeur_id">
                    <!-- suggestions chauffeur (optionnel) -->
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
                <div>
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

                <!-- NOTE LIBRE -->
                <div style="grid-column:1/-1">
                    <label style="display:block;font-weight:600;margin-bottom:.35rem;">Note (optionnel)</label>
                    <textarea name="note" rows="2" style="width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);
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

{{-- ======================= --}}
{{-- JS: modal + filtres    --}}
{{-- ======================= --}}
<script>
(function() {
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    // ---- MODAL refs
    const modal = $('#leasePayModal');
    const backdrop = modal?.querySelector('.modal-backdrop');
    const btnClose = $('#leasePayClose');
    const btnCancel = $('#leasePayCancel');
    const btnNew = $('#btn-new-pay');

    const fForm = $('#leasePayForm');
    const fContratLbl = $('#pay_contrat_label');
    const fContratId = $('#pay_contrat_id');
    const sugContrat = $('#contrat_suggest');

    const fChauf = $('#pay_chauffeur');
    const fChaufId = $('#pay_chauffeur_id');
    const sugChauf = $('#chauffeur_suggest');

    const fMoto = $('#pay_moto');
    const fBat = $('#pay_batterie');
    const fTotal = $('#pay_total');
    const fDate = $('#pay_date');
    const fDeadline = $('#pay_deadline');

    // Open/close modal
    const openModal = () => {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    const closeModal = () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        hideContratSuggest();
        hideChaufSuggest();
    };

    btnClose?.addEventListener('click', closeModal);
    btnCancel?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'block') closeModal();
    });

    // Reset form for "Nouveau paiement"
    function resetForm() {
        fForm.reset();
        fContratId.value = '';
        fTotal.value = 0;
    }

    btnNew?.addEventListener('click', () => {
        resetForm();
        openModal();
        setTimeout(() => fContratLbl?.focus(), 40);
    });

    // ---- Récupérer la liste des CONTRATS à partir des <tr>
    const contractsMap = new Map();
    $$('.leases-table tbody tr').forEach(tr => {
        const cid = tr.dataset.contratId;
        if (!cid) return;
        if (contractsMap.has(cid)) return;

        const chauffeurName = tr.dataset.chauffeurName || (tr.children[1]?.textContent || '').trim();
        const chauffeurId = tr.dataset.chauffeurId || '';
        const motoId = (tr.children[2]?.textContent || '').trim();
        const vin = (tr.children[3]?.textContent || '').trim();

        const item = {
            contrat_id: cid,
            label: `#${cid} — ${chauffeurName} — ${motoId} — ${vin}`,
            chauffeur_id: chauffeurId,
            chauffeur_name: chauffeurName,
            montant_moto: Number(tr.dataset.contratMoto || 0),
            montant_batterie: Number(tr.dataset.contratBatterie || 0),
            date_concerne: tr.dataset.contratDateConcerne || '',
            date_limite: tr.dataset.contratDateLimite || ''
        };
        contractsMap.set(cid, item);
    });
    const contractsList = Array.from(contractsMap.values());

    // ---- Suggestions Contrat
    function hideContratSuggest() {
        if (sugContrat) {
            sugContrat.style.display = 'none';
            sugContrat.innerHTML = '';
        }
    }

    function showContratSuggest(items) {
        if (!sugContrat) return;
        if (!items.length) return hideContratSuggest();
        sugContrat.innerHTML = items.map(it =>
            `<div class="sug-contract" data-id="${it.contrat_id}"
                  style="padding:.5rem .75rem;cursor:pointer;border-top:1px solid var(--border-color)">
                  ${it.label}
            </div>`).join('');
        sugContrat.style.display = 'block';
    }

    function fillFromContract(it) {
        fContratId.value = it.contrat_id;
        fContratLbl.value = it.label;

        // auto-fill
        fChauf.value = it.chauffeur_name || '';
        fChaufId.value = it.chauffeur_id || '';
        fMoto.value = Math.max(0, Math.floor(it.montant_moto || 0));
        fBat.value = Math.max(0, Math.floor(it.montant_batterie || 0));
        fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;
        fDate.value = it.date_concerne || '';
        fDeadline.value = it.date_limite || '';
    }

    fContratLbl?.addEventListener('input', () => {
        const q = (fContratLbl.value || '').toLowerCase();
        const items = q ? contractsList.filter(x => x.label.toLowerCase().includes(q)).slice(0, 20) : [];
        showContratSuggest(items);
    });
    fContratLbl?.addEventListener('blur', () => setTimeout(hideContratSuggest, 150));
    // capter avant le blur + guard sur target
    sugContrat?.addEventListener('pointerdown', (ev) => {
        const target = (ev.target instanceof Element) ? ev.target : null;
        const el = target ? target.closest('.sug-contract') : null;
        if (!el) return;
        ev.preventDefault(); // empêche le blur de l'input avant la sélection

        const id = String(el.getAttribute('data-id') || '');
        const it = contractsMap.get(id);
        if (it) fillFromContract(it);

        hideContratSuggest();
        // Optionnel: enlever le focus pour fermer proprement
        // fContratLbl?.blur();
    });


    // ---- Ancien raccourci: bouton "Payer" depuis la ligne
    $$('.btn-pay-lease').forEach(btn => {
        btn.addEventListener('click', () => {
            // remplir depuis dataset bouton
            fContratId.value = btn.getAttribute('data-contrat') || '';
            fContratLbl.value =
                `#${fContratId.value} — ${(btn.getAttribute('data-chauffeur')||'').trim()}`;

            fChauf.value = btn.getAttribute('data-chauffeur') || '';
            fChaufId.value = btn.getAttribute('data-chauffeur-id') || '';

            fMoto.value = Number(btn.getAttribute('data-moto') || 0);
            fBat.value = Number(btn.getAttribute('data-batterie') || 0);
            fTotal.value = Number(fMoto.value || 0) + Number(fBat.value || 0);

            fDate.value = btn.getAttribute('data-date-concerne-contrat') || '';
            fDeadline.value = btn.getAttribute('data-date-limite-contrat') || '';

            openModal();
            setTimeout(() => fMoto.focus(), 40);
        });
    });

    // ---- Suggestions Chauffeur (facultatif, inchangé)
    const driverList = [];
    $$('.leases-table tbody tr').forEach(tr => {
        const chauffeur = (tr.children[1]?.textContent || '').trim();
        if (!chauffeur || chauffeur === '—') return;
        const cid = tr.dataset.contratId || '';
        const moto = Number(tr.dataset.contratMoto || 0);
        const batt = Number(tr.dataset.contratBatterie || 0);
        const id = tr.dataset.chauffeurId || '';
        const key = chauffeur + '|' + cid;
        if (!driverList.find(d => (d.name + '|' + d.contrat_id) === key)) {
            driverList.push({
                id,
                name: chauffeur,
                contrat_id: cid,
                montant_moto: moto,
                montant_batterie: batt
            });
        }
    });

    function hideChaufSuggest() {
        if (sugChauf) {
            sugChauf.style.display = 'none';
            sugChauf.innerHTML = '';
        }
    }

    function showChaufSuggest(items) {
        if (!sugChauf) return;
        if (!items.length) return hideChaufSuggest();
        sugChauf.innerHTML = items.map(it =>
            `<div class="sug-item" data-id="${it.id||''}" data-contrat="${it.contrat_id||''}"
                 data-moto="${it.montant_moto||0}" data-batterie="${it.montant_batterie||0}"
                 style="padding:.5rem .75rem;cursor:pointer;border-top:1px solid var(--border-color)">${it.name}</div>`
        ).join('');
        sugChauf.style.display = 'block';
    }

    fChauf?.addEventListener('input', () => {
        const q = (fChauf.value || '').toLowerCase();
        const items = (!q) ? [] : driverList.filter(d => d.name.toLowerCase().includes(q)).slice(0, 12);
        showChaufSuggest(items);
    });
    fChauf?.addEventListener('blur', () => setTimeout(hideChaufSuggest, 150));
    sugChauf?.addEventListener('pointerdown', (ev) => {
        const target = (ev.target instanceof Element) ? ev.target : null;
        const it = target ? target.closest('.sug-item') : null;
        if (!it) return;
        ev.preventDefault(); // évite le blur qui annule le clic

        fChauf.value = (it.textContent || '').trim();
        fChaufId.value = it.getAttribute('data-id') || '';
        fContratId.value = it.getAttribute('data-contrat') || fContratId.value;

        fMoto.value = Math.max(0, Math.floor(Number(it.getAttribute('data-moto') || 0)));
        fBat.value = Math.max(0, Math.floor(Number(it.getAttribute('data-batterie') || 0)));
        fTotal.value = (Number(fMoto.value || 0) + Number(fBat.value || 0)) || 0;

        hideChaufSuggest();
        // fChauf?.blur(); // optionnel
    });


    // Totaux auto
    function recalcTotal() {
        const a = Number(fMoto.value || 0),
            b = Number(fBat.value || 0);
        fTotal.value = Math.max(0, Math.floor(a + b));
    }
    fMoto.addEventListener('input', recalcTotal);
    fBat.addEventListener('input', recalcTotal);

    // Validation
    fForm.addEventListener('submit', (e) => {
        if (!fContratId.value) {
            e.preventDefault();
            alert('Veuillez choisir un contrat.');
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

<script>
// === Filtres (un seul bloc) ===
document.addEventListener('DOMContentLoaded', () => {
    const selPenalty = document.getElementById('filter-statut-penalite');
    const selStation = document.getElementById('filter-station');
    const selSwap = document.getElementById('filter-swappeur');

    const selPeriod = document.getElementById('filter-periode'); // today|week|month|year|date|range
    const inDate = document.getElementById('input-date');
    const inStart = document.getElementById('input-start');
    const inEnd = document.getElementById('input-end');

    const rowsEnreg = Array.from(document.querySelectorAll('tr.row-enreg'));
    const rowsConcern = Array.from(document.querySelectorAll('tr.row-concern'));

    // Stats
    const statTotal = document.getElementById('stat-count-total');
    const statPayes = document.getElementById('stat-count-payes');
    const statImpayes = document.getElementById('stat-count-impayes');
    const statPenT = document.getElementById('stat-count-penalites');
    const statPenL = document.getElementById('stat-count-pen-leg');
    const statPenG = document.getElementById('stat-count-pen-gra');
    const statSumLea = document.getElementById('stat-sum-leases');
    const statSumPen = document.getElementById('stat-sum-penalites');

    // Search
    const searchInput = document.getElementById('global-search');

    const at0 = (d) => d ? new Date(d.getFullYear(), d.getMonth(), d.getDate()) : null;

    function parseISO(iso) {
        if (!iso) return null;
        const p = String(iso).split('-');
        if (p.length !== 3) return null;
        const d = new Date(+p[0], +p[1] - 1, +p[2]);
        return isNaN(d) ? null : at0(d);
    }

    function weekBounds(today) {
        const d = new Date(today);
        const weekday = d.getDay();
        const diff = (weekday === 0 ? -6 : 1 - weekday);
        const first = new Date(d);
        first.setDate(d.getDate() + diff);
        const last = new Date(first);
        last.setDate(first.getDate() + 6);
        return [at0(first), at0(last)];
    }

    function dateMatchesEnreg(d, mode, one, from, to) {
        if (!d) return false;
        const today = at0(new Date());
        const y = today.getFullYear();
        const m = today.getMonth();
        const [wStart, wEnd] = weekBounds(today);
        switch (mode) {
            case 'today':
                return d.getTime() === today.getTime();
            case 'week':
                return d >= wStart && d <= wEnd;
            case 'month':
                return d.getMonth() === m && d.getFullYear() === y;
            case 'year':
                return d.getFullYear() === y;
            case 'range':
                if (from && d < from) return false;
                if (to && d > to) return false;
                return true;
            default:
                return true;
        }
    }

    function penaltyMatches(row, value) {
        if (!value) return true;
        if (value === 'penalite_all') {
            return (parseFloat(row.dataset.penAmount) || 0) > 0;
        }
        if (value === 'penalite_legere' || value === 'penalite_grave' || value === 'sans_penalite') {
            return row.dataset.penalite === value;
        }
        if (value === 'payé' || value === 'impayé') {
            return row.dataset.paiementStatus === value;
        }
        return true;
    }

    function stationMatches(row, value) {
        return !value || (row.dataset.station || '') === value;
    }

    function swappeurMatches(row, value) {
        return !value || (row.dataset.swappeur || '').includes((value || '').toLowerCase());
    }

    function textMatches(row, q) {
        if (!q) return true;
        const hay = (row.dataset.search || '');
        return hay.includes(q.toLowerCase());
    }

    function toggleInputsFor(mode) {
        inDate.style.display = 'none';
        inStart.style.display = 'none';
        inEnd.style.display = 'none';
        if (mode === 'date') {
            inDate.style.display = 'inline-block';
        }
        if (mode === 'range') {
            inStart.style.display = 'inline-block';
            inEnd.style.display = 'inline-block';
        }
    }

    function applyFilters() {
        const mode = selPeriod.value;
        const one = at0(parseISO(inDate.value));
        const from = at0(parseISO(inStart.value));
        const to = at0(parseISO(inEnd.value));
        const q = (searchInput?.value || '').trim().toLowerCase();

        let total = 0,
            payes = 0,
            impayes = 0,
            penT = 0,
            penL = 0,
            penG = 0,
            sumLease = 0,
            sumPen = 0;

        // ENREG visible sauf en mode "date"
        rowsEnreg.forEach(row => {
            let show = penaltyMatches(row, selPenalty.value) &&
                stationMatches(row, selStation.value) &&
                swappeurMatches(row, selSwap.value) &&
                textMatches(row, q);

            if (mode === 'date') {
                show = false;
            } else {
                const dEn = parseISO(row.dataset.enregDate || '');
                show = show && dateMatchesEnreg(dEn, mode, one, from, to);
            }

            row.style.display = show ? '' : 'none';
            if (show) {
                total++;
                const st = (row.dataset.paiementStatus || '').toLowerCase();
                if (st === 'payé' || st === 'paye') payes++;
                else if (st === 'impayé' || st === 'impaye') impayes++;
                const penKey = row.dataset.penalite;
                const penAmt = parseFloat(row.dataset.penAmount) || 0;
                if (penAmt > 0) {
                    penT++;
                    if (penKey === 'penalite_legere') penL++;
                    if (penKey === 'penalite_grave') penG++;
                }
                sumLease += parseFloat(row.dataset.total || 0) || 0;
                sumPen += penAmt;
            }
        });

        // CONCERN visible seulement en mode "date"
        rowsConcern.forEach(row => {
            let show = penaltyMatches(row, selPenalty.value) &&
                stationMatches(row, selStation.value) &&
                swappeurMatches(row, selSwap.value) &&
                textMatches(row, q);

            if (mode === 'date') {
                const dCo = parseISO(row.dataset.concernDate || '');
                show = show && !!one && dCo && (dCo.getTime() === one.getTime());
            } else {
                show = false;
            }

            row.style.display = show ? '' : 'none';
            if (show) {
                total++;
                const st = (row.dataset.paiementStatus || '').toLowerCase();
                if (st === 'payé' || st === 'paye') payes++;
                else if (st === 'impayé' || st === 'impaye') impayes++;
                const penKey = row.dataset.penalite;
                const penAmt = parseFloat(row.dataset.penAmount) || 0;
                if (penAmt > 0) {
                    penT++;
                    if (penKey === 'penalite_legere') penL++;
                    if (penKey === 'penalite_grave') penG++;
                }
                sumLease += parseFloat(row.dataset.total || 0) || 0;
                sumPen += penAmt;
            }
        });

        // MAJ stats
        if (statTotal) statTotal.textContent = total;
        if (statPayes) statPayes.textContent = payes;
        if (statImpayes) statImpayes.textContent = impayes;
        if (statPenT) statPenT.textContent = penT;
        if (statPenL) statPenL.textContent = penL;
        if (statPenG) statPenG.textContent = penG;
        if (statSumLea) statSumLea.textContent = Math.round(sumLease).toLocaleString('fr-FR');
        if (statSumPen) statSumPen.textContent = Math.round(sumPen).toLocaleString('fr-FR');
    }

    [selPenalty, selStation, selSwap].forEach(el => el.addEventListener('change', applyFilters));
    selPeriod.addEventListener('change', () => {
        toggleInputsFor(selPeriod.value);
        applyFilters();
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    inDate.addEventListener('input', applyFilters);
    inStart.addEventListener('input', applyFilters);
    inEnd.addEventListener('input', applyFilters);

    // Init
    toggleInputsFor(selPeriod.value);
    applyFilters();
});
</script>

<!-- XLSX (SheetJS) pour un vrai .xlsx -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- jsPDF + AutoTable pour PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
// ======== EXPORT FRONT (CSV / XLSX / PDF) ========
document.addEventListener('DOMContentLoaded', () => {
  const btnXLSX = document.querySelector('.export-excel');
  const btnCSV  = document.querySelector('.export-csv');
  const btnPDF  = document.querySelector('.export-pdf');

  const table   = document.querySelector('.leases-table');
  const badge   = document.getElementById('date-badge');
  const modeSel = document.getElementById('filter-periode');

  const HEADERS = [
    'ID Utilisateur','Nom Utilisateur','ID Moto','VIN Moto',
    'Montant Moto','Montant Batterie','Montant Total',
    'Date concernée','Date limite','Station','Statut',
    'Statut pénalité','Montant pénalité','Swappeur',
    'Date enregistrement','Heure enreg.','ID Contrat'
  ];

  const PEN_LABEL = (key) => {
    if (key === 'penalite_legere') return 'pénalité légère';
    if (key === 'penalite_grave')  return 'pénalité grave';
    if (key === 'sans_penalite')   return 'sans pénalité';
    return key || '—';
  };

  const cleanMoney = (txt) => {
    // enlève espaces, NBSP, 'FCFA' et non-chiffres (garde le signe)
    if (!txt) return '';
    return (''+txt)
      .replace(/\u00A0/g,' ')
      .replace(/FCFA/gi,'')
      .replace(/[^\d\-., ]+/g,'')
      .replace(/\s+/g,'')
      .replace(/,/g,'.'); // au cas où
  };

  const splitDateHeure = (txt) => {
    const s = (txt||'').trim();
    if (!s) return {d:'', h:''};
    const parts = s.split(/\s+/);
    if (parts.length >= 2) return {d: parts[0], h: parts[1]};
    return {d: s, h: ''};
  };

  function collectVisibleRows() {
    if (!table) return [];
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const visible = rows.filter(tr => tr.style.display !== 'none');

    return visible.map(tr => {
      const tds = tr.querySelectorAll('td');

      // Colonnes texte du tableau
      const idUser   = (tds[0]?.textContent || '').trim();
      const nomUser  = (tds[1]?.textContent || '').trim();
      const motoId   = (tds[2]?.textContent || '').trim();
      const vin      = (tds[3]?.textContent || '').trim();

      // Montants à nettoyer
      const mMotoTxt = (tds[4]?.textContent || '').trim();
      const mBattTxt = (tds[5]?.textContent || '').trim();
      const mTotTxt  = (tds[6]?.textContent || '').trim();

      const mMoto = cleanMoney(mMotoTxt);
      const mBatt = cleanMoney(mBattTxt);
      const mTot  = cleanMoney(mTotTxt);

      // Dates concernée / limite -> on prend ce qui est visible
      const dateConcern = (tds[7]?.textContent || '').trim();
      const dateLimite  = (tds[8]?.textContent || '').trim();

      const station = (tds[9]?.textContent || '').trim();
      const statut  = (tds[10]?.textContent || '').trim();

      // Pénalité depuis les data-* pour être propre
      const penKey  = tr.dataset.penalite || '';
      const penLbl  = PEN_LABEL(penKey);
      const penAmt  = (tr.dataset.penAmount ? String(Math.round(parseFloat(tr.dataset.penAmount))) : '') || '';

      // Swappeur tel qu’affiché (maj/min conservées)
      const swappeur = (tds[12]?.textContent || '').trim();

      // Date/Heure enreg: colonne 13, sinon dataset (enregDate)
      const dtHeure = splitDateHeure((tds[13]?.textContent || '').trim());
      let dateEnreg = dtHeure.d, heureEnreg = dtHeure.h;
      if (!dateEnreg) {
        const enISO = tr.dataset.enregDate || '';
        if (enISO) {
          // en ISO yyyy-mm-dd => laisse tel quel, ou convertis si tu veux
          dateEnreg = enISO;
        }
      }

      const contratId = tr.dataset.contratId || '';

      return [
        idUser, nomUser, motoId, vin,
        mMoto, mBatt, mTot,
        dateConcern, dateLimite, station, statut,
        penLbl, penAmt, swappeur,
        dateEnreg, heureEnreg, contratId
      ];
    });
  }

  function fileBaseName() {
    const mode = (modeSel?.value || 'today');
    const label = (badge?.textContent || '').trim().replace(/[^\d\-\/– ]+/g,'');
    const safe  = label.replace(/[\/– ]+/g,'-');
    return `leases_${mode}_${safe || 'export'}`;
  }

  // ---- CSV
  function exportCSV() {
    const rows = collectVisibleRows();
    if (!rows.length) { alert('Aucune ligne visible à exporter.'); return; }

    const sep = ';';
    const escape = (s) => {
      const v = (s == null ? '' : String(s));
      if (/[;"\n\r]/.test(v)) return `"${v.replace(/"/g,'""')}"`;
      return v;
    };

    const lines = [];
    lines.push(HEADERS.map(escape).join(sep));
    rows.forEach(r => lines.push(r.map(escape).join(sep)));

    const csv = '\uFEFF' + lines.join('\r\n'); // BOM UTF-8
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = fileBaseName() + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  // ---- XLSX (via SheetJS, sinon fallback .xls HTML)
  function exportXLSX() {
    const rows = collectVisibleRows();
    if (!rows.length) { alert('Aucune ligne visible à exporter.'); return; }

    if (window.XLSX && XLSX.utils && XLSX.writeFile) {
      const data = [HEADERS, ...rows];
      const ws = XLSX.utils.aoa_to_sheet(data);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Leases');
      XLSX.writeFile(wb, fileBaseName() + '.xlsx');
      return;
    }

    // Fallback: .xls basé HTML (ouvert par Excel)
    let html = '<table><thead><tr>';
    HEADERS.forEach(h => html += `<th>${h}</th>`);
    html += '</tr></thead><tbody>';
    rows.forEach(r => {
      html += '<tr>' + r.map(c => `<td>${c ?? ''}</td>`).join('') + '</tr>';
    });
    html += '</tbody></table>';

    const blob = new Blob(['\uFEFF', html], {type: 'application/vnd.ms-excel;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = fileBaseName() + '.xls';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  // ---- PDF (jsPDF + autoTable)
  function exportPDF() {
    const rows = collectVisibleRows();
    if (!rows.length) { alert('Aucune ligne visible à exporter.'); return; }

    const hasJsPDF = window.jspdf && window.jspdf.jsPDF && (typeof window.jspdf.jsPDF === 'function');
    const hasAuto  = !!(window.jsPDFInvoiceTemplate || (window.jspdf && window.jspdf.jsPDF && window.jspdf.jsPDF.API && window.jspdf.jsPDF.API.autoTable));

    if (!hasJsPDF || !hasAuto) {
      alert("Librairies PDF manquantes. Assure-toi d'avoir inclus jsPDF et jspdf-autotable (voir CDN).");
      return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });

    doc.setFontSize(12);
    const title = 'Leases — ' + (badge?.textContent?.trim() || '');
    doc.text(title, 40, 40);

    const body = rows.map(r => r.map(c => (c==null?'':String(c))));
    doc.autoTable({
      head: [HEADERS],
      body,
      startY: 60,
      styles: { fontSize: 8, cellPadding: 4, overflow: 'linebreak' },
      headStyles: { fillColor: [240,240,240] },
      theme: 'grid',
      tableWidth: 'auto'
    });

    doc.save(fileBaseName() + '.pdf');
  }

  btnCSV?.addEventListener('click',  exportCSV);
  btnXLSX?.addEventListener('click', exportXLSX);
  btnPDF?.addEventListener('click',  exportPDF);
});
</script>



@endsection