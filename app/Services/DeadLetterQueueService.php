<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;

final class DeadLetterQueueService
{
    public function __construct(
        private readonly PostPublishingService $publishingService,
    ) {}

    public function replay(string $tenantId, string $messageOrPostId): bool
    {
        /** @var Post|null $post */
        $post = Post::query()
            ->where('organization_id', $tenantId)
            ->where(function ($query) use ($messageOrPostId) {
                $query->where('id', $messageOrPostId)
                    ->orWhere('idempotency_key', $messageOrPostId);
            })
            ->first();

        if ($post) {
            $this->publishingService->retryFailedPost($tenantId, $post->id);

            return true;
        }

        return false;
    }
}
