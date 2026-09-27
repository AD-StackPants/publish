<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('retrieves active plans catalog', function () {
    config(['app.env' => 'production']);

    Plan::query()->create([
        'slug' => 'plan-pro',
        'name' => 'Pro Plan',
        'type' => 'base_plan',
        'price_cents' => 1900,
        'billing_interval' => 'month',
        'features' => ['channels_limit' => 5],
        'is_active' => true,
    ]);

    Plan::query()->create([
        'slug' => 'addon-extra',
        'name' => 'Extra Channel Pack',
        'type' => 'addon',
        'price_cents' => 500,
        'billing_interval' => 'month',
        'features' => ['channels_limit' => 3],
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/billing/plans');

    $response->assertStatus(200)
        ->assertJsonStructure(['plans', 'base_plans', 'addons'])
        ->assertJsonCount(2, 'plans')
        ->assertJsonCount(1, 'base_plans')
        ->assertJsonCount(1, 'addons');
});

test('handles cart checkout and creates invoice with line items', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme', 'slug' => 'acme', 'timezone' => 'UTC']);
    $basePlan = Plan::query()->create([
        'slug' => 'plan-pro',
        'name' => 'Pro Plan',
        'type' => 'base_plan',
        'price_cents' => 1900,
        'billing_interval' => 'month',
        'features' => ['channels_limit' => 5],
        'is_active' => true,
    ]);

    $addonPlan = Plan::query()->create([
        'slug' => 'addon-channels',
        'name' => 'Channel Pack',
        'type' => 'addon',
        'price_cents' => 500,
        'billing_interval' => 'month',
        'features' => ['channels_limit' => 3],
        'is_active' => true,
    ]);

    $sub = Subscription::query()->create([
        'organization_id' => $org->id,
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $sub->subscriptionItems()->create([
        'plan_id' => $basePlan->id,
        'quantity' => 1,
    ]);

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/billing/cart/checkout', [
        'items' => [
            [
                'plan_id' => $addonPlan->id,
                'quantity' => 2,
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('invoices', [
        'organization_id' => $org->id,
        'total_cents' => 1000,
        'status' => 'paid',
    ]);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $sub->id,
        'plan_id' => $addonPlan->id,
        'quantity' => 2,
    ]);
});
