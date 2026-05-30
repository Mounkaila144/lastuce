<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD admin des logos de partenaires affichés sur la page d'accueil.
 * Distinct des *demandes* de partenariat (cf. PartenariatAdminController).
 */
class PartnerAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Partner::query()->with('media');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('nom', 'like', "%{$search}%");
        }

        $paginator = $query->ordered()->paginate(24)->withQueryString();

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $paginator->through(fn (Partner $partner) => $this->listRow($partner)),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'stats' => [
                'total' => Partner::count(),
                'visible' => Partner::where('is_visible', true)->count(),
                'hidden' => Partner::where('is_visible', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Partners/Form', [
            'partner' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request, true);

        $partner = Partner::create([
            'nom' => $validated['nom'],
            'site_web' => $validated['site_web'] ?? null,
            'is_visible' => $validated['is_visible'] ?? true,
            'ordre' => $validated['ordre'] ?? 0,
        ]);

        if ($request->hasFile('logo')) {
            $partner->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        $this->logAction('partner.create', $partner, "a ajouté le partenaire « {$partner->nom} »");

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partenaire ajouté.');
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Admin/Partners/Form', [
            'partner' => $this->detailPayload($partner),
        ]);
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $this->validateRequest($request, false);

        $partner->update([
            'nom' => $validated['nom'],
            'site_web' => $validated['site_web'] ?? null,
            'is_visible' => $validated['is_visible'] ?? false,
            'ordre' => $validated['ordre'] ?? 0,
        ]);

        if ($request->hasFile('logo')) {
            $partner->clearMediaCollection('logo');
            $partner->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        $this->logAction('partner.update', $partner, "a modifié le partenaire « {$partner->nom} »");

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partenaire mis à jour.');
    }

    public function destroy(Partner $partner)
    {
        $nom = $partner->nom;
        $partner->delete();

        $this->logAction('partner.delete', null, "a supprimé le partenaire « {$nom} »");

        return redirect()->route('admin.partners.index')
            ->with('success', 'Partenaire supprimé.');
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function validateRequest(Request $request, bool $logoRequired): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'is_visible' => ['boolean'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'logo' => [$logoRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function listRow(Partner $partner): array
    {
        $media = $partner->getFirstMedia('logo');

        return [
            'id' => $partner->id,
            'nom' => $partner->nom,
            'site_web' => $partner->site_web,
            'is_visible' => $partner->is_visible,
            'ordre' => $partner->ordre,
            'logo_url' => $media?->getUrl('logo') ?: $media?->getUrl(),
            'created_at' => $partner->created_at->toIso8601String(),
            'edit_url' => route('admin.partners.edit', $partner),
        ];
    }

    private function detailPayload(Partner $partner): array
    {
        $media = $partner->getFirstMedia('logo');

        return [
            'id' => $partner->id,
            'nom' => $partner->nom,
            'site_web' => $partner->site_web,
            'is_visible' => $partner->is_visible,
            'ordre' => $partner->ordre,
            'logo_url' => $media?->getUrl('logo') ?: $media?->getUrl(),
            'has_logo' => $media !== null,
        ];
    }

    private function logAction(string $action, ?Partner $partner, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $partner ? Partner::class : null,
            'model_id' => $partner?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => 'info',
        ]);
    }
}
