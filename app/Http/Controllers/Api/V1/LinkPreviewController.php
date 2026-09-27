<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LinkPreviewController extends Controller
{
    public function __construct(
        private readonly LinkPreviewService $previewService,
    ) {}

    /**
     * GET /api/v1/tools/preview-link?url=...
     */
    public function preview(Request $request): JsonResponse
    {
        $url = (string) $request->query('url', 'https://example.com');
        $preview = $this->previewService->preview($url);

        return response()->json($preview);
    }
}
