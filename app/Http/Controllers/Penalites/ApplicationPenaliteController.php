<?php

namespace App\Http\Controllers\Penalites;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

use App\Models\ContratChauffeur;
use App\Models\PaiementLease;
use App\Models\ApplicationPenalite; // table: payments_penalite
use App\Models\AssociationUserMoto; // 👈 NEW
use Throwable;

class ApplicationPenaliteController extends Controller
{
    public function appliquerPourAujourdhui(Request $request)
    {
        $now   = Carbon::now();
        $today = $now->toDateString(); // YYYY-MM-DD

        $report = [
            'date_execution' => $now->toDateTimeString(),
            'today'          => $today,
            'counters'       => [
                'checked'         => 0,
                'applied'         => 0,
                'upgraded'        => 0,
                'skipped_paid'    => 0,
                'skipped_noon'    => 0,
                'skipped_exists'  => 0,
                'skipped_nodate'  => 0,
                'errors'          => 0,
            ],
            'details'        => [],
        ];

        Log::info('[PENALITES] Démarrage application pénalités', [
            'today' => $today,
            'time'  => $now->format('H:i:s'),
        ]);

        ContratChauffeur::query()
            ->whereDate('date_limite_paiement', $today)
            ->whereNotNull('date_paiement_concerne')
            ->orderBy('id')
            ->chunkById(200, function ($contrats) use ($now, $today, &$report) {

                foreach ($contrats as $contrat) {
                    $report['counters']['checked']++;

                    $context = [
                        'contrat_id'            => $contrat->id,
                        'date_concerne'         => optional($contrat->date_paiement_concerne)->toDateString(),
                        'date_limite'           => optional($contrat->date_limite_paiement)->toDateString(),
                        'execution_time'        => $now->toDateTimeString(),
                    ];

                    // date concernée manquante -> on ignore
                    if (empty($contrat->date_paiement_concerne)) {
                        $report['counters']['skipped_nodate']++;
                        $report['details'][] = array_merge($context, [
                            'status' => 'SKIPPED',
                            'reason' => 'Contrat sans date_paiement_concerne',
                        ]);
                        Log::warning('[PENALITES] Contrat ignoré (pas de date concernée).', $context);
                        continue;
                    }

                    // Avant midi -> pas de pénalité
                    [$montant, $type] = $this->calculPenaliteParHeure($now);
                    if ($montant === 0) {
                        $report['counters']['skipped_noon']++;
                        $report['details'][] = array_merge($context, [
                            'status'  => 'SKIPPED',
                            'reason'  => 'Avant 12:00, aucune pénalité',
                            'palier'  => 'AUCUNE',
                        ]);
                        Log::info('[PENALITES] Aucun traitement (avant midi).', $context);
                        continue;
                    }

                    try {
                        DB::transaction(function () use ($contrat, $now, $montant, $type, &$report, $context) {

                            $dateConcerne = Carbon::parse($contrat->date_paiement_concerne)->toDateString();

                            // 1) Paiement déjà fait pour la date concernée ?
                            $paiementExiste = PaiementLease::query()
                                ->where('contrat_chauffeur_id', $contrat->id)
                                ->whereDate('date_paiement_concerne', $dateConcerne)
                                ->exists();

                            if ($paiementExiste) {
                                $report['counters']['skipped_paid']++;
                                $report['details'][] = array_merge($context, [
                                    'status'  => 'SKIPPED',
                                    'reason'  => 'Paiement trouvé pour la date concernée',
                                    'palier'  => $type,
                                ]);
                                Log::info('[PENALITES] Skip (déjà payé).', array_merge($context, [
                                    'date_paiement_concerne' => $dateConcerne,
                                ]));
                                return;
                            }

                            // 2) Idempotence & upgrade
                            $penalite = ApplicationPenalite::query()
                                ->where('contrat_chauffeur_id', $contrat->id)
                                ->whereDate('date_paiement_manque', $dateConcerne)
                                ->lockForUpdate()
                                ->first();

                            if (!$penalite) {
                                // Créer
                                ApplicationPenalite::create([
                                    'contrat_chauffeur_id' => $contrat->id,
                                    'date_paiement_manque' => $dateConcerne,
                                    'type_penalite'        => $type,     // 'RETARD_LEGER' / 'RETARD_GRAVE'
                                    'montant'              => $montant,  // 2000 / 5000
                                    'statut'               => 'DUE',
                                    'date_creation'        => $now,
                                    'date_modification'    => $now,
                                    'motif'                => 'Retard paiement lease',
                                    'description'          => 'Pénalité auto selon heure de dépassement',
                                ]);

                                // 👇 NEW: bloquer le swap de l’association liée au contrat
                                if (!empty($contrat->association_id)) {
                                    AssociationUserMoto::whereKey($contrat->association_id)
                                        ->update(['swap_bloque' => 1]); // bool/int OK
                                }

                                $report['counters']['applied']++;
                                $report['details'][] = array_merge($context, [
                                    'status'  => 'APPLIED',
                                    'reason'  => 'Pénalité créée',
                                    'palier'  => $type,
                                    'amount'  => $montant,
                                    'swap_bloque' => true, // 👈 NEW (info rapport)
                                ]);
                                Log::info('[PENALITES] Pénalité créée + swap bloqué.', array_merge($context, [
                                    'date_manquee' => $dateConcerne,
                                    'type'         => $type,
                                    'montant'      => $montant,
                                    'assoc_id'     => $contrat->association_id,
                                ]));
                            } else {
                                // Mettre à niveau si nouveau palier supérieur
                                if ($montant > (int)$penalite->montant) {
                                    $old = (int)$penalite->montant;
                                    $penalite->update([
                                        'montant'           => $montant,
                                        'type_penalite'     => $type,
                                        'date_modification' => $now,
                                    ]);

                                    // 👇 NEW: bloquer aussi lors d’un upgrade
                                    if (!empty($contrat->association_id)) {
                                        AssociationUserMoto::whereKey($contrat->association_id)
                                            ->update(['swap_bloque' => 1]);
                                    }

                                    $report['counters']['upgraded']++;
                                    $report['details'][] = array_merge($context, [
                                        'status'   => 'UPGRADED',
                                        'reason'   => "Palier augmenté ($old → $montant)",
                                        'palier'   => $type,
                                        'amount'   => $montant,
                                        'swap_bloque' => true, // 👈 NEW (info rapport)
                                    ]);
                                    Log::info('[PENALITES] Pénalité mise à niveau + swap bloqué.', array_merge($context, [
                                        'date_manquee' => $dateConcerne,
                                        'old_montant'  => $old,
                                        'new_montant'  => $montant,
                                        'type'         => $type,
                                        'assoc_id'     => $contrat->association_id,
                                    ]));
                                } else {
                                    $report['counters']['skipped_exists']++;
                                    $report['details'][] = array_merge($context, [
                                        'status'  => 'SKIPPED',
                                        'reason'  => 'Déjà au bon palier, pas de cumul',
                                        'palier'  => $type,
                                        'amount'  => (int)$penalite->montant,
                                    ]);
                                    Log::info('[PENALITES] Skip (déjà au bon palier).', array_merge($context, [
                                        'date_manquee' => $dateConcerne,
                                        'montant'      => (int)$penalite->montant,
                                    ]));
                                }
                            }
                        });
                    } catch (Throwable $e) {
                        $report['counters']['errors']++;
                        $report['details'][] = array_merge($context, [
                            'status' => 'ERROR',
                            'reason' => 'Exception: '.$e->getMessage(),
                        ]);
                        Log::error('[PENALITES] Erreur lors du traitement d’un contrat.', array_merge($context, [
                            'exception' => $e->getMessage(),
                        ]));
                    }
                }
            });

        Log::info('[PENALITES] Fin application pénalités', [
            'summary' => $report['counters'],
        ]);

        return response()->json($report);
    }

   // private function calculPenaliteParHeure(Carbon $now): array
   // {
   //     $hhmm = $now->format('H:i');
   //     if ($hhmm < '12:00') {
   //         return [0, 'AUCUNE'];
   //     }
   //     if ($hhmm <= '14:00') {
   //         return [2000, 'RETARD_LEGER'];
   //     }
   //     return [5000, 'RETARD_GRAVE'];
   // }


private function calculPenaliteParHeure(Carbon $now): array
{
    // ⚠️ TEST UNIQUEMENT
    // Toujours appliquer RETARD_LEGER peu importe l'heure
    return [2000, 'RETARD_LEGER'];

    // ou forcer le palier grave
    // return [5000, 'RETARD_GRAVE'];
}


}
