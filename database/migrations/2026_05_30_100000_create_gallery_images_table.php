<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galerie publique — chaque ligne porte une image (tournages, coulisses, etc.)
 * stockée via Spatie Media Library (collection `image`). Les métadonnées
 * éditoriales (titre, légende, ordre, visibilité) vivent ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('titre')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['is_visible', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_images');
    }
};
