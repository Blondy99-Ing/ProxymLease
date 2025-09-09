<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Employe extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'employes';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'phone',
        'password',
        'is_staff',
        'is_active',
        'last_login',
        'is_superuser',
    ];

       protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'is_staff' => 'boolean',
        'is_active' => 'boolean',
        'is_superuser' => 'boolean',
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


// app/Models/Employe.php
public function paiements()
{
    // Tous les paiements enregistrés par cet employé
    return $this->hasMany(\App\Models\PaiementLease::class, 'enregistre_par_id');
}


}
