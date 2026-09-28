<?php

use App\Http\Middleware\EnsureTenantAccess;
use App\Models\Organization;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Posexei Multi-Tenant SaaS Landing Page
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified', EnsureTenantAccess::class])->prefix('w/{tenant_slug}')->group(function () {
    Route::get('/', function (string $tenant_slug) {
        return redirect("/w/{$tenant_slug}/posts");
    });

    Route::get('/posts', function (string $tenant_slug) {
        return Inertia::render('workspace/PostsFeed', [
            'tenant_slug' => $tenant_slug,
        ]);
    })->name('workspace.posts');

    Route::get('/composer', function (string $tenant_slug) {
        return Inertia::render('workspace/Composer', [
            'tenant_slug' => $tenant_slug,
        ]);
    })->name('workspace.composer');

    Route::get('/channels', function (string $tenant_slug) {
        return Inertia::render('workspace/Channels', [
            'tenant_slug' => $tenant_slug,
        ]);
    })->name('workspace.channels');

    Route::get('/settings', function (Request $request, string $tenant_slug) {
        /** @var Organization $organization */
        $organization = $request->attributes->get('tenant') ?? Organization::query()->where('slug', $tenant_slug)->firstOrFail();
        $user = $request->user();

        $canManageTwoFactor = class_exists(Features::class) && Features::canManageTwoFactorAuthentication();
        $canManagePasskeys = class_exists(Features::class) && Features::canManagePasskeys();

        $passkeys = [];
        if ($canManagePasskeys && $user) {
            $passkeys = $user->passkeys()
                ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                ->latest()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'authenticator' => $p->authenticator,
                    'created_at_diff' => $p->created_at?->diffForHumans() ?? '',
                    'last_used_at_diff' => $p->last_used_at?->diffForHumans(),
                ])
                ->values()
                ->all();
        }

        $twoFactorEnabled = false;
        $requiresConfirmation = false;
        if ($canManageTwoFactor && $user) {
            $twoFactorEnabled = (bool) $user->hasEnabledTwoFactorAuthentication();
            $requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $members = $organization->users()
            ->select(['id', 'name', 'email', 'is_superadmin', 'role', 'created_at'])
            ->get()
            ->map(fn ($u) => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->isSuperAdmin() ? 'Owner' : ($u->role ?: 'Member'),
                'created_at' => $u->created_at?->format('M d, Y') ?? 'Recently',
            ])
            ->values()
            ->all();

        $seats = 5;
        if ($organization->subscription) {
            $basePlan = $organization->subscription->basePlan();
            $seats = (int) ($basePlan?->features['team_members'] ?? 5);
        }

        return Inertia::render('workspace/Settings', [
            'tenant_slug' => $tenant_slug,
            'organization_id' => (string) $organization->id,
            'organization_name' => $organization->name,
            'organization_seats' => $seats,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'canManageTwoFactor' => $canManageTwoFactor,
            'canManagePasskeys' => $canManagePasskeys,
            'passkeys' => $passkeys,
            'twoFactorEnabled' => $twoFactorEnabled,
            'requiresConfirmation' => $requiresConfirmation,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'members' => $members,
        ]);
    })->name('workspace.settings');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // !FIXME: move logic to controller
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        /** @var Organization|null $organization */
        $organization = null;

        if ($user->isSuperAdmin()) {
            $preferredSlug = $request->cookie('active_tenant_slug');
            if (is_string($preferredSlug) && ! empty($preferredSlug)) {
                $organization = Organization::query()->where('slug', $preferredSlug)->first();
            }
            if (! $organization) {
                $organization = $user->organization ?? Organization::query()->first();
            }
        } else {
            $organization = $user->organization;
        }

        if (! $organization) {
            $baseSlug = Str::slug($user->name);
            $slug = ! empty($baseSlug) ? $baseSlug : 'workspace';
            if (Organization::query()->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::lower(Str::random(4));
            }

            $organization = Organization::query()->create([
                'name' => $user->name.' Workspace',
                'slug' => $slug,
                'timezone' => 'UTC',
            ]);

            $user->update(['organization_id' => $organization->id]);
        }

        $redirectPath = $request->query('redirect', '/posts');
        if (! is_string($redirectPath) || ! str_starts_with($redirectPath, '/')) {
            $redirectPath = '/posts';
        }

        return redirect("/w/{$organization->slug}{$redirectPath}");
    })->name('dashboard');
});

require __DIR__.'/settings.php';
