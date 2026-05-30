<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partenaires affichés sur la page d'accueil (logos défilants). À ne pas
 * confondre avec la table `partenariats` qui stocke les *demandes* de
 * partenariat issues du formulaire public. Ici on gère uniquement les logos
 * éditorialisés par l'admin. Le logo est stocké via Spatie Media Library
 * (collection `logo`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('site_web')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['is_visible', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
