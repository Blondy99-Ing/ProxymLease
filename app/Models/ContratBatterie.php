<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratBatterie extends Model
{
    protected $table = 'contrats_contratbatterie';

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
        'montant_caution',
        'duree_caution',
        'montant_engage_batterie',
        'partenaire_id',
        'chauffeur_id',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'montant_restant' => 'decimal:2',
        'montant_engage' => 'decimal:2',
        'montant_caution' => 'decimal:2',
        'montant_engage_batterie' => 'decimal:2',
        'date_signature' => 'date',
        'date_enregistrement' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // 🔗 Relations

    public function partenaire()
    {
        return $this->belongsTo(ContratPartenaire::class, 'partenaire_id');
    }

    public function chauffeur()
    {
        return $this->belongsTo(ContratChauffeur::class, 'chauffeur_id');
    }

    public function paiements()
    {
        return $this->hasMany(PaiementLease::class, 'contrat_batterie_id');
    }
}
