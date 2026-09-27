<?php

use Illuminate\Support\Facades\Route;

use Inertia\Inertia;

// Multi-Tenant SaaS Workspace Application
Route::redirect('/', '/w/acme-studio/posts')->name('home');

Route::prefix('w/{tenant_slug}')->group(function () {
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
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
