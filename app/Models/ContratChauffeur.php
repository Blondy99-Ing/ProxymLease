<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContratChauffeur extends Model
{
   

    protected $table = 'contrats_contratchauffeur'; // correspond à ta table MySQL

    protected $fillable = [
        'reference',
        'montant_total',
        'montant_paye',
        'montant_restant',
        'frequence_paiement',
        'montant_par_paiement',
        'date_signature',
        'date_enregistrement',
        'date_debut',
        'duree_semaines',
        'duree_jours',
        'date_fin',
        'statut',
        'montant_engage',
        'contrat_physique',
        'montant_caution_batterie',
        'duree_caution_batterie',
        'montant_engage_batterie',
        'jours_conges_total',
        'jours_conges_utilises',
        'jours_conges_restants',
        'association_id',
        'garant_id',
        'date_paiement_concerne',
        'date_limite_paiement'
    ];

    protected $dates = [
        'date_signature',
        'date_enregistrement',
        'date_debut',
        'date_fin',
        'deleted_at'
    ];

    // Exemple de relation avec Association
  // app/Models/ContratChauffeur.php
public function association()
{
    return $this->belongsTo(\App\Models\AssociationUserMoto::class, 'association_id')->withDefault();
}


    // Exemple de relation avec Garant (User, Personne, etc. ?)
    // app/Models/ContratChauffeur.php
public function garant()
{
    return $this->belongsTo(\App\Models\Garant::class, 'garant_id');
}

}
