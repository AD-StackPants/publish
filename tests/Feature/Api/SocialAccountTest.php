<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('retrieves only social accounts belonging to the active tenant', function () {
    config(['app.env' => 'production']);

    $org1 = Organization::query()->create(['name' => 'Org 1', 'slug' => 'org-1', 'timezone' => 'UTC']);
    $org2 = Organization::query()->create(['name' => 'Org 2', 'slug' => 'org-2', 'timezone' => 'UTC']);

    $acc1 = SocialAccount::query()->create([
        'organization_id' => $org1->id,
        'provider' => 'linkedin',
        'account_id' => 'li_123',
        'name' => 'Org 1 LinkedIn',
        'status' => 'healthy',
    ]);

    SocialAccount::query()->create([
        'organization_id' => $org2->id,
        'provider' => 'twitter',
        'account_id' => 'tw_456',
        'name' => 'Org 2 Twitter',
        'status' => 'healthy',
    ]);

    $response = $this->withHeaders([
        'X-Organization-Id' => $org1->id,
    ])->getJson('/api/v1/accounts');

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Org 1 LinkedIn']);
});

test('can disconnect a social account', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme', 'slug' => 'acme', 'timezone' => 'UTC']);
    $acc = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'twitter',
        'account_id' => 'tw_123',
        'name' => 'Acme Twitter',
        'status' => 'healthy',
    ]);

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->deleteJson("/api/v1/accounts/{$acc->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('social_accounts', ['id' => $acc->id]);
});
