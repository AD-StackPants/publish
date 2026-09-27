<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('database seeder seeds stackpants tenant, active subscription, test user, and superadmin', function () {
    $this->seed(DatabaseSeeder::class);

    // Verify tenant
    $tenant = Organization::query()->where('slug', 'stackpants')->first();
    expect($tenant)->not->toBeNull();
    expect($tenant->name)->toBe('Stackpants');

    // Verify plans catalog
    $proPlan = Plan::query()->where('slug', 'plan-pro')->first();
    expect($proPlan)->not->toBeNull();
    expect($proPlan->type)->toBe('base_plan');

    // Verify subscription
    $subscription = Subscription::query()->where('organization_id', $tenant->id)->first();
    expect($subscription)->not->toBeNull();
    expect($subscription->status)->toBe('active');
    expect($subscription->basePlan()?->slug)->toBe('plan-pro');

    // Verify standard user with test email assigned to tenant
    $testUser = User::query()->where('email', 'test@example.com')->first();
    expect($testUser)->not->toBeNull();
    expect($testUser->organization_id)->toBe($tenant->id);
    expect($testUser->organization?->slug)->toBe('stackpants');
    expect($testUser->isSuperAdmin())->toBeFalse();
    expect($testUser->canBypassSubscription())->toBeFalse();

    // Verify superadmin users
    $superadmin = User::query()->where('email', 'superadmin@stackpants.com')->first();
    expect($superadmin)->not->toBeNull();
    expect($superadmin->organization_id)->toBe($tenant->id);
    expect($superadmin->isSuperAdmin())->toBeTrue();
    expect($superadmin->canBypassSubscription())->toBeTrue();

    $superadminExample = User::query()->where('email', 'superadmin@example.com')->first();
    expect($superadminExample)->not->toBeNull();
    expect($superadminExample->isSuperAdmin())->toBeTrue();
});

test('superadmin can oversee tenants and bypass subscription via gates and billing service', function () {
    $this->seed(DatabaseSeeder::class);

    /** @var User $testUser */
    $testUser = User::query()->where('email', 'test@example.com')->firstOrFail();
    /** @var User $superadmin */
    $superadmin = User::query()->where('email', 'superadmin@stackpants.com')->firstOrFail();
    /** @var Organization $tenant */
    $tenant = Organization::query()->where('slug', 'stackpants')->firstOrFail();

    // Verify Gates
    expect(Gate::forUser($testUser)->allows('bypass-subscription'))->toBeFalse();
    expect(Gate::forUser($testUser)->allows('oversee-tenants'))->toBeFalse();

    expect(Gate::forUser($superadmin)->allows('bypass-subscription'))->toBeTrue();
    expect(Gate::forUser($superadmin)->allows('oversee-tenants'))->toBeTrue();

    // Verify BillingService subscription details bypass
    /** @var BillingService $billingService */
    $billingService = app(BillingService::class);

    // As regular user
    $this->actingAs($testUser);
    $detailsForUser = $billingService->getSubscriptionDetails($tenant->id);
    expect($detailsForUser['channels_limit'])->toBe(5);
    expect($detailsForUser['is_bypassed'])->toBeFalse();

    // As superadmin
    $this->actingAs($superadmin);
    $detailsForAdmin = $billingService->getSubscriptionDetails($tenant->id);
    expect($detailsForAdmin['channels_limit'])->toBeGreaterThanOrEqual(9999);
    expect($detailsForAdmin['is_bypassed'])->toBeTrue();
    expect($detailsForAdmin['can_bypass_subscription'])->toBeTrue();
});

test('database seeder is idempotent and can be run multiple times safely', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Organization::query()->where('slug', 'stackpants')->count())->toBe(1);
    expect(User::query()->where('email', 'test@example.com')->count())->toBe(1);
    expect(User::query()->where('email', 'superadmin@stackpants.com')->count())->toBe(1);
    expect(Subscription::query()->count())->toBe(1);
});
