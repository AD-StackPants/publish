<?php

declare(strict_types=1);

namespace App\Services\Channels\DTOs;

final readonly class PublishResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $success,
        public string $platformPostId,
        public ?string $permalink = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
        public bool $isRevokedToken = false,
    ) {}
}
