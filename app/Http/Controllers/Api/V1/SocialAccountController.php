<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $accounts = $this->accountService->getAccountsForTenant($tenantId);

        return response()->json($accounts);
    }

    /**
     * POST /api/v1/accounts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:linkedin,facebook,twitter'],
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'url'],
            'access_token' => ['nullable', 'string'],
        ]);

        $tenantId = (string) $request->attributes->get('tenant_id');
        $account = $this->accountService->connectAccount($tenantId, $validated);

        return response()->json($account, Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/v1/accounts/{id}
     */
    public function destroy(Request $request, string $id): Response
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $this->accountService->disconnectAccount($tenantId, $id);

        return response()->noContent();
    }

    /**
     * POST /api/v1/accounts/{id}/reconnect
     */
    public function reconnect(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $account = $this->accountService->reconnectAccount($tenantId, $id);

        return response()->json($account);
    }
}
