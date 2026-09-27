<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;

final class DeadLetterQueueService
{
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
            $post->update([
                'status' => 'publishing',
            ]);

            $post->checkpoints()
                ->where('status', 'failed')
                ->update([
                    'status' => 'in_progress',
                    'error_message' => null,
                ]);

            return true;
        }

        return false;
    }
}
