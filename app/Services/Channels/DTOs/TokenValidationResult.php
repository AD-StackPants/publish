<?php

declare(strict_types=1);

namespace App\Services\Channels\DTOs;

final readonly class TokenValidationResult
{
    /**
     * @param  list<string>|null  $scopes
     */
    public function __construct(
        public bool $valid,
        public string $accountId,
        public string $provider,
        public ?string $accountName = null,
        public ?array $scopes = null,
        public ?string $errorMessage = null,
    ) {}
}
