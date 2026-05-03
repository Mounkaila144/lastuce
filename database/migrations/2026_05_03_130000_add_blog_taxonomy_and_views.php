<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_articles', 'vues')) {
                $table->unsignedInteger('vues')->default(0)->after('meta_description');
            }
            if (! Schema::hasColumn('blog_articles', 'categorie')) {
                $table->string('categorie')->nullable()->after('vues');
                $table->index('categorie');
            }
            if (! Schema::hasColumn('blog_articles', 'mots_cles')) {
                $table->json('mots_cles')->nullable()->after('categorie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            if (Schema::hasColumn('blog_articles', 'mots_cles')) {
                $table->dropColumn('mots_cles');
            }
            if (Schema::hasColumn('blog_articles', 'categorie')) {
                $table->dropIndex(['categorie']);
                $table->dropColumn('categorie');
            }
            if (Schema::hasColumn('blog_articles', 'vues')) {
                $table->dropColumn('vues');
            }
        });
    }
};
