<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
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

    /**
     * GET /api/v1/organizations/{id}/members
     */
    public function members(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('tenant') ?? Organization::query()->findOrFail($id);

        $members = $this->workspaceService->getMembers($organization);

        return response()->json($members);
    }

    /**
     * POST /api/v1/organizations/{id}/members
     */
    public function inviteMember(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('tenant') ?? Organization::query()->findOrFail($id);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'string', 'in:Admin,Editor,Contributor'],
        ]);

        $member = $this->workspaceService->inviteMember(
            $organization,
            $validated['email'],
            $validated['role'] ?? 'Editor'
        );

        return response()->json($member, 201);
    }

    /**
     * DELETE /api/v1/organizations/{id}/members/{userId}
     */
    public function removeMember(Request $request, string $id, string $userId): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('tenant') ?? Organization::query()->findOrFail($id);

        $this->workspaceService->removeMember($organization, $userId);

        return response()->json(['message' => 'Member removed successfully.']);
    }
}
