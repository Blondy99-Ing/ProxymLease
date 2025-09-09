<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    protected $table = 'agences';

    protected $fillable = [
        'agence_unique_id',
        'nom_agence',
        'nom_proprietaire',
        'ville',
        'quartier',
        'telephone',
        'email',
        'password',
        'description',
        'logo',
        'longitude',
        'latitude',
        'energy',
        'id_entrepot',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
        'energy' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🔗 Relations

    public function usersAgence()
    {
        return $this->hasMany(UserAgence::class, 'id_agence');
    }

    public function entrepot()
    {
        return $this->belongsTo(Entrepot::class, 'id_entrepot');
    }
}
