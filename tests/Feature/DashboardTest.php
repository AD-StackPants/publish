<?php

use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from dashboard to their workspace', function () {
    $user = User::factory()->create();
    $org = Organization::query()->create([
        'name' => 'Acme Org',
        'slug' => 'acme-org',
        'timezone' => 'UTC',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/w/acme-org/posts');
});

test('authenticated users are redirected to custom path if specified', function () {
    $user = User::factory()->create();
    $org = Organization::query()->create([
        'name' => 'Acme Org',
        'slug' => 'acme-org',
        'timezone' => 'UTC',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard', ['redirect' => '/composer']));
    $response->assertRedirect('/w/acme-org/composer');
});

test('authenticated users without existing organization get default workspace created and redirected', function () {
    $user = User::factory()->create(['name' => 'Sarah Connor']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/w/sarah-connor/posts');
    $this->assertDatabaseHas('organizations', ['slug' => 'sarah-connor']);
});
