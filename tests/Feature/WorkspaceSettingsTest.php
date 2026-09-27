<?php

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected when attempting to access workspace settings', function () {
    $response = $this->get('/w/acme/settings');
    $response->assertRedirect(route('login'));
});

test('tenant member can access workspace settings with comprehensive props', function () {
    $org = Organization::query()->create([
        'name' => 'Acme Corporation',
        'slug' => 'acme-corp',
        'timezone' => 'America/New_York',
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $response = $this->get('/w/acme-corp/settings');
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Settings')
        ->where('tenant_slug', 'acme-corp')
        ->has('members')
        ->has('passwordRules')
    );
});

test('user cannot view workspace settings of another organization', function () {
    $org1 = Organization::query()->create(['name' => 'Org 1', 'slug' => 'org-1', 'timezone' => 'UTC']);
    $org2 = Organization::query()->create(['name' => 'Org 2', 'slug' => 'org-2', 'timezone' => 'UTC']);

    $user = User::factory()->create(['organization_id' => $org1->id]);
    $this->actingAs($user);

    $response = $this->get('/w/org-2/settings');
    $response->assertForbidden();
});

test('superadmin can access any workspace settings', function () {
    $org = Organization::query()->create(['name' => 'Client Org', 'slug' => 'client-org', 'timezone' => 'UTC']);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $response = $this->get('/w/client-org/settings');
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Settings')
        ->where('tenant_slug', 'client-org')
    );
});
