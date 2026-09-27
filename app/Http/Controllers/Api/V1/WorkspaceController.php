<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {}

    /**
     * GET /api/v1/user/organizations
     */
    public function userOrganizations(): JsonResponse
    {
        $organizations = $this->workspaceService->getUserOrganizations();

        return response()->json($organizations);
    }

    /**
     * PATCH /api/v1/organizations/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:100'],
        ]);

        $organization = $this->workspaceService->updateOrganization($id, $validated);

        return response()->json($organization);
    }
}
