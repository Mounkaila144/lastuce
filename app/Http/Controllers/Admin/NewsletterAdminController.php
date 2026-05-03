<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\NewsletterAbonne;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Story S8.6 — Gestion admin Newsletter (Inertia).
 */
class NewsletterAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NewsletterAbonne::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('date_inscription', 'desc')->paginate(30)->withQueryString();

        $counts = NewsletterAbonne::countByStatus();
        $total = array_sum($counts);
        $tauxActifs = $total > 0 ? round(($counts[NewsletterAbonne::STATUS_ACTIF] / $total) * 100) : 0;

        $newThisMonth = NewsletterAbonne::where('date_inscription', '>=', now()->startOfMonth())->count();

        return Inertia::render('Admin/Newsletter/Index', [
            'abonnes' => $paginator->through(fn (NewsletterAbonne $a) => $this->listRow($a)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'stats' => [
                'total' => $total,
                'actif' => $counts[NewsletterAbonne::STATUS_ACTIF] ?? 0,
                'inactif' => $counts[NewsletterAbonne::STATUS_INACTIF] ?? 0,
                'desabonne' => $counts[NewsletterAbonne::STATUS_DESABONNE] ?? 0,
                'taux_actifs' => $tauxActifs,
                'new_this_month' => $newThisMonth,
            ],
        ]);
    }

    public function activate(NewsletterAbonne $abonne)
    {
        $abonne->confirmer();
        $this->logAction('newsletter.activate', $abonne, "a activé l'abonné {$abonne->email}");

        return back()->with('success', 'Abonné activé.');
    }

    public function deactivate(NewsletterAbonne $abonne)
    {
        $abonne->update(['status' => NewsletterAbonne::STATUS_INACTIF, 'confirme' => false]);
        $this->logAction('newsletter.deactivate', $abonne, "a désactivé l'abonné {$abonne->email}");

        return back()->with('success', 'Abonné désactivé.');
    }

    public function destroy(NewsletterAbonne $abonne)
    {
        $email = $abonne->email;
        $abonne->desabonner('admin');
        $this->logAction('newsletter.unsubscribe', null, "a désabonné {$email}");

        return back()->with('success', 'Abonné désabonné.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'abonnes' => ['required', 'array', 'min:1'],
            'abonnes.*' => ['integer', 'exists:newsletter_abonnes,id'],
        ]);

        $items = NewsletterAbonne::whereIn('id', $validated['abonnes'])->get();
        $count = $items->count();

        match ($validated['action']) {
            'activate' => $items->each(fn ($a) => $a->confirmer()),
            'deactivate' => NewsletterAbonne::whereIn('id', $validated['abonnes'])
                ->update(['status' => NewsletterAbonne::STATUS_INACTIF, 'confirme' => false]),
            'delete' => NewsletterAbonne::whereIn('id', $validated['abonnes'])
                ->each(fn ($a) => $a->desabonner('admin')),
        };

        $this->logAction('newsletter.bulk.' . $validated['action'], null, "a appliqué « {$validated['action']} » à {$count} abonné(s)");

        return back()->with('success', "Action appliquée à {$count} abonné(s).");
    }

    public function export(): StreamedResponse
    {
        $abonnes = NewsletterAbonne::orderBy('date_inscription', 'desc')->get();
        $filename = 'newsletter_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($abonnes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Email', 'Prénom', 'Nom', 'Statut', 'Confirmé', 'Source', 'Date inscription']);
            foreach ($abonnes as $a) {
                fputcsv($out, [
                    $a->email,
                    $a->prenom,
                    $a->nom,
                    $a->status,
                    $a->confirme ? 'oui' : 'non',
                    $a->source_inscription,
                    optional($a->date_inscription)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function sendCampaign(Request $request)
    {
        return back()->with('info', 'Envoi de campagnes disponible en v1.1.');
    }

    /* Resource stubs — not needed for this model */
    public function create() { return redirect()->route('admin.newsletter.index'); }
    public function store(Request $request) { return redirect()->route('admin.newsletter.index'); }
    public function show(string $id) { return redirect()->route('admin.newsletter.index'); }
    public function edit(string $id) { return redirect()->route('admin.newsletter.index'); }
    public function update(Request $request, string $id) { return redirect()->route('admin.newsletter.index'); }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function listRow(NewsletterAbonne $a): array
    {
        return [
            'id' => $a->id,
            'email' => $a->email,
            'prenom_complet' => $a->prenom_complet,
            'status' => $a->status,
            'status_label' => $a->status_label,
            'confirme' => (bool) $a->confirme,
            'source_inscription' => $a->source_inscription,
            'date_inscription' => optional($a->date_inscription)->toIso8601String(),
        ];
    }

    private function logAction(string $action, ?NewsletterAbonne $abonne, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $abonne ? NewsletterAbonne::class : null,
            'model_id' => $abonne?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => 'info',
        ]);
    }
}
