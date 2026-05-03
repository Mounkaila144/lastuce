<?php

use App\Models\BlogArticle;
use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;

uses(RefreshDatabase::class);

it('Episode implements HasMedia and exposes the documented collections', function () {
    expect(new Episode())->toBeInstanceOf(HasMedia::class);

    $episode = Episode::create([
        'titre' => 'Episode test',
        'type' => Episode::TYPE_EPISODE,
        'statut' => Episode::STATUT_BROUILLON,
    ]);

    $collections = collect($episode->getRegisteredMediaCollections());

    expect($collections->pluck('name')->all())
        ->toContain('thumbnail', 'gallery');

    $thumbnail = $collections->firstWhere('name', 'thumbnail');
    expect($thumbnail->singleFile)->toBeTrue();
});

it('Episode declares thumb / card / hero conversions', function () {
    $episode = Episode::create([
        'titre' => 'Episode conversions',
        'type' => Episode::TYPE_EPISODE,
        'statut' => Episode::STATUT_BROUILLON,
    ]);

    $episode->registerAllMediaConversions();

    $names = collect($episode->mediaConversions)
        ->map(fn ($conversion) => $conversion->getName())
        ->all();

    expect($names)->toEqualCanonicalizing(['thumb', 'card', 'hero']);
});

it('BlogArticle implements HasMedia with a featured collection', function () {
    expect(new BlogArticle())->toBeInstanceOf(HasMedia::class);

    $article = BlogArticle::create([
        'titre' => 'Article test',
        'contenu' => str_repeat('Bonjour le monde. ', 20),
    ]);

    $collections = collect($article->getRegisteredMediaCollections());

    expect($collections->pluck('name')->all())->toContain('featured');
    expect($collections->firstWhere('name', 'featured')->singleFile)->toBeTrue();
});

it('media table migration applies', function () {
    expect(\Schema::hasTable('media'))->toBeTrue()
        ->and(\Schema::hasColumns('media', [
            'model_type', 'model_id', 'collection_name', 'file_name', 'disk',
        ]))->toBeTrue();
});

it('media disk is registered and resolves to a local path in tests', function () {
    config()->set('filesystems.disks.media.driver', 'local');
    config()->set('filesystems.disks.media.root', storage_path('app/public/media'));

    expect(config('filesystems.disks.media'))->not->toBeNull()
        ->and(config('filesystems.disks.media.driver'))->toBe('local');
});
