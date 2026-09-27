<?php

declare(strict_types=1);

namespace App\Services\Channels\Contracts;

use App\Services\Channels\DTOs\PublishResult;
use App\Services\Channels\DTOs\TokenValidationResult;

interface SocialChannelInterface
{
    /**
     * Canonical name of the provider (e.g. 'facebook', 'twitter', 'linkedin').
     */
    public function provider(): string;

    /**
     * Validate an account access token.
     */
    public function validateToken(string $token, string $accountId): TokenValidationResult;

    /**
     * Publish a post to the external social channel.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function publish(
        string $accountId,
        string $content,
        ?string $token = null,
        ?string $mediaUrl = null,
        array $overrides = []
    ): PublishResult;
}
