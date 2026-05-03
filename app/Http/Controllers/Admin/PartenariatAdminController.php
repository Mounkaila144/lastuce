<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Partenariat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Story S8.5 — CRUD admin Partenariats (Inertia).
 */
class PartenariatAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort_by', 'created_at')->toString();
        $direction = $request->string('sort_order', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'nom_entreprise', 'status'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = Partenariat::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('nom_entreprise', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return Inertia::render('Admin/Partenariats/Index', [
            'partenariats' => $paginator->through(fn (Partenariat $p) => $this->listRow($p)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'sort_by' => $sort,
                'sort_order' => $direction,
            ],
            'stats' => [
                'total' => Partenariat::count(),
                'nouveau' => Partenariat::where('status', 'nouveau')->count(),
                'en_cours' => Partenariat::where('status', 'en_cours')->count(),
                'accepte' => Partenariat::where('status', 'accepte')->count(),
                'refuse' => Partenariat::where('status', 'refuse')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Partenariats/Form', [
            'partenariat' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_entreprise' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'type_partenariat' => ['nullable', 'string', 'max:50'],
            'budget_envisage' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:20'],
            'status' => ['required', 'in:nouveau,en_cours,accepte,refuse'],
            'notes_internes' => ['nullable', 'string'],
        ]);

        $partenariat = Partenariat::create($validated);
        $this->logAction('partenariat.create', $partenariat, "a créé le partenariat « {$partenariat->nom_entreprise} »");
        $this->forgetCaches();

        return redirect()->route('admin.partenariats.index')
            ->with('success', 'Partenariat créé avec succès.');
    }

    public function show(Partenariat $partenariat): Response
    {
        return Inertia::render('Admin/Partenariats/Show', [
            'partenariat' => $this->detailPayload($partenariat),
        ]);
    }

    public function edit(Partenariat $partenariat): Response
    {
        return Inertia::render('Admin/Partenariats/Form', [
            'partenariat' => $this->detailPayload($partenariat),
        ]);
    }

    public function update(Request $request, Partenariat $partenariat)
    {
        $validated = $request->validate([
            'nom_entreprise' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'type_partenariat' => ['nullable', 'string', 'max:50'],
            'budget_envisage' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:20'],
            'status' => ['required', 'in:nouveau,en_cours,accepte,refuse'],
            'notes_internes' => ['nullable', 'string'],
        ]);

        $partenariat->update($validated);
        $this->logAction('partenariat.update', $partenariat, "a modifié le partenariat « {$partenariat->nom_entreprise} »");
        $this->forgetCaches();

        return redirect()->route('admin.partenariats.index')
            ->with('success', 'Partenariat mis à jour avec succès.');
    }

    public function destroy(Partenariat $partenariat)
    {
        $nom = $partenariat->nom_entreprise;
        $partenariat->delete();
        $this->logAction('partenariat.delete', null, "a supprimé le partenariat « {$nom} »");
        $this->forgetCaches();

        return redirect()->route('admin.partenariats.index')
            ->with('success', 'Partenariat supprimé.');
    }

    public function approve(Request $request, Partenariat $partenariat)
    {
        $request->validate(['notes_internes' => ['nullable', 'string']]);

        $partenariat->update([
            'status' => 'accepte',
            'notes_internes' => $request->notes_internes ?? $partenariat->notes_internes,
        ]);

        $this->logAction('partenariat.approve', $partenariat, "a accepté le partenariat « {$partenariat->nom_entreprise} »");
        $this->forgetCaches();

        return back()->with('success', 'Partenariat accepté.');
    }

    public function reject(Request $request, Partenariat $partenariat)
    {
        $request->validate(['notes_internes' => ['nullable', 'string']]);

        $partenariat->update([
            'status' => 'refuse',
            'notes_internes' => $request->notes_internes ?? $partenariat->notes_internes,
        ]);

        $this->logAction('partenariat.reject', $partenariat, "a refusé le partenariat « {$partenariat->nom_entreprise} »");
        $this->forgetCaches();

        return back()->with('success', 'Partenariat refusé.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,en_cours,delete'],
            'partenariats' => ['required', 'array', 'min:1'],
            'partenariats.*' => ['integer', 'exists:partenariats,id'],
        ]);

        $items = Partenariat::whereIn('id', $validated['partenariats']);
        $count = $items->count();

        match ($validated['action']) {
            'approve' => $items->update(['status' => 'accepte']),
            'reject' => $items->update(['status' => 'refuse']),
            'en_cours' => $items->update(['status' => 'en_cours']),
            'delete' => $items->delete(),
        };

        $this->forgetCaches();

        return back()->with('success', "Action appliquée à {$count} partenariat(s).");
    }

    public function export(): StreamedResponse
    {
        $partenariats = Partenariat::orderBy('created_at', 'desc')->get();
        $filename = 'partenariats_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($partenariats) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Entreprise', 'Contact', 'Email', 'Type', 'Budget', 'Statut', 'Date']);
            foreach ($partenariats as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->nom_entreprise,
                    $p->contact,
                    $p->email,
                    $p->type_partenariat,
                    $p->budget_envisage,
                    $p->status,
                    $p->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function listRow(Partenariat $p): array
    {
        return [
            'id' => $p->id,
            'nom_entreprise' => $p->nom_entreprise,
            'contact' => $p->contact,
            'email' => $p->email,
            'type_partenariat' => $p->type_partenariat,
            'budget_envisage' => $p->budget_envisage,
            'status' => $p->status,
            'created_at' => $p->created_at->toIso8601String(),
            'show_url' => route('admin.partenariats.show', $p),
            'edit_url' => route('admin.partenariats.edit', $p),
        ];
    }

    private function detailPayload(Partenariat $p): array
    {
        return [
            'id' => $p->id,
            'nom_entreprise' => $p->nom_entreprise,
            'contact' => $p->contact,
            'email' => $p->email,
            'telephone' => $p->telephone,
            'site_web' => $p->site_web,
            'type_partenariat' => $p->type_partenariat,
            'budget_envisage' => $p->budget_envisage,
            'message' => $p->message,
            'status' => $p->status,
            'notes_internes' => $p->notes_internes,
            'ip_demandeur' => $p->ip_demandeur,
            'created_at' => $p->created_at->toIso8601String(),
            'updated_at' => $p->updated_at->toIso8601String(),
        ];
    }

    private function logAction(string $action, ?Partenariat $partenariat, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $partenariat ? Partenariat::class : null,
            'model_id' => $partenariat?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => 'info',
        ]);
    }

    private function forgetCaches(): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin.pending_counts');
    }
}
