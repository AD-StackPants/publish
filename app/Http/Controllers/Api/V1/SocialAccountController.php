<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectSocialAccountRequest;
use App\Services\SocialAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SocialAccountController extends Controller
{
    public function __construct(
        private readonly SocialAccountService $accountService,
    ) {}

    /**
     * GET /api/v1/accounts
     *
     * List all connected social accounts scoped to the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $accounts = $this->accountService->getAccountsForTenant($tenantId);

        return response()->json($accounts);
    }

    /**
     * POST /api/v1/accounts
     *
     * Connect a new social account to the tenant workspace.
     */
    public function store(ConnectSocialAccountRequest $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $validated = $request->validated();

        $account = $this->accountService->connectAccount($tenantId, $validated);

        return response()->json($account, Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/v1/accounts/{id}
     *
     * Disconnect (remove) a social account from the tenant workspace.
     */
    public function destroy(Request $request, string $id): Response
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $this->accountService->disconnectAccount($tenantId, $id);

        return response()->noContent();
    }

    /**
     * POST /api/v1/accounts/{id}/reconnect
     *
     * Re-authenticate / restore health status of an existing social account.
     */
    public function reconnect(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $account = $this->accountService->reconnectAccount($tenantId, $id);

        return response()->json($account);
    }
}
