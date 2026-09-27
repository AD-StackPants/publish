<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('creates post and sets checkpoints', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme', 'slug' => 'acme', 'timezone' => 'UTC']);
    $account = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'linkedin',
        'account_id' => 'li_111',
        'name' => 'Acme LinkedIn',
        'status' => 'healthy',
    ]);

    $idempotencyKey = (string) Str::uuid();

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Hello World!',
        'account_ids' => [$account->id],
        'idempotency_key' => $idempotencyKey,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'job_id',
            'post' => [
                'id',
                'organization_id',
                'content',
                'status',
                'checkpoints',
            ],
        ]);

    $this->assertDatabaseHas('posts', [
        'organization_id' => $org->id,
        'content' => 'Hello World!',
        'idempotency_key' => $idempotencyKey,
    ]);

    $this->assertDatabaseCount('post_checkpoints', 3);
});

test('idempotency contract returns existing post record on duplicate submission within 24 hours', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme', 'slug' => 'acme', 'timezone' => 'UTC']);
    $account = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'twitter',
        'account_id' => 'tw_222',
        'name' => 'Acme Twitter',
        'status' => 'healthy',
    ]);

    $idempotencyKey = (string) Str::uuid();

    // First call
    $firstResponse = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Idempotent Tweet',
        'account_ids' => [$account->id],
        'idempotency_key' => $idempotencyKey,
    ]);

    $firstResponse->assertStatus(201);
    $firstPostId = $firstResponse->json('post.id');

    // Second call with same idempotency key
    $secondResponse = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Idempotent Tweet',
        'account_ids' => [$account->id],
        'idempotency_key' => $idempotencyKey,
    ]);

    $secondResponse->assertStatus(200)
        ->assertJsonFragment(['idempotent_replay' => true])
        ->assertJsonPath('post.id', $firstPostId);

    // Only 1 post created in DB
    $this->assertDatabaseCount('posts', 1);
});
