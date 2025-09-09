<?php

// app/Models/PaiementLease.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementLease extends Model
{
    protected $table = 'payments_paiement';
    public $timestamps = false;

    protected $fillable = [
        'reference','montant_moto','montant_batterie','montant_total',
        'date_paiement','date_enregistrement','methode_paiement',
        'reference_transaction','type_contrat','statut_paiement',
        'statut_paiement_batterie','statut_paiement_moto','est_penalite',
        'inclut_penalites','montant_penalites_inclus','heure_paiement','notes',
        'contrat_batterie_id','contrat_chauffeur_id','contrat_partenaire_id',
        'enregistre_par_id','user_agence_id','date_paiement_concerne',
    'date_limite_paiement',
    ];

    protected $casts = [
        'est_penalite' => 'boolean',
        'inclut_penalites' => 'boolean',
        'montant_moto' => 'decimal:2',
        'montant_batterie' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'montant_penalites_inclus' => 'decimal:2',
        'date_paiement' => 'date',
        'date_enregistrement' => 'datetime',
        // IMPORTANT : TIME(6) -> string, pas datetime
        'heure_paiement' => 'string',
    ];

    // Relations
    public function contratChauffeur()
    {
        return $this->belongsTo(ContratChauffeur::class, 'contrat_chauffeur_id')->withDefault();
    }

    public function contratBatterie()
    {
        return $this->belongsTo(ContratBatterie::class, 'contrat_batterie_id')->withDefault();
    }

    public function contratPartenaire()
    {
        return $this->belongsTo(ContratPartenaire::class, 'contrat_partenaire_id')->withDefault();
    }

    public function userAgence()
    {
        // <-- pointer vers UsersAgence (pluriel)
        return $this->belongsTo(\App\Models\UserAgence::class, 'user_agence_id')->withDefault();
    }

public function enregistrePar()
{
    return $this->belongsTo(\App\Models\Employe::class, 'enregistre_par_id')
                ->withDefault()
                ->withTrashed();
}


}
