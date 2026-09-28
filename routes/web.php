<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\EnsureTenantAccess;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', EnsureTenantAccess::class])
    ->prefix('/w/{tenant_slug}')
    ->group(function () {
        Route::get('/', [WorkspaceController::class, 'index']);
        Route::get('/posts', [WorkspaceController::class, 'posts'])->name('workspace.posts');
        Route::get('/composer', [WorkspaceController::class, 'composer'])->name('workspace.composer');
        Route::get('/channels', [WorkspaceController::class, 'channels'])->name('workspace.channels');
        Route::get('/settings', [WorkspaceController::class, 'settings'])->name('workspace.settings');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
