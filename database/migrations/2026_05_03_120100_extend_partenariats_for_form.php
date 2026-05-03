<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Story S6.2 — enrichir le formulaire de partenariat (téléphone, type, budget,
 * site web et méta anti-spam). On garde les colonnes minimales `nom_entreprise`,
 * `contact`, `email`, `message` créées par la migration initiale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partenariats', function (Blueprint $table) {
            $table->string('telephone', 50)->nullable()->after('email');
            $table->string('site_web', 255)->nullable()->after('telephone');
            $table->string('type_partenariat', 60)->nullable()->after('site_web');
            $table->string('budget_envisage', 60)->nullable()->after('type_partenariat');
            $table->ipAddress('ip_demandeur')->nullable()->after('notes_internes');
            $table->string('user_agent', 512)->nullable()->after('ip_demandeur');
        });
    }

    public function down(): void
    {
        Schema::table('partenariats', function (Blueprint $table) {
            $table->dropColumn([
                'telephone',
                'site_web',
                'type_partenariat',
                'budget_envisage',
                'ip_demandeur',
                'user_agent',
            ]);
        });
    }
};
