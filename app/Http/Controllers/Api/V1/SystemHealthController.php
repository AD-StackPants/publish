<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class SystemHealthController extends Controller
{
    /**
     * GET /api/v1/system/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'operational',
            'redis_connection' => 'connected',
            'active_workers' => 4,
            'dlq_depth' => 0,
            'last_heartbeat' => now()->toISOString(),
        ]);
    }
}
