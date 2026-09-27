<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Services\Channels\Contracts\SocialChannelInterface;
use App\Services\Channels\DTOs\PublishResult;
use App\Services\Channels\DTOs\TokenValidationResult;
use Illuminate\Support\Facades\Http;
use Throwable;

final class LinkedInChannelService implements SocialChannelInterface
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.linkedin.base_url', 'https://api.linkedin.com/v2');
    }

    public function provider(): string
    {
        return 'linkedin';
    }

    public function validateToken(string $token, string $accountId): TokenValidationResult
    {
        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->get("{$this->baseUrl}/userinfo");

            if ($response->status() === 429) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'LinkedIn API rate limit exceeded (429).',
                );
            }

            if (! $response->successful()) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'Invalid LinkedIn token: '.$response->body(),
                );
            }

            $data = (array) $response->json();

            return new TokenValidationResult(
                valid: true,
                accountId: $accountId,
                provider: $this->provider(),
                accountName: (string) ($data['name'] ?? 'LinkedIn User'),
            );
        } catch (Throwable $e) {
            return new TokenValidationResult(
                valid: false,
                accountId: $accountId,
                provider: $this->provider(),
                errorMessage: 'LinkedIn connection error: '.$e->getMessage(),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function publish(
        string $accountId,
        string $content,
        ?string $token = null,
        ?string $mediaUrl = null,
        array $overrides = []
    ): PublishResult {
        $finalCommentary = isset($overrides['content']) && is_string($overrides['content'])
            ? $overrides['content']
            : $content;

        $url = "{$this->baseUrl}/ugcPosts";
        $headers = [
            'Authorization' => 'Bearer '.($token ?? 'simulated_li_token'),
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        $shareContent = [
            'shareCommentary' => ['text' => $finalCommentary],
            'shareMediaCategory' => 'NONE',
        ];

        if ($mediaUrl) {
            $shareContent['shareMediaCategory'] = 'ARTICLE';
            $shareContent['media'] = [
                [
                    'status' => 'READY',
                    'originalUrl' => $mediaUrl,
                ],
            ];
        }

        $authorUrn = str_starts_with($accountId, 'urn:li:')
            ? $accountId
            : "urn:li:organization:{$accountId}";

        $payload = [
            'author' => $authorUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => $shareContent,
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        try {
            $response = Http::timeout(10)->withHeaders($headers)->post($url, $payload);

            if ($response->status() === 429) {
                return new PublishResult(
                    success: false,
                    platformPostId: '',
                    errorMessage: 'LinkedIn API 429: Account Rate Limit Exceeded',
                );
            }

            if ($response->successful()) {
                $data = (array) $response->json();
                $urn = (string) ($data['id'] ?? 'urn:li:share:'.time());

                return new PublishResult(
                    success: true,
                    platformPostId: $urn,
                    permalink: "https://linkedin.com/feed/update/{$urn}",
                    rawResponse: $data,
                );
            }

            if ($response->status() === 401) {
                return new PublishResult(
                    success: false,
                    platformPostId: '',
                    errorMessage: 'LinkedIn 401: Access token expired or revoked',
                    rawResponse: (array) $response->json(),
                    isRevokedToken: true,
                );
            }

            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'LinkedIn API error ('.$response->status().'): '.$response->body(),
            );
        } catch (Throwable $e) {
            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'Network error posting to LinkedIn: '.$e->getMessage(),
            );
        }
    }
}
