<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Story S3.1 — Champs nécessaires au catalogue refondu :
 * - facebook_url : provider FB en plus de YouTube (cf. VideoEmbedService).
 * - invite_nom / invite_bio : crédits invité affichés sur la fiche.
 * - transcript : texte intégral de l'épisode pour SEO + accessibilité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'facebook_url')) {
                $table->string('facebook_url')->nullable()->after('youtube_url');
            }

            if (!Schema::hasColumn('episodes', 'invite_nom')) {
                $table->string('invite_nom')->nullable();
            }

            if (!Schema::hasColumn('episodes', 'invite_bio')) {
                $table->text('invite_bio')->nullable();
            }

            if (!Schema::hasColumn('episodes', 'transcript')) {
                $table->longText('transcript')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['facebook_url', 'invite_nom', 'invite_bio', 'transcript']);
        });
    }
};
