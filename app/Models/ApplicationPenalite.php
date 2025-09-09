<?php
// app/Models/ApplicationPenalite.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationPenalite extends Model
{
    use HasFactory;

    // ⚠️ Ce modèle correspond à l'APPLICATION d'une pénalité (cas concret)
    // et tu l’as mappé sur la table `payments_penalite`.
    protected $table = 'payments_penalite';

    // Si ta table n’a PAS de colonnes created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'type_penalite',          // ex: legere / grave (ou code interne)
        'montant',
        'date_creation',
        'motif',
        'description',
        'statut',                 // ex: active / payee / annulee
        'date_modification',
        'date_paiement_manque',   // jour du lease manquant
        'raison_annulation',

        'contrat_batterie_id',
        'contrat_chauffeur_id',
        'contrat_partenaire_id',

        'creer_par_id',
        'modifie_par_id',
        'pardonnee_par_id',

        'paiement_id',            // PaiementLease concerné (optionnel selon ton flux)
        'montant_payé',
    ];

    protected $casts = [
        'montant'               => 'decimal:2',
        'montant_payé'          => 'decimal:2',
        'date_creation'         => 'datetime',
        'date_modification'     => 'datetime',
        'date_paiement_manque'  => 'date',
    ];

    /* ================= Relations utiles ================= */

    // Paiement "lease" auquel se rattache la pénalité (si tu la relies à un paiement)
    public function paiement()
    {
        return $this->belongsTo(\App\Models\PaiementLease::class, 'paiement_id');
    }

    public function contratChauffeur()
    {
        return $this->belongsTo(\App\Models\ContratChauffeur::class, 'contrat_chauffeur_id');
    }

    public function contratBatterie()
    {
        return $this->belongsTo(\App\Models\ContratBatterie::class, 'contrat_batterie_id');
    }

    public function contratPartenaire()
    {
        return $this->belongsTo(\App\Models\ContratPartenaire::class, 'contrat_partenaire_id');
    }

    public function creePar()
    {
        return $this->belongsTo(\App\Models\Employe::class, 'creer_par_id')->withDefault();
    }

    public function modifiePar()
    {
        return $this->belongsTo(\App\Models\Employe::class, 'modifie_par_id')->withDefault();
    }

    public function pardonneePar()
    {
        return $this->belongsTo(\App\Models\Employe::class, 'pardonnee_par_id')->withDefault();
    }

    // Paiements d’une pénalité (règlements partiels/total)
    public function reglements()
    {
        return $this->hasMany(\App\Models\PaiementPenalite::class, 'penalite_id');
    }
}
