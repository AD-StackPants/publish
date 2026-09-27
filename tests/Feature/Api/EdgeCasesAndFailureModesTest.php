<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('partial failure sets partial_failure status and isolated retry only retries failed channel', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme Corp', 'slug' => 'acme', 'timezone' => 'UTC']);
    $twAccount = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'twitter',
        'account_id' => 'tw_123',
        'name' => 'Acme Twitter',
        'status' => 'healthy',
    ]);
    $liAccount = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'linkedin',
        'account_id' => 'li_456',
        'name' => 'Acme LinkedIn',
        'status' => 'healthy',
    ]);

    // Initial dispatch: Twitter succeeds (201), LinkedIn hits rate limit (429) on attempt 1, succeeds on attempt 2
    $twitterCalls = 0;
    $linkedInCalls = 0;
    $linkedInAttempt = 0;

    Http::fake([
        'api.twitter.com/2/tweets' => function () use (&$twitterCalls) {
            $twitterCalls++;

            return Http::response(['data' => ['id' => 'tw_tweet_100']], 201);
        },
        'api.linkedin.com/v2/ugcPosts' => function () use (&$linkedInCalls, &$linkedInAttempt) {
            $linkedInCalls++;
            $linkedInAttempt++;
            if ($linkedInAttempt === 1) {
                return Http::response(['message' => 'Rate limit exceeded'], 429);
            }

            return Http::response(['id' => 'urn:li:share:99999'], 201);
        },
    ]);

    $idempotencyKey = (string) Str::uuid();

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Multi-channel post',
        'account_ids' => [$twAccount->id, $liAccount->id],
        'idempotency_key' => $idempotencyKey,
    ]);

    $response->assertStatus(201);
    expect($response->json('post.status'))->toBe('partial_failure');
    expect($twitterCalls)->toBe(1);
    expect($linkedInCalls)->toBe(1);

    $postId = $response->json('post.id');

    // Isolated Retry: When retrying failed post, Twitter must NOT be called again!
    $retryResponse = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson("/api/v1/posts/{$postId}/retry-failed");

    $retryResponse->assertStatus(200);
    expect($retryResponse->json('status'))->toBe('published');

    // VERIFY ISOLATED EXECUTION: Twitter was NOT called a second time!
    expect($twitterCalls)->toBe(1);
    expect($linkedInCalls)->toBe(2);

    // VERIFY ISOLATED EXECUTION: Twitter was NOT called a second time!
    expect($twitterCalls)->toBe(1);
    expect($linkedInCalls)->toBe(2);
});

test('facebook OAuthException 190 password changed revokes social account and sends post to DLQ', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme Corp', 'slug' => 'acme', 'timezone' => 'UTC']);
    $fbAccount = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'facebook',
        'account_id' => 'page_789',
        'name' => 'Acme Facebook',
        'status' => 'healthy',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => [
                'message' => 'Error validating access token: The session has been invalidated because the user changed their password.',
                'type' => 'OAuthException',
                'code' => 190,
                'error_subcode' => 460,
            ],
        ], 400),
    ]);

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Facebook post with revoked token',
        'account_ids' => [$fbAccount->id],
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertStatus(201);
    expect($response->json('post.status'))->toBe('dlq');

    // Account status should be updated to revoked
    $fbAccount->refresh();
    expect($fbAccount->status)->toBe('revoked');
});

test('past timestamp gracefully falls back to immediate dispatch instead of being stranded in scheduled', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme Corp', 'slug' => 'acme', 'timezone' => 'UTC']);
    $account = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'twitter',
        'account_id' => 'tw_past',
        'name' => 'Acme Twitter',
        'status' => 'healthy',
    ]);

    Http::fake([
        'api.twitter.com/2/tweets' => Http::response(['data' => ['id' => 'tw_past_123']], 201),
    ]);

    // User picked 2 minutes ago while composing
    $pastDate = now()->subMinutes(2)->toIso8601String();

    $response = $this->withHeaders([
        'X-Organization-Id' => $org->id,
    ])->postJson('/api/v1/social/posts', [
        'content' => 'Post with past timestamp',
        'account_ids' => [$account->id],
        'scheduled_at' => $pastDate,
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertStatus(201);
    // Graceful fallback to immediate dispatch
    expect($response->json('post.status'))->toBe('published');
});

test('artisan posts:publish-scheduled command dispatches due scheduled posts', function () {
    config(['app.env' => 'production']);

    $org = Organization::query()->create(['name' => 'Acme Corp', 'slug' => 'acme', 'timezone' => 'UTC']);
    $account = SocialAccount::query()->create([
        'organization_id' => $org->id,
        'provider' => 'twitter',
        'account_id' => 'tw_sched',
        'name' => 'Acme Twitter',
        'status' => 'healthy',
    ]);

    $scheduledPost = Post::query()->create([
        'organization_id' => $org->id,
        'content' => 'Scheduled tweet due now',
        'status' => 'scheduled',
        'scheduled_at' => now()->subMinute(),
        'target_account_ids' => [$account->id],
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $scheduledPost->checkpoints()->create([
        'step' => 'Downstream API Dispatch',
        'status' => 'pending',
    ]);

    Http::fake([
        'api.twitter.com/2/tweets' => Http::response(['data' => ['id' => 'tw_sched_999']], 201),
    ]);

    $this->artisan('posts:publish-scheduled')
        ->assertSuccessful();

    $scheduledPost->refresh();
    expect($scheduledPost->status)->toBe('published');
});
