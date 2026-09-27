<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Services\Channels\Contracts\SocialChannelInterface;
use App\Services\Channels\DTOs\PublishResult;
use App\Services\Channels\DTOs\TokenValidationResult;
use Illuminate\Support\Facades\Http;
use Throwable;

final class FacebookChannelService implements SocialChannelInterface
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.facebook.base_url', 'https://graph.facebook.com/v19.0');
    }

    public function provider(): string
    {
        return 'facebook';
    }

    public function validateToken(string $token, string $accountId): TokenValidationResult
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/me", [
                    'access_token' => $token,
                    'fields' => 'id,name',
                ]);

            if ($response->status() === 429) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'Meta Graph API rate limit exceeded (429).',
                );
            }

            if (! $response->successful()) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'Invalid Facebook token: '.$response->body(),
                );
            }

            $data = $response->json();

            return new TokenValidationResult(
                valid: true,
                accountId: $accountId,
                provider: $this->provider(),
                accountName: is_array($data) ? ($data['name'] ?? null) : null,
            );
        } catch (Throwable $e) {
            return new TokenValidationResult(
                valid: false,
                accountId: $accountId,
                provider: $this->provider(),
                errorMessage: 'Facebook connection error: '.$e->getMessage(),
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
        $finalMessage = isset($overrides['content']) && is_string($overrides['content'])
            ? $overrides['content']
            : $content;

        $url = "{$this->baseUrl}/{$accountId}/feed";
        $payload = [
            'message' => $finalMessage,
            'access_token' => $token ?? 'simulated_fb_token',
        ];

        if ($mediaUrl) {
            $payload['link'] = $mediaUrl;
        }

        try {
            $response = Http::timeout(10)->post($url, $payload);

            if ($response->status() === 429) {
                return new PublishResult(
                    success: false,
                    platformPostId: '',
                    errorMessage: 'Meta Graph API 429: Account Rate Limit Exceeded',
                );
            }

            if ($response->successful()) {
                $data = (array) $response->json();
                $postId = (string) ($data['id'] ?? "{$accountId}_".time());

                return new PublishResult(
                    success: true,
                    platformPostId: $postId,
                    permalink: "https://facebook.com/{$accountId}/posts/{$postId}",
                    rawResponse: $data,
                );
            }

            $data = (array) $response->json();
            $error = is_array($data['error'] ?? null) ? $data['error'] : [];
            $code = $error['code'] ?? null;
            $subcode = $error['error_subcode'] ?? null;
            $type = (string) ($error['type'] ?? '');

            if ($code === 190 || $type === 'OAuthException') {
                $msg = 'OAuthException 190: Access token revoked';
                if ($subcode === 460) {
                    $msg .= ' (password changed by user)';
                } elseif ($subcode === 463) {
                    $msg .= ' (token expired)';
                }

                return new PublishResult(
                    success: false,
                    platformPostId: '',
                    errorMessage: $msg,
                    rawResponse: $data,
                    isRevokedToken: true,
                );
            }

            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'Facebook API error ('.$response->status().'): '.$response->body(),
            );
        } catch (Throwable $e) {
            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'Network error posting to Facebook: '.$e->getMessage(),
            );
        }
    }
}
