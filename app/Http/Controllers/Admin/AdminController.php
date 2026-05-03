<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AdminNotification;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des utilisateurs (S8.8), logs/sécurité (S8.9) et paramètres (S8.10).
 * Migré de Blade vers Inertia.
 */
class AdminController extends Controller
{
    /* ==================================================================== */
    /* S8.8 — Gestion des admins                                            */
    /* ==================================================================== */

    public function users(Request $request): Response
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'locked') {
                $query->where('locked_until', '>', now());
            } else {
                $query->where(function ($q) {
                    $q->whereNull('locked_until')->orWhere('locked_until', '<=', now());
                });
            }
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $paginator->through(fn (User $u) => $this->userRow($u)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'stats' => [
                'total' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'locked' => User::where('locked_until', '>', now())->count(),
                'recent' => User::where('created_at', '>=', now()->subWeek())->count(),
            ],
            'options' => [
                'roles' => collect(User::getAvailableRoles())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'permissions' => collect(User::getAvailablePermissions())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ]);
    }

    public function createUser(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'options' => [
                'roles' => collect(User::getAvailableRoles())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
                'permissions' => collect(User::getAvailablePermissions())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            ],
        ]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,moderator'],
            'permissions' => ['array'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => true,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
        ]);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.create',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => auth()->user()?->name . " a créé l'utilisateur {$user->name}",
            'severity' => 'info',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function editUser(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => $this->userDetail($user),
            'options' => [
                'roles' => collect(User::getAvailableRoles())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
                'permissions' => collect(User::getAvailablePermissions())
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            ],
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,moderator'],
            'permissions' => ['array'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
        ]);
        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.update',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => auth()->user()?->name . " a modifié l'utilisateur {$user->name}",
            'severity' => 'info',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        $name = $user->name;
        $user->delete();

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.delete',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => auth()->user()?->name . " a supprimé l'utilisateur {$name}",
            'severity' => 'warning',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }

    public function lockUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas verrouiller votre propre compte.');
        }
        $user->lockAccount(24);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.lock',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => auth()->user()?->name . " a verrouillé le compte de {$user->name}",
            'severity' => 'warning',
        ]);

        return back()->with('success', 'Compte verrouillé.');
    }

    public function unlockUser(User $user)
    {
        $user->unlockAccount();

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.unlock',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => auth()->user()?->name . " a déverrouillé le compte de {$user->name}",
            'severity' => 'info',
        ]);

        return back()->with('success', 'Compte déverrouillé.');
    }

    public function bulkUserAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:lock,unlock,delete'],
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['exists:users,id'],
        ]);

        $count = 0;
        foreach (User::whereIn('id', $request->users)->get() as $user) {
            if ($user->id === auth()->id()) continue;
            match ($request->action) {
                'lock' => ($user->lockAccount(24) && $count++),
                'unlock' => ($user->unlockAccount() && $count++),
                'delete' => ($user->delete() && $count++),
            };
        }

        return back()->with('success', "Action appliquée à {$count} utilisateur(s).");
    }

    /* ==================================================================== */
    /* S8.9 — Sécurité & logs                                              */
    /* ==================================================================== */

    public function logs(Request $request): Response
    {
        $query = AdminLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity')->toString());
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        return Inertia::render('Admin/Security/Logs', [
            'logs' => $paginator->through(fn (AdminLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'severity' => $log->severity,
                'user_name' => $log->user?->name ?? '—',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
            'filters' => [
                'user_id' => $request->string('user_id')->toString(),
                'action' => $request->string('action')->toString(),
                'severity' => $request->string('severity')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
            'stats' => [
                'total' => AdminLog::count(),
                'today' => AdminLog::whereDate('created_at', today())->count(),
                'critical' => AdminLog::where('severity', 'critical')->count(),
                'errors' => AdminLog::where('severity', 'error')->count(),
            ],
            'options' => [
                'users' => User::where('is_admin', true)->get(['id', 'name'])
                    ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name])->values(),
                'actions' => AdminLog::distinct()->pluck('action')->values(),
                'severities' => ['info', 'warning', 'error', 'critical'],
            ],
        ]);
    }

    public function failedAttempts(Request $request): Response
    {
        $query = FailedLoginAttempt::query();
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', "%{$request->ip}%");
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        $paginator = $query->orderBy('attempted_at', 'desc')->paginate(50)->withQueryString();

        return Inertia::render('Admin/Security/FailedAttempts', [
            'attempts' => $paginator->through(fn (FailedLoginAttempt $a) => [
                'id' => $a->id,
                'email' => $a->email,
                'ip_address' => $a->ip_address,
                'type' => $a->type,
                'attempted_at' => $a->attempted_at?->toIso8601String(),
            ]),
            'filters' => [
                'ip' => $request->string('ip')->toString(),
                'email' => $request->string('email')->toString(),
            ],
            'stats' => [
                'total' => FailedLoginAttempt::count(),
                'today' => FailedLoginAttempt::whereDate('attempted_at', today())->count(),
                'last_hour' => FailedLoginAttempt::where('attempted_at', '>=', now()->subHour())->count(),
            ],
        ]);
    }

    public function blockedIps(Request $request): Response
    {
        $blockedIps = FailedLoginAttempt::select('ip_address')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('MAX(attempted_at) as last_attempt')
            ->where('attempted_at', '>=', now()->subHour())
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= 5')
            ->orderBy('attempts', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Security/BlockedIps', [
            'blocked_ips' => $blockedIps->through(fn ($row) => [
                'ip_address' => $row->ip_address,
                'attempts' => (int) $row->attempts,
                'last_attempt' => $row->last_attempt,
            ]),
        ]);
    }

    public function unblockIp(Request $request)
    {
        $request->validate(['ip_address' => ['required', 'ip']]);

        FailedLoginAttempt::where('ip_address', $request->ip_address)
            ->where('attempted_at', '>=', now()->subHour())
            ->delete();

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'security.unblock_ip',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => auth()->user()?->name . " a débloqué l'IP {$request->ip_address}",
            'severity' => 'warning',
        ]);

        return back()->with('success', "IP {$request->ip_address} débloquée.");
    }

    public function deleteLog(AdminLog $log)
    {
        $log->delete();
        return back()->with('success', 'Log supprimé.');
    }

    public function cleanupLogs(Request $request)
    {
        $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
        $deleted = AdminLog::where('created_at', '<', now()->subDays($request->days))->delete();

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'security.cleanup_logs',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => auth()->user()?->name . " a nettoyé {$deleted} log(s) de plus de {$request->days} jour(s)",
            'severity' => 'info',
        ]);

        return back()->with('success', "{$deleted} log(s) supprimé(s).");
    }

    /* ==================================================================== */
    /* S8.10 — Settings & maintenance                                      */
    /* ==================================================================== */

    public function settings(): Response
    {
        $settings = Cache::get('app_settings', []);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => array_merge([
                'site_name' => config('app.name'),
                'site_description' => '',
                'contact_email' => config('mail.from.address', ''),
                'maintenance_mode' => app()->isDownForMaintenance(),
                'ga_id' => '',
            ], $settings),
            'is_maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['required', 'email'],
            'ga_id' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $request->only(['site_name', 'site_description', 'contact_email', 'ga_id']);
        Cache::put('app_settings', $settings, now()->addDays(30));

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'settings.update',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => auth()->user()?->name . ' a mis à jour les paramètres du site',
            'severity' => 'info',
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Paramètres mis à jour.');
    }

    public function backup(): Response
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->map(fn ($file) => [
                'name' => basename($file),
                'size' => Storage::disk('local')->size($file),
                'date' => Storage::disk('local')->lastModified($file),
            ])
            ->sortByDesc('date')
            ->values();

        return Inertia::render('Admin/Settings/Backup', [
            'backups' => $backups,
        ]);
    }

    public function createBackup(Request $request)
    {
        try {
            Artisan::call('backup:run');
            AdminLog::create([
                'user_id' => auth()->id(),
                'action' => 'settings.backup',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => auth()->user()?->name . ' a créé une sauvegarde',
                'severity' => 'info',
            ]);
            return back()->with('success', 'Sauvegarde lancée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function maintenance(): Response
    {
        return Inertia::render('Admin/Settings/Maintenance', [
            'is_maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function toggleMaintenance(Request $request)
    {
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $action = 'désactivé';
            } else {
                Artisan::call('down', ['--secret' => 'admin-access']);
                $action = 'activé';
            }

            AdminLog::create([
                'user_id' => auth()->id(),
                'action' => 'settings.toggle_maintenance',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => auth()->user()?->name . " a {$action} le mode maintenance",
                'severity' => 'warning',
            ]);

            return back()->with('success', "Mode maintenance {$action}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /* ==================================================================== */
    /* API Admin                                                           */
    /* ==================================================================== */

    public function getNotifications(Request $request)
    {
        $notificationService = app(NotificationService::class);
        $notifications = $notificationService->getUnreadForUser(auth()->id(), 20);
        return response()->json($notifications);
    }

    public function markNotificationRead(AdminNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function userRow(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'is_admin' => (bool) $u->is_admin,
            'is_locked' => $u->locked_until && $u->locked_until->isFuture(),
            'locked_until' => optional($u->locked_until)->toIso8601String(),
            'created_at' => $u->created_at->toIso8601String(),
            'edit_url' => route('admin.users.edit', $u),
        ];
    }

    private function userDetail(User $u): array
    {
        return array_merge($this->userRow($u), [
            'permissions' => $u->permissions ?? [],
        ]);
    }
}
