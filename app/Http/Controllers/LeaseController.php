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
