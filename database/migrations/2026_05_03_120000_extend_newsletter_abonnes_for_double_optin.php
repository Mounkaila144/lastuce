<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Story S5.1 — passer la newsletter au double opt-in.
 *
 * On enrichit la table existante au lieu de la recréer : les comptes déjà
 * actifs restent valides, on les marque simplement `confirme = true` lors
 * du backfill (cf. seeder ou commande Artisan si besoin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_abonnes', function (Blueprint $table) {
            $table->boolean('confirme')->default(false)->after('status');
            $table->timestamp('date_confirmation')->nullable()->after('confirme');
            $table->string('source_inscription', 80)->nullable()->after('date_confirmation');
            $table->string('frequence_envoi', 32)->default('hebdomadaire')->after('source_inscription');
            $table->json('interets')->nullable()->after('frequence_envoi');
            $table->string('prenom', 100)->nullable()->after('interets');
            $table->string('nom', 100)->nullable()->after('prenom');
            $table->ipAddress('ip_inscription')->nullable()->after('nom');
            $table->timestamp('date_desinscription')->nullable()->after('ip_inscription');
            $table->string('raison_desinscription', 60)->nullable()->after('date_desinscription');
            $table->text('commentaire_desinscription')->nullable()->after('raison_desinscription');
        });

        // Les comptes existants en base sont considérés confirmés (statu quo
        // ante). Tout nouveau enregistrement passera par le double opt-in.
        \DB::table('newsletter_abonnes')->update(['confirme' => true]);
    }

    public function down(): void
    {
        Schema::table('newsletter_abonnes', function (Blueprint $table) {
            $table->dropColumn([
                'confirme',
                'date_confirmation',
                'source_inscription',
                'frequence_envoi',
                'interets',
                'prenom',
                'nom',
                'ip_inscription',
                'date_desinscription',
                'raison_desinscription',
                'commentaire_desinscription',
            ]);
        });
    }
};
