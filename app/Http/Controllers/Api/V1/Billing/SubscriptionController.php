<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * GET /api/v1/billing/subscription
     */
    public function show(Request $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $details = $this->billingService->getSubscriptionDetails($tenantId);

        return response()->json($details);
    }

    /**
     * POST /api/v1/billing/change-plan
     */
    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $validated = $request->validated();
        $targetPlanId = (string) $validated['plan_id'];

        $updatedDetails = $this->billingService->changeBasePlan($tenantId, $targetPlanId);

        return response()->json([
            'success' => true,
            'message' => 'Subscription base plan updated successfully.',
            'subscription' => $updatedDetails,
        ]);
    }
}
