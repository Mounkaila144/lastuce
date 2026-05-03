<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Story S4.1 — métadonnées de soumission utiles pour anti-spam
 * et statistiques (IP, user-agent), et `slug` côté admin (Epic 8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('astuces_soumises', function (Blueprint $table) {
            if (!Schema::hasColumn('astuces_soumises', 'ip_soumetteur')) {
                $table->string('ip_soumetteur', 45)->nullable();
            }
            if (!Schema::hasColumn('astuces_soumises', 'user_agent')) {
                $table->string('user_agent', 512)->nullable();
            }
            if (!Schema::hasColumn('astuces_soumises', 'source')) {
                $table->string('source', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('astuces_soumises', function (Blueprint $table) {
            $table->dropColumn(['ip_soumetteur', 'user_agent', 'source']);
        });
    }
};
