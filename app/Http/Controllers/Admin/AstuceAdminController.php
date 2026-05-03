<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAstuceRequest;
use App\Http\Requests\RejectAstuceRequest;
use App\Models\AdminLog;
use App\Models\AstucesSoumise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Story S8.4 — Modération des astuces communautaires (Inertia).
 *
 * On conserve la logique d'approbation/rejet du legacy mais on bascule
 * en réponses Inertia + redirect+flash. Chaque action est loggée dans
 * admin_logs avec une sévérité différenciée (warning sur rejet/suppression).
 */
class AstuceAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort_by', 'created_at')->toString();
        $direction = $request->string('sort_order', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'titre_astuce', 'status'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = AstucesSoumise::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('titre_astuce', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('difficulte')) {
            $query->where('difficulte', $request->string('difficulte')->toString());
        }

        $paginator = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return Inertia::render('Admin/Astuces/Index', [
            'astuces' => $paginator->through(fn (AstucesSoumise $a) => $this->listRow($a)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'difficulte' => $request->string('difficulte')->toString(),
                'sort_by' => $sort,
                'sort_order' => $direction,
            ],
            'options' => [
                'statuses' => collect(AstucesSoumise::getStatuses())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'difficultes' => [
                    ['value' => 'facile', 'label' => 'Facile'],
                    ['value' => 'moyen', 'label' => 'Moyen'],
                    ['value' => 'difficile', 'label' => 'Difficile'],
                ],
            ],
            'stats' => [
                'total' => AstucesSoumise::count(),
                'en_attente' => AstucesSoumise::where('status', 'en_attente')->count(),
                'approuve' => AstucesSoumise::where('status', 'approuve')->count(),
                'rejete' => AstucesSoumise::where('status', 'rejete')->count(),
            ],
        ]);
    }

    public function show(AstucesSoumise $astuce): Response
    {
        return Inertia::render('Admin/Astuces/Show', [
            'astuce' => $this->detailPayload($astuce),
        ]);
    }

    public function approve(ApproveAstuceRequest $request, AstucesSoumise $astuce)
    {
        $astuce->update([
            'status' => AstucesSoumise::STATUS_APPROUVE,
            'commentaire_admin' => $request->validated('commentaire_admin'),
        ]);

        $this->logAction(
            'astuce.approve',
            $astuce,
            "a approuvé l'astuce « {$astuce->titre_astuce} »",
            'info',
        );
        $this->forgetCaches();

        return redirect()
            ->route('admin.astuces.index')
            ->with('success', 'Astuce approuvée.');
    }

    public function reject(RejectAstuceRequest $request, AstucesSoumise $astuce)
    {
        $astuce->update([
            'status' => AstucesSoumise::STATUS_REJETE,
            'commentaire_admin' => $request->validated('commentaire_admin'),
        ]);

        $this->logAction(
            'astuce.reject',
            $astuce,
            "a rejeté l'astuce « {$astuce->titre_astuce} »",
            'warning',
        );
        $this->forgetCaches();

        return redirect()
            ->route('admin.astuces.index')
            ->with('success', 'Astuce rejetée.');
    }

    public function destroy(AstucesSoumise $astuce)
    {
        $titre = $astuce->titre_astuce;

        // Nettoyage des fichiers liés (legacy : disk public).
        if (! empty($astuce->images) && is_array($astuce->images)) {
            foreach ($astuce->images as $img) {
                if (is_string($img)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }
        if ($astuce->fichier_joint) {
            Storage::disk('public')->delete($astuce->fichier_joint);
        }

        $astuce->delete();

        $this->logAction(
            'astuce.delete',
            null,
            "a supprimé l'astuce « {$titre} »",
            'warning',
        );
        $this->forgetCaches();

        return redirect()
            ->route('admin.astuces.index')
            ->with('success', 'Astuce supprimée.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'astuces' => ['required', 'array', 'min:1'],
            'astuces.*' => ['integer', 'exists:astuces_soumises,id'],
            'commentaire_admin' => ['nullable', 'string', 'max:5000'],
        ]);

        // Le rejet en lot exige un commentaire (cohérent avec RejectAstuceRequest).
        if ($validated['action'] === 'reject' && empty(trim($validated['commentaire_admin'] ?? ''))) {
            return back()->withErrors([
                'commentaire_admin' => 'Un commentaire est requis pour rejeter en lot.',
            ]);
        }

        $astuces = AstucesSoumise::whereIn('id', $validated['astuces'])->get();
        $count = 0;

        foreach ($astuces as $astuce) {
            switch ($validated['action']) {
                case 'approve':
                    if ($astuce->status !== AstucesSoumise::STATUS_APPROUVE) {
                        $astuce->update([
                            'status' => AstucesSoumise::STATUS_APPROUVE,
                            'commentaire_admin' => $validated['commentaire_admin'] ?? null,
                        ]);
                        $count++;
                    }
                    break;
                case 'reject':
                    if ($astuce->status !== AstucesSoumise::STATUS_REJETE) {
                        $astuce->update([
                            'status' => AstucesSoumise::STATUS_REJETE,
                            'commentaire_admin' => $validated['commentaire_admin'],
                        ]);
                        $count++;
                    }
                    break;
                case 'delete':
                    if (! empty($astuce->images) && is_array($astuce->images)) {
                        foreach ($astuce->images as $img) {
                            if (is_string($img)) {
                                Storage::disk('public')->delete($img);
                            }
                        }
                    }
                    if ($astuce->fichier_joint) {
                        Storage::disk('public')->delete($astuce->fichier_joint);
                    }
                    $astuce->delete();
                    $count++;
                    break;
            }
        }

        $this->logAction(
            "astuce.bulk.{$validated['action']}",
            null,
            "a appliqué l'action « {$validated['action']} » à {$count} astuce(s)",
            $validated['action'] === 'approve' ? 'info' : 'warning',
        );
        $this->forgetCaches();

        return back()->with('success', "Action appliquée à {$count} astuce(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:en_attente,approuve,rejete'],
        ]);

        $query = AstucesSoumise::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        $astuces = $query->get();
        $filename = 'astuces_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($astuces) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'ID', 'Titre', 'Auteur', 'Email', 'Catégorie', 'Difficulté',
                'Statut', 'Date soumission', 'Description (extrait)',
            ]);
            foreach ($astuces as $a) {
                fputcsv($out, [
                    $a->id,
                    $a->titre_astuce,
                    $a->nom,
                    $a->email,
                    $a->categorie,
                    $a->difficulte,
                    $a->status,
                    $a->created_at?->format('Y-m-d H:i'),
                    Str::limit(strip_tags((string) $a->description), 100),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function listRow(AstucesSoumise $a): array
    {
        return [
            'id' => $a->id,
            'titre_astuce' => $a->titre_astuce,
            'nom' => $a->nom,
            'email' => $a->email,
            'categorie' => $a->categorie,
            'difficulte' => $a->difficulte,
            'status' => $a->status,
            'status_label' => AstucesSoumise::getStatuses()[$a->status] ?? $a->status,
            'created_at' => $a->created_at?->toIso8601String(),
            'extrait' => Str::limit(strip_tags((string) $a->description), 140),
            'show_url' => route('admin.astuces.show', $a),
        ];
    }

    private function detailPayload(AstucesSoumise $a): array
    {
        return [
            'id' => $a->id,
            'titre_astuce' => $a->titre_astuce,
            'description' => $a->description,
            'categorie' => $a->categorie,
            'difficulte' => $a->difficulte,
            'temps_estime' => $a->temps_estime,
            'materiel_requis' => $a->materiel_requis,
            'etapes' => $a->etapes ?? [],
            'conseils' => $a->conseils,
            'fichier_joint' => $a->fichier_joint,
            'fichier_joint_url' => $a->fichier_joint
                ? Storage::disk('public')->url($a->fichier_joint)
                : null,
            'images' => collect($a->images ?? [])
                ->filter(fn ($p) => is_string($p))
                ->map(fn ($p) => [
                    'path' => $p,
                    'url' => Storage::disk('public')->url($p),
                ])
                ->values()
                ->all(),
            'nom' => $a->nom,
            'email' => $a->email,
            'status' => $a->status,
            'status_label' => AstucesSoumise::getStatuses()[$a->status] ?? $a->status,
            'commentaire_admin' => $a->commentaire_admin,
            'ip_soumetteur' => $a->ip_soumetteur,
            'created_at' => $a->created_at?->toIso8601String(),
            'updated_at' => $a->updated_at?->toIso8601String(),
        ];
    }

    private function logAction(string $action, ?AstucesSoumise $astuce, string $description, string $severity = 'info'): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $astuce ? AstucesSoumise::class : null,
            'model_id' => $astuce?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => $severity,
        ]);
    }

    private function forgetCaches(): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_dashboard_chart_30d');
        Cache::forget('admin.pending_counts');
    }
}
