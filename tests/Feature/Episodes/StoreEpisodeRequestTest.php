<?php

use App\Http\Requests\StoreEpisodeRequest;
use Illuminate\Support\Facades\Validator;

function validateEpisode(array $payload): \Illuminate\Validation\Validator
{
    /** @var StoreEpisodeRequest $request */
    $request = StoreEpisodeRequest::create('/test', 'POST', $payload);
    $request->setContainer(app());

    $validator = Validator::make($payload, $request->rules());
    $request->withValidator($validator);

    return $validator;
}

it('échoue si aucune URL vidéo n\'est fournie', function () {
    $validator = validateEpisode([
        'titre' => 'Démo',
        'type' => 'episode',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('youtube_url'))->toBeTrue();
});

it('passe avec une URL YouTube valide seule', function () {
    $validator = validateEpisode([
        'titre' => 'Démo YouTube',
        'type' => 'episode',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('passe avec une URL Facebook valide seule', function () {
    $validator = validateEpisode([
        'titre' => 'Démo Facebook',
        'type' => 'episode',
        'facebook_url' => 'https://www.facebook.com/lastuce/videos/123456789012345',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('rejette une URL YouTube de format inconnu', function () {
    $validator = validateEpisode([
        'titre' => 'Démo invalide',
        'type' => 'episode',
        'youtube_url' => 'https://vimeo.com/dQw4w9WgXcQ',
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejette un type inconnu', function () {
    $validator = validateEpisode([
        'titre' => 'X',
        'type' => 'unicorn',
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('type'))->toBeTrue();
});
