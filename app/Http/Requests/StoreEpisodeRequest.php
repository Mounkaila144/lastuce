<?php

namespace App\Http\Requests;

use App\Models\Episode;
use App\Services\VideoEmbedService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Story S3.1 — Validation à l'admission d'un épisode (côté admin et import).
 *
 * La règle métier : un épisode doit toujours pointer vers au moins une URL
 * vidéo connue (YouTube ou Facebook). On délègue la détection à VideoEmbedService
 * pour ne pas dupliquer les regex et pour rester aligné avec ce qui sera
 * effectivement embeddable côté front (cf. <VideoPlayer>).
 */
class StoreEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contenu' => ['nullable', 'string'],
            'transcript' => ['nullable', 'string'],
            'invite_nom' => ['nullable', 'string', 'max:255'],
            'invite_bio' => ['nullable', 'string'],
            'youtube_url' => [
                'nullable',
                'url',
                'regex:/^https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\//i',
            ],
            'facebook_url' => [
                'nullable',
                'url',
                'regex:/^https?:\/\/(www\.|m\.|web\.)?(facebook\.com|fb\.watch)\//i',
            ],
            'audio_url' => ['nullable', 'url'],
            'thumbnail_url' => ['nullable', 'url'],
            'duree' => ['nullable', 'integer', 'min:1'],
            'type' => ['required', Rule::in(array_keys(Episode::TYPES))],
            'statut' => ['nullable', Rule::in(array_keys(Episode::STATUTS))],
            'date_publication' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('episodes', 'slug')->ignore($this->route('episode'))],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            // Story S8.3 — upload depuis le back-office.
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $service = app(VideoEmbedService::class);
            $youtube = $this->input('youtube_url');
            $facebook = $this->input('facebook_url');

            $hasYoutube = $youtube && $service->detectProvider($youtube) === VideoEmbedService::PROVIDER_YOUTUBE;
            $hasFacebook = $facebook && $service->detectProvider($facebook) === VideoEmbedService::PROVIDER_FACEBOOK;

            if (!$hasYoutube && !$hasFacebook) {
                $v->errors()->add(
                    'youtube_url',
                    'Au moins une URL vidéo (YouTube ou Facebook) doit être fournie.',
                );
            }
        });
    }
}
