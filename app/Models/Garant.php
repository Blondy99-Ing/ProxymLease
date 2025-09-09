<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garant extends Model
{
    protected $table = 'contrats_garant';

    protected $fillable = [
        'nom',
        'prenom',
        'numero_cni',
        'adresse',
        'occupation',
        'telephone',
        'cni_document',
        'justificatif_domicile',
        'justificatif_activite',
        'contrat_physique',
    ];

    // Un garant peut être lié à plusieurs contrats chauffeurs
    public function contratsChauffeurs()
    {
        return $this->hasMany(ContratChauffeur::class, 'garant_id');
    }
}
