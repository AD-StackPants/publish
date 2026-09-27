<?php

use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from dashboard to their workspace', function () {
    $org = Organization::query()->create([
        'name' => 'Acme Org',
        'slug' => 'acme-org',
        'timezone' => 'UTC',
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/w/acme-org/posts');
});

test('authenticated users are redirected to custom path if specified', function () {
    $org = Organization::query()->create([
        'name' => 'Acme Org',
        'slug' => 'acme-org',
        'timezone' => 'UTC',
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard', ['redirect' => '/composer']));
    $response->assertRedirect('/w/acme-org/composer');
});

test('authenticated users without existing organization get default workspace created and redirected', function () {
    $user = User::factory()->withoutOrganization()->create(['name' => 'Sarah Connor']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/w/sarah-connor/posts');
    $this->assertDatabaseHas('organizations', ['slug' => 'sarah-connor']);
    expect($user->fresh()->organization_id)->not->toBeNull();
});

test('non-superadmin users cannot access another tenant workspace', function () {
    $org1 = Organization::query()->create(['name' => 'Org 1', 'slug' => 'org-1', 'timezone' => 'UTC']);
    $org2 = Organization::query()->create(['name' => 'Org 2', 'slug' => 'org-2', 'timezone' => 'UTC']);

    $user = User::factory()->create(['organization_id' => $org1->id]);
    $this->actingAs($user);

    $response = $this->get('/w/org-2/posts');
    $response->assertForbidden();
});

test('superadmin users can access any tenant workspace', function () {
    $org = Organization::query()->create(['name' => 'Target Tenant', 'slug' => 'target-tenant', 'timezone' => 'UTC']);
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $response = $this->get('/w/target-tenant/posts');
    $response->assertOk();
});
