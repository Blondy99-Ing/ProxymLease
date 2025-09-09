<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PaiementLease;
use App\Models\ContratChauffeur;
use App\Models\UserAgence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Throwable;






class LeaseController extends Controller
{
    public function index(Request $request)
    {
        // --- 1) Résoudre la période demandée
        [$start, $end, $dateMode, $label] = $this->resolvePeriod($request);

        // --- 2) Lister toutes les dates de la période (YYYY-MM-DD)
        $dates = $this->datesInRange($start, $end);

        // --- 3) Construire les “buckets” (un tableau par date) -> [$date => Collection<row>]
        $buckets = [];
        foreach ($dates as $date) {
            $buckets[$date] = $this->buildRowsForDate($date);
        }

        // --- 4) Filtres dynamiques (stations + swappeurs)
        $stations = UserAgence::query()
            ->selectRaw('DISTINCT COALESCE(agences.nom_agence, "Direction") as nom_agence')
            ->leftJoin('agences', 'agences.id', '=', 'users_agences.id_agence')
            ->orderBy('nom_agence')
            ->pluck('nom_agence')
            ->toArray();

        // correction colonne
        if (empty($stations)) {
            $stations = UserAgence::query()
                ->selectRaw('DISTINCT COALESCE(agences.nom_agence, "Direction") as nom_agence')
                ->leftJoin('agences', 'agences.id', '=', 'users_agences.id_agence')
                ->orderBy('nom_agence')
                ->pluck('nom_agence')
                ->toArray();
        }

        $swappers = UserAgence::query()
            ->selectRaw('TRIM(CONCAT(COALESCE(nom,""), " ", COALESCE(prenom,""))) as full_name')
            ->whereNotNull('nom')
            ->whereNotNull('prenom')
            ->orderBy('full_name')
            ->pluck('full_name')
            ->filter(fn($n) => trim($n) !== '')
            ->unique()
            ->values()
            ->toArray();

        // $date gardé pour compat des badges (on prend le start pour l’affichage principal)
        $date = $start;

        return view('leases.index', compact(
            'buckets',   // => tables groupées par date
            'date',      // => pour badge en-tête
            'dateMode',  // => valeur du select période
            'label',     // => libellé période (ex: 02/09/2025 - 05/09/2025)
            'stations',
            'swappers'
        ));
    }

    /* =======================
     *        Helpers
     * ======================= */

    /**
     * Résout la période à partir du request:
     * today|week|month|year|date|range
     * Retourne [startDate, endDate, mode, label]
     */
    private function resolvePeriod(Request $request): array
    {
        $mode = $request->get('date_mode', 'today'); // today|week|month|year|date|range
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
                try {
                    $d = $date ? Carbon::parse($date) : $today;
                } catch (\Throwable $e) {
                    $d = $today;
                }
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

    /**
     * Renvoie un tableau de dates (YYYY-MM-DD) inclusives entre start et end.
     */
    private function datesInRange(string $start, string $end): array
    {
        $dates = [];
        $cur = Carbon::parse($start)->startOfDay();
        $last = Carbon::parse($end)->startOfDay();
        while ($cur->lte($last)) {
            $dates[] = $cur->toDateString();
            $cur->addDay();
        }
        return $dates;
    }

    /**
     * Construit la collection "rows" pour 1 journée (même format que tu affiches en Blade)
     */
    private function buildRowsForDate(string $date)
    {
        // Contrats actifs à cette date
        $contrats = $this->expectedContractsQuery($date)
            ->with([
                'association:id,validated_user_id,moto_valide_id',
                'association.validatedUser:id,user_unique_id,nom,prenom',
                'association.motosValide:id,vin,moto_unique_id',
            ])
            ->orderBy('id')
            ->get();

        // Paiements du jour (par contrat)
        $paymentsByContrat = $this->paymentsForDate($date);

        // Transformer en lignes (objet stdClass similaire à PaiementLease pour la vue)
        return $contrats->map(function (ContratChauffeur $contrat) use ($paymentsByContrat, $date) {
            $payment = $this->pickPaymentForContrat($paymentsByContrat, $contrat->id);
            $row = new \stdClass();
            $row->contratChauffeur = $contrat;

            if ($payment) {
                $row->statut_paiement = 'PAYE';
                $row->montant_moto = $payment->montant_moto;
                $row->montant_batterie = $payment->montant_batterie;
                $row->montant_total = $payment->montant_total;
                $row->date_paiement = $payment->date_paiement;
                $row->heure_paiement = $payment->heure_paiement;
                $row->est_penalite = $payment->est_penalite;
                $row->userAgence   = $payment->userAgence ?? null;
                $row->enregistrePar= $payment->enregistrePar ?? null;
                $row->statut_penalite_calcule = $this->computePenaltyStatus($payment->date_enregistrement, $payment->heure_paiement);
                $row->montant_penalites_inclus = $payment->montant_penalites_inclus ?? 0;
 
                // ✅ AJOUTE CES 3 LIGNES
            $row->date_paiement_concerne = $payment->date_paiement_concerne;
            $row->date_limite_paiement   = $payment->date_limite_paiement;
            // (facultatif) utile si tu veux data_get($p,'paiement.xxx') en Blade
            $row->paiement = $payment;
            } else {
                $row->statut_paiement = 'IMPAYE';
                $row->montant_moto = null;
                $row->montant_batterie = null;
                $row->montant_total = null;
                $row->date_paiement = $date;
                $row->heure_paiement = null;
                $row->est_penalite = false;
                $row->userAgence = null;
                $row->enregistrePar = null;
                $row->statut_penalite_calcule = $this->computePenaltyStatus(Carbon::now(), null);
                $row->montant_penalites_inclus = 0;
                            // ✅ côté "impayé" on n’a pas de dates saisies
                 $row->date_paiement_concerne = null;
                $row->date_limite_paiement   = null;

            }
            return $row;
        });
    }

    /** requête des contrats actifs ce jour-là */
    private function expectedContractsQuery(string $date)
    {
        return ContratChauffeur::query()
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date);
    }

    /** Paiements d’un jour (groupés par contrat) */
    private function paymentsForDate(string $date)
    {
        $payments = PaiementLease::with([
                'userAgence:id,nom,prenom,id_agence',
                'userAgence.agence:id,nom_agence',
                'enregistrePar:id,nom,prenom',
            ])
            ->whereDate('date_paiement', $date)
            ->orderByDesc('date_enregistrement')
            ->get();

        return $payments->groupBy('contrat_chauffeur_id')->map(fn($g) => $g->first());
    }

    private function pickPaymentForContrat($paymentsByContrat, int $contratId): ?PaiementLease
    {
        return $paymentsByContrat->get($contratId);
    }

    /** Renvoie le statut d'une penalite pénalité (avant 12h / 12–14h / après 14h) */
    private function computePenaltyStatus($dateEnregistrement, ?string $heurePaiement): string
    {
        if ($dateEnregistrement) {
            $t = Carbon::parse($dateEnregistrement)->format('H:i');
        } elseif (!empty($heurePaiement)) {
            $t = substr($heurePaiement, 0, 5);
            if (strlen($t) < 5) return 'sans pénalité';
        } else {
            return 'sans pénalité';
        }

        if ($t < '12:00')  return 'sans pénalité';
        if ($t <= '14:00') return 'pénalité légère';
        return 'pénalité grave';
    }









  // paiement du lease
public function pay(Request $request)
{
    // Normaliser les champs issus du formulaire
    $request->merge([
        'methode_paiement'        => $request->input('methode_paiement') ?? $request->input('mode_paiement'),
        'date_paiement_concerne'  => $request->input('date_paiement_concerne') ?? $request->input('date_paiement'),
        'date_limite_paiement'    => $request->input('date_limite_paiement')   ?? $request->input('date_limite'),
        'notes'                   => $request->input('notes') ?? $request->input('note'),
    ]);

    // Validation
    $v = \Validator::make($request->all(), [
        'contrat_id'              => ['required','integer','exists:contrats_contratchauffeur,id'],
        'montant_moto'            => ['nullable','numeric','min:0'],
        'montant_batterie'        => ['nullable','numeric','min:0'],
        'montant_total'           => ['nullable','numeric','min:0'],
        'methode_paiement'        => ['required','in:especes,mobile_money,autre'],
        'reference_transaction'   => ['nullable','string','max:100'],
        'date_paiement_concerne'  => ['required','date'],
        'date_limite_paiement'    => ['required','date','after_or_equal:date_paiement_concerne'],
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

            // 1) Récup contrat
            /** @var \App\Models\ContratChauffeur $contrat */
            $contrat = \App\Models\ContratChauffeur::findOrFail($data['contrat_id']);

            // 2) Montants
            $moto = (float)($data['montant_moto'] ?? 0);
            $bat  = (float)($data['montant_batterie'] ?? 0);
            $tot  = isset($data['montant_total']) ? (float)$data['montant_total'] : ($moto + $bat);
            if (abs(($moto + $bat) - $tot) > 0.0001) $tot = $moto + $bat;

            // 3) Références / dates
            $now   = Carbon::now();
            $heure = $now->format('H:i:s.u');
            $ref   = 'PL-'.$now->format('Ymd-His').'-'.Str::upper(Str::random(5));

            $statutGlobal  = $tot  > 0 ? 'PAYE' : 'IMPAYE';
            $statutMoto    = $moto > 0 ? 'PAYE' : 'IMPAYE';
            $statutBatt    = $bat  > 0 ? 'PAYE' : 'IMPAYE';
            $estPenalite   = $now->format('H:i') > '12:00';

            // 4) ⚠️ ID de l’employé connecté
            //    - Si ton guard par défaut est celui des employés:
            $enregistreParId = Auth::id();
            //    - Sinon, si tu utilises un guard nommé "employe", décommente la ligne suivante :
            // $enregistreParId = Auth::guard('employe')->id();

            // 5) Création du paiement (user_agence_id = null car non géré ici)
            \App\Models\PaiementLease::create([
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

                // ✅ ICI : l’employé qui a enregistré
                'enregistre_par_id'        => $enregistreParId ?: null,

                // ✅ Les swappeurs (users_agences) ne sont pas gérés ici
                'user_agence_id'           => null,

                'notes'                    => $data['notes'] ?? null,
            ]);

            // 6) Avancement simple du contrat
            $contrat->increment('montant_paye', $tot);
            $contrat->update([
                'montant_restant' => max(0, (float)$contrat->montant_total - (float)$contrat->montant_paye),
            ]);

            // 7) Prochaines dates à partir des dates saisies
            $baseConcerned = Carbon::parse($data['date_paiement_concerne'])->startOfDay();
            $baseLimit     = Carbon::parse($data['date_limite_paiement'])->startOfDay();

            $hadGap = $baseLimit->gt($baseConcerned); // limite > concernée ?

            // Prochaine "concernée"
            $nextConcerned = $baseConcerned->copy()->addDay();
            if ($nextConcerned->isSunday()) {
                $nextConcerned->addDay(); // lundi
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







}