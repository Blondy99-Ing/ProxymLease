<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments_penalite', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Nature de la pénalité
            $table->string('type_penalite', 50)->index();   // ex: legere | grave (ou code)
            $table->decimal('montant', 12, 2)->default(0);

            // Périmètre métier
            $table->dateTime('date_creation')->nullable();
            $table->string('motif', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('statut', 30)->default('active')->index(); // active|payee|annulee|pardonnee...
            $table->dateTime('date_modification')->nullable();

            // Jour du lease manqué (si applicable)
            $table->date('date_paiement_manque')->nullable()->index();

            // Annulation / pardon
            $table->string('raison_annulation', 200)->nullable();

            // Liens métier
            $table->unsignedBigInteger('contrat_batterie_id')->nullable()->index();
            $table->unsignedBigInteger('contrat_chauffeur_id')->nullable()->index();
            $table->unsignedBigInteger('contrat_partenaire_id')->nullable()->index();

            $table->unsignedBigInteger('creer_par_id')->nullable()->index();
            $table->unsignedBigInteger('modifie_par_id')->nullable()->index();
            $table->unsignedBigInteger('pardonnee_par_id')->nullable()->index();

            // Paiement "lease" auquel cette pénalité se rattache (optionnel)
            $table->unsignedBigInteger('paiement_id')->nullable()->index(); // -> payments_paiement.id

            // Suivi de règlement
            $table->decimal('montant_payé', 12, 2)->default(0);

            // Pas de timestamps() ici car le modèle n'utilise pas $timestamps
        });

        // 👉 Si tu veux ajouter des clés étrangères, dé-commente
        // Schema::table('payments_penalite', function (Blueprint $table) {
        //     $table->foreign('contrat_chauffeur_id')->references('id')->on('contrats_contratchauffeur')->nullOnDelete();
        //     $table->foreign('contrat_batterie_id')->references('id')->on('contrats_contratbatterie')->nullOnDelete();
        //     $table->foreign('contrat_partenaire_id')->references('id')->on('contrats_contratpartenaire')->nullOnDelete();
        //     $table->foreign('paiement_id')->references('id')->on('payments_paiement')->nullOnDelete();
        //     $table->foreign('creer_par_id')->references('id')->on('employes')->nullOnDelete();
        //     $table->foreign('modifie_par_id')->references('id')->on('employes')->nullOnDelete();
        //     $table->foreign('pardonnee_par_id')->references('id')->on('employes')->nullOnDelete();
        // });
    }

    public function down(): void
    {
        // Si des FKs ont été ajoutées, pense à les drop() ici d’abord
        Schema::dropIfExists('payments_penalite');
    }
};
