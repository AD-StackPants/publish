<?php

declare(strict_types=1);

use App\Services\Channels\FacebookChannelService;
use App\Services\Channels\LinkedInChannelService;
use App\Services\Channels\SocialChannelManager;
use App\Services\Channels\TwitterChannelService;
use Illuminate\Support\Facades\Http;

test('social channel manager registers and resolves all three providers', function () {
    /** @var SocialChannelManager $manager */
    $manager = app(SocialChannelManager::class);

    expect($manager->supportedProviders())->toContain('facebook', 'twitter', 'linkedin')
        ->and($manager->channel('facebook'))->toBeInstanceOf(FacebookChannelService::class)
        ->and($manager->channel('twitter'))->toBeInstanceOf(TwitterChannelService::class)
        ->and($manager->channel('linkedin'))->toBeInstanceOf(LinkedInChannelService::class);
});

test('facebook channel publishes and handles successful graph API response', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'id' => 'fb_post_999128',
        ], 200),
    ]);

    /** @var FacebookChannelService $service */
    $service = app(FacebookChannelService::class);
    $result = $service->publish(
        accountId: 'page_123',
        content: 'Facebook release announcement',
        token: 'fb_token_test'
    );

    expect($result->success)->toBeTrue()
        ->and($result->platformPostId)->toBe('fb_post_999128')
        ->and($result->permalink)->toBe('https://facebook.com/page_123/posts/fb_post_999128');
});

test('twitter channel publishes and handles successful tweet response', function () {
    Http::fake([
        'api.twitter.com/2/tweets' => Http::response([
            'data' => [
                'id' => 'tw_1827364519',
                'text' => 'Hello X from Posexei',
            ],
        ], 201),
    ]);

    /** @var TwitterChannelService $service */
    $service = app(TwitterChannelService::class);
    $result = $service->publish(
        accountId: 'user_456',
        content: 'Hello X from Posexei',
        token: 'tw_token_test'
    );

    expect($result->success)->toBeTrue()
        ->and($result->platformPostId)->toBe('tw_1827364519')
        ->and($result->permalink)->toBe('https://x.com/user/status/tw_1827364519');
});

test('linkedin channel publishes and handles ugcPosts response', function () {
    Http::fake([
        'api.linkedin.com/v2/ugcPosts' => Http::response([
            'id' => 'urn:li:share:7192837465',
        ], 201),
    ]);

    /** @var LinkedInChannelService $service */
    $service = app(LinkedInChannelService::class);
    $result = $service->publish(
        accountId: 'org_789',
        content: 'Professional announcement on LinkedIn',
        token: 'li_token_test'
    );

    expect($result->success)->toBeTrue()
        ->and($result->platformPostId)->toBe('urn:li:share:7192837465')
        ->and($result->permalink)->toBe('https://linkedin.com/feed/update/urn:li:share:7192837465');
});

test('facebook channel catches rate limit 429 response', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Rate limit exceeded'],
        ], 429),
    ]);

    /** @var FacebookChannelService $service */
    $service = app(FacebookChannelService::class);
    $result = $service->publish(
        accountId: 'page_123',
        content: 'Post that gets rate-limited',
        token: 'fb_token_test'
    );

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('Account Rate Limit Exceeded');
});
