<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('episode_tag', function (Blueprint $table) {
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['episode_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_tag');
        Schema::dropIfExists('tags');
    }
};
