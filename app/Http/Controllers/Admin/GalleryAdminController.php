<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD admin de la galerie (Inertia). L'image est portée par Spatie Media
 * Library (collection `image`), à l'identique des épisodes.
 */
class GalleryAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GalleryImage::query()->with('media');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginator = $query->ordered()->paginate(24)->withQueryString();

        return Inertia::render('Admin/Gallery/Index', [
            'images' => $paginator->through(fn (GalleryImage $image) => $this->listRow($image)),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'stats' => [
                'total' => GalleryImage::count(),
                'visible' => GalleryImage::where('is_visible', true)->count(),
                'hidden' => GalleryImage::where('is_visible', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gallery/Form', [
            'image' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request, true);

        $image = GalleryImage::create([
            'titre' => $validated['titre'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_visible' => $validated['is_visible'] ?? true,
            'ordre' => $validated['ordre'] ?? 0,
        ]);

        if ($request->hasFile('image')) {
            $image->addMediaFromRequest('image')->toMediaCollection('image');
        }

        $this->logAction('gallery.create', $image, "a ajouté une image à la galerie « {$image->titre} »");

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Image ajoutée à la galerie.');
    }

    public function edit(GalleryImage $gallery): Response
    {
        return Inertia::render('Admin/Gallery/Form', [
            'image' => $this->detailPayload($gallery),
        ]);
    }

    public function update(Request $request, GalleryImage $gallery)
    {
        $validated = $this->validateRequest($request, false);

        $gallery->update([
            'titre' => $validated['titre'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_visible' => $validated['is_visible'] ?? false,
            'ordre' => $validated['ordre'] ?? 0,
        ]);

        if ($request->hasFile('image')) {
            $gallery->clearMediaCollection('image');
            $gallery->addMediaFromRequest('image')->toMediaCollection('image');
        }

        $this->logAction('gallery.update', $gallery, "a modifié une image de la galerie « {$gallery->titre} »");

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Image mise à jour.');
    }

    public function destroy(GalleryImage $gallery)
    {
        $titre = $gallery->titre;
        $gallery->delete();

        $this->logAction('gallery.delete', null, "a supprimé une image de la galerie « {$titre} »");

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Image supprimée.');
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function validateRequest(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'titre' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['boolean'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function listRow(GalleryImage $image): array
    {
        $media = $image->getFirstMedia('image');

        return [
            'id' => $image->id,
            'titre' => $image->titre,
            'description' => $image->description,
            'is_visible' => $image->is_visible,
            'ordre' => $image->ordre,
            'thumb_url' => $media?->getUrl('thumb'),
            'created_at' => $image->created_at->toIso8601String(),
            'edit_url' => route('admin.gallery.edit', $image),
        ];
    }

    private function detailPayload(GalleryImage $image): array
    {
        $media = $image->getFirstMedia('image');

        return [
            'id' => $image->id,
            'titre' => $image->titre,
            'description' => $image->description,
            'is_visible' => $image->is_visible,
            'ordre' => $image->ordre,
            'image_url' => $media?->getUrl('card'),
            'has_image' => $media !== null,
        ];
    }

    private function logAction(string $action, ?GalleryImage $image, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $image ? GalleryImage::class : null,
            'model_id' => $image?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => 'info',
        ]);
    }
}
