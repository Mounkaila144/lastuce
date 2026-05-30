<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Galerie publique — affiche les images éditorialisées par l'admin
 * (tournages, coulisses, événements). Page Inertia unique.
 */
class GalleryController extends Controller
{
    public function index(): Response
    {
        $images = GalleryImage::query()
            ->visible()
            ->ordered()
            ->with('media')
            ->get()
            ->map(function (GalleryImage $image) {
                $media = $image->getFirstMedia('image');

                return [
                    'id' => $image->id,
                    'titre' => $image->titre,
                    'description' => $image->description,
                    'thumb_url' => $media?->getUrl('thumb'),
                    'card_url' => $media?->getUrl('card'),
                    'full_url' => $media?->getUrl('hero') ?: $media?->getUrl(),
                ];
            })
            ->filter(fn ($item) => $item['full_url'] !== null)
            ->values();

        return Inertia::render('Gallery/Index', [
            'images' => $images,
        ]);
    }
}
