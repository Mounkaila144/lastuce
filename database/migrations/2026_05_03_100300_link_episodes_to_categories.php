<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Story S3.1 — Migration de données :
 * - Ajoute episodes.category_id (FK).
 * - Convertit l'ancienne colonne string `category` en lignes `categories`.
 * - Convertit le JSON `tags` en pivot `episode_tag`.
 *
 * On garde les colonnes legacy (`category`, `tags`) en place : elles seront
 * supprimées dans une migration ultérieure quand l'admin (Epic 8) sera passé
 * à la table relationnelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('categories')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('episodes', 'category')) {
            $this->migrateCategories();
        }

        if (Schema::hasColumn('episodes', 'tags')) {
            $this->migrateTags();
        }
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });
    }

    private function migrateCategories(): void
    {
        $names = DB::table('episodes')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->unique()
            ->values();

        foreach ($names as $name) {
            $slug = Str::slug($name);
            if ($slug === '') {
                continue;
            }

            $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
            if (!$categoryId) {
                $categoryId = DB::table('categories')->insertGetId([
                    'nom' => $name,
                    'slug' => $slug,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('episodes')
                ->where('category', $name)
                ->update(['category_id' => $categoryId]);
        }
    }

    private function migrateTags(): void
    {
        $rows = DB::table('episodes')
            ->whereNotNull('tags')
            ->where('tags', '!=', '')
            ->select('id', 'tags')
            ->get();

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->tags, true);
            if (!is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $tagName) {
                if (!is_string($tagName)) {
                    continue;
                }
                $tagName = trim($tagName);
                if ($tagName === '') {
                    continue;
                }

                $slug = Str::slug($tagName);
                if ($slug === '') {
                    continue;
                }

                $tagId = DB::table('tags')->where('slug', $slug)->value('id');
                if (!$tagId) {
                    $tagId = DB::table('tags')->insertGetId([
                        'nom' => $tagName,
                        'slug' => $slug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('episode_tag')->insertOrIgnore([
                    'episode_id' => $row->id,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }
};
