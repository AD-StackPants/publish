<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Services\PostPublishingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostPublishingService $publishingService,
    ) {}

    /**
     * GET /api/v1/posts
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');

        $status = $request->query('status');
        $search = $request->query('search');
        $platform = $request->query('platform');

        $posts = $this->publishingService->getPostsForTenant(
            tenantId: $tenantId,
            status: is_string($status) ? $status : null,
            search: is_string($search) ? $search : null,
            platform: is_string($platform) ? $platform : null,
        );

        return response()->json($posts);
    }

    /**
     * POST /api/v1/social/posts
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $validated = $request->validated();

        $result = $this->publishingService->createAndPublishPost($tenantId, $validated);

        $status = $result['idempotent_replay'] ? Response::HTTP_OK : Response::HTTP_CREATED;

        return response()->json([
            'job_id' => $result['job_id'],
            'post' => $result['post'],
            'idempotent_replay' => $result['idempotent_replay'],
            'message' => $result['idempotent_replay']
                ? 'Returning previously ingested post under existing idempotency key.'
                : 'Post created and processed.',
        ], $status);
    }

    /**
     * GET /api/v1/jobs/{job_id}
     */
    public function jobStatus(Request $request, string $job_id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $status = $this->publishingService->getJobStatus($tenantId, $job_id);

        return response()->json($status);
    }

    /**
     * PATCH /api/v1/posts/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');

        $validated = $request->validate([
            'content' => ['sometimes', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'platform_overrides' => ['nullable', 'array'],
            'media_url' => ['nullable', 'url'],
            'status' => ['sometimes', 'string'],
        ]);

        $post = $this->publishingService->updatePost($tenantId, $id, $validated);

        return response()->json($post);
    }

    /**
     * DELETE /api/v1/posts/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $this->publishingService->deletePost($tenantId, $id);

        return response()->json(['success' => true, 'message' => 'Post removed']);
    }

    /**
     * POST /api/v1/posts/{id}/retry-failed
     */
    public function retryFailed(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $post = $this->publishingService->retryFailedPost($tenantId, $id);

        return response()->json($post);
    }
}
