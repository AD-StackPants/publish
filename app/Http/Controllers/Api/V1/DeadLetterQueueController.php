<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DeadLetterQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeadLetterQueueController extends Controller
{
    public function __construct(
        private readonly DeadLetterQueueService $dlqService,
    ) {}

    /**
     * POST /api/v1/dlq/{service_name}/{message_id}/replay
     */
    public function replayServiceMessage(Request $request, string $service_name, string $message_id): JsonResponse
    {
        return $this->replay($request, $message_id);
    }

    /**
     * POST /api/v1/dlq/{id}/replay
     */
    public function replay(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $replayed = $this->dlqService->replay($tenantId, $id);

        return response()->json([
            'success' => $replayed,
            'message' => $replayed ? 'Job re-enqueued from DLQ' : 'No matching DLQ record found.',
        ]);
    }
}
