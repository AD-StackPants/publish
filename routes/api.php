<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Billing\BillingCatalogController;
use App\Http\Controllers\Api\V1\Billing\CartCheckoutController;
use App\Http\Controllers\Api\V1\Billing\InvoiceController;
use App\Http\Controllers\Api\V1\Billing\SubscriptionController;
use App\Http\Controllers\Api\V1\DeadLetterQueueController;
use App\Http\Controllers\Api\V1\LinkPreviewController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\SystemHealthController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public / non-tenant-gated utilities
    Route::get('/user/organizations', [WorkspaceController::class, 'userOrganizations']);
    Route::get('/tools/preview-link', [LinkPreviewController::class, 'preview']);
    Route::get('/billing/plans', [BillingCatalogController::class, 'index']);
    Route::get('/system/health', [SystemHealthController::class, 'health']);

    // Tenant-isolated endpoints enforced via X-Organization-Id
    Route::middleware([ResolveTenant::class])->group(function () {
        // Workspace & Team Members
        Route::patch('/organizations/{id}', [WorkspaceController::class, 'update']);
        Route::get('/organizations/{id}/members', [WorkspaceController::class, 'members']);
        Route::post('/organizations/{id}/members', [WorkspaceController::class, 'inviteMember']);
        Route::delete('/organizations/{id}/members/{userId}', [WorkspaceController::class, 'removeMember']);

        // Social Accounts Hub
        Route::get('/accounts', [SocialAccountController::class, 'index']);
        Route::post('/accounts', [SocialAccountController::class, 'store']);
        Route::delete('/accounts/{id}', [SocialAccountController::class, 'destroy']);
        Route::post('/accounts/{id}/reconnect', [SocialAccountController::class, 'reconnect']);

        // Publishing & Jobs Engine
        Route::get('/posts', [PostController::class, 'index']);
        Route::post('/social/posts', [PostController::class, 'store']);
        Route::get('/jobs/{job_id}', [PostController::class, 'jobStatus']);
        Route::patch('/posts/{id}', [PostController::class, 'update']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
        Route::post('/posts/{id}/retry-failed', [PostController::class, 'retryFailed']);

        // Dead Letter Queue Replay
        Route::post('/dlq/{service_name}/{message_id}/replay', [DeadLetterQueueController::class, 'replayServiceMessage']);
        Route::post('/dlq/{id}/replay', [DeadLetterQueueController::class, 'replay']);

        // Billing & Cart Checkout
        Route::get('/billing/subscription', [SubscriptionController::class, 'show']);
        Route::post('/billing/change-plan', [SubscriptionController::class, 'changePlan']);
        Route::post('/billing/cart/checkout', [CartCheckoutController::class, 'checkout']);
        Route::get('/billing/invoices', [InvoiceController::class, 'index']);
        Route::get('/billing/invoices/{id}/download', [InvoiceController::class, 'download']);
    });
});
