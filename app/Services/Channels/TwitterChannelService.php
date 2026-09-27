<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Services\Channels\Contracts\SocialChannelInterface;
use App\Services\Channels\DTOs\PublishResult;
use App\Services\Channels\DTOs\TokenValidationResult;
use Illuminate\Support\Facades\Http;
use Throwable;

final class TwitterChannelService implements SocialChannelInterface
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.twitter.base_url', 'https://api.twitter.com/2');
    }

    public function provider(): string
    {
        return 'twitter';
    }

    public function validateToken(string $token, string $accountId): TokenValidationResult
    {
        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->get("{$this->baseUrl}/users/me");

            if ($response->status() === 429) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'Twitter API rate limit exceeded (429).',
                );
            }

            if (! $response->successful()) {
                return new TokenValidationResult(
                    valid: false,
                    accountId: $accountId,
                    provider: $this->provider(),
                    errorMessage: 'Invalid Twitter token: '.$response->body(),
                );
            }

            $data = (array) $response->json();
            $user = is_array($data['data'] ?? null) ? $data['data'] : [];

            return new TokenValidationResult(
                valid: true,
                accountId: $accountId,
                provider: $this->provider(),
                accountName: (string) ($user['name'] ?? $user['username'] ?? 'Twitter User'),
            );
        } catch (Throwable $e) {
            return new TokenValidationResult(
                valid: false,
                accountId: $accountId,
                provider: $this->provider(),
                errorMessage: 'Twitter connection error: '.$e->getMessage(),
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
        $finalText = isset($overrides['content']) && is_string($overrides['content'])
            ? $overrides['content']
            : $content;

        if ($mediaUrl && ! str_contains($finalText, $mediaUrl)) {
            $finalText .= "\n".$mediaUrl;
        }

        $url = "{$this->baseUrl}/tweets";
        $payload = [
            'text' => $finalText,
        ];

        try {
            $response = Http::timeout(10)
                ->withToken($token ?? 'simulated_tw_token')
                ->post($url, $payload);

            if ($response->status() === 429) {
                return new PublishResult(
                    success: false,
                    platformPostId: '',
                    errorMessage: 'Twitter API 429: Rate limit cooling down active.',
                );
            }

            if ($response->successful()) {
                $data = (array) $response->json();
                $tweetData = is_array($data['data'] ?? null) ? $data['data'] : [];
                $tweetId = (string) ($tweetData['id'] ?? (string) time());

                return new PublishResult(
                    success: true,
                    platformPostId: $tweetId,
                    permalink: "https://x.com/user/status/{$tweetId}",
                    rawResponse: $data,
                );
            }

            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'Twitter API error ('.$response->status().'): '.$response->body(),
            );
        } catch (Throwable $e) {
            return new PublishResult(
                success: false,
                platformPostId: '',
                errorMessage: 'Network error posting to Twitter: '.$e->getMessage(),
            );
        }
    }
}
