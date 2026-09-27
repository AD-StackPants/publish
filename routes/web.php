<?php

use App\Http\Middleware\EnsureTenantAccess;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

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

    Route::get('/settings', function (string $tenant_slug) {
        return Inertia::render('workspace/Settings', [
            'tenant_slug' => $tenant_slug,
        ]);
    })->name('workspace.settings');

    Route::get('/settings/billing', function (string $tenant_slug) {
        return Inertia::render('workspace/Billing', [
            'tenant_slug' => $tenant_slug,
        ]);
    })->name('workspace.billing');
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
            $baseSlug = $user ? Str::slug($user->name) : 'workspace';
            $slug = ! empty($baseSlug) ? $baseSlug : 'workspace';
            if (Organization::query()->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::lower(Str::random(4));
            }

            $organization = Organization::query()->create([
                'name' => ($user ? $user->name : 'My').' Workspace',
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
