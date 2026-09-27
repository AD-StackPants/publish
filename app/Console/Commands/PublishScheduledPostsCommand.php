<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostPublishingService;
use Illuminate\Console\Command;

final class PublishScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches scheduled social posts whose target execution time has arrived';

    public function handle(PostPublishingService $publishingService): int
    {
        $duePosts = Post::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = $duePosts->count();
        $this->info("Found {$count} scheduled posts due for publication.");

        foreach ($duePosts as $post) {
            try {
                $publishingService->publishScheduledPost($post);
                $this->info("Dispatched scheduled post ID: {$post->id}");
            } catch (\Throwable $e) {
                $this->error("Failed dispatching post ID {$post->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
