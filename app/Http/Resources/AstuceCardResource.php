<?php

namespace App\Http\Resources;

use App\Http\Controllers\AstuceController;
use App\Models\AstucesSoumise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AstuceCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AstucesSoumise $astuce */
        $astuce = $this->resource;
        $locale = $request->route('locale') ?? app()->getLocale();

        return [
            'id' => $astuce->id,
            'titre' => $astuce->titre_astuce,
            'categorie' => $astuce->categorie,
            'categorie_label' => AstuceController::CATEGORIES[$astuce->categorie] ?? $astuce->categorie,
            'difficulte' => $astuce->difficulte,
            'difficulte_label' => AstuceController::DIFFICULTES[$astuce->difficulte] ?? $astuce->difficulte,
            'temps_estime' => $astuce->temps_estime,
            'extrait' => mb_substr(strip_tags((string) $astuce->description), 0, 160),
            'auteur' => $astuce->nom,
            'date' => optional($astuce->created_at)->toIso8601String(),
            'url' => "/{$locale}/astuces/{$astuce->id}",
        ];
    }
}
