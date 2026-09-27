<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns 400 when X-Organization-Id header is missing', function () {
    config(['app.env' => 'production']);

    $response = $this->getJson('/api/v1/accounts');

    $response->assertStatus(400)
        ->assertJson(['message' => 'Missing X-Organization-Id header.']);
});

test('returns 403 when X-Organization-Id does not match an existing tenant', function () {
    config(['app.env' => 'production']);

    $response = $this->withHeaders([
        'X-Organization-Id' => '01923a4b-7c8d-7e9f-a012-000000000000',
    ])->getJson('/api/v1/accounts');

    $response->assertStatus(403)
        ->assertJson(['message' => 'Unauthorized or invalid tenant organization.']);
});

test('succeeds when X-Organization-Id matches valid tenant', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create([
        'name' => 'Valid Tenant',
        'slug' => 'valid-tenant',
        'timezone' => 'UTC',
    ]);

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->getJson('/api/v1/accounts');

    $response->assertStatus(200)
        ->assertJson([]);
});
