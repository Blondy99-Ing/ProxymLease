<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments_paiementpenalite', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('reference', 60)->unique();                 // ex: PPL-20250905-120001-AB12C
            $table->unsignedBigInteger('penalite_id')->index();        // -> payments_penalite.id
            $table->decimal('montant', 12, 2)->default(0);

            $table->date('date_paiement')->nullable()->index();
            $table->dateTime('date_enregistrement')->nullable()->index();

            $table->string('methode_paiement', 50)->nullable();        // especes|mobile_money|autre
            $table->unsignedBigInteger('enregistre_par_id')->nullable()->index();

            // Pas de timestamps() ici (conforme à ton modèle)
        });

        // 👉 FKs optionnelles
        // Schema::table('payments_paiementpenalite', function (Blueprint $table) {
        //     $table->foreign('penalite_id')->references('id')->on('payments_penalite')->cascadeOnDelete();
        //     $table->foreign('enregistre_par_id')->references('id')->on('employes')->nullOnDelete();
        // });
    }

    public function down(): void
    {
        // Si des FKs ont été ajoutées, pense à les drop() ici d’abord
        Schema::dropIfExists('payments_paiementpenalite');
    }
};
