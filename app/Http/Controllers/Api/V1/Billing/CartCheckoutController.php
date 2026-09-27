<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutCartRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CartCheckoutController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * POST /api/v1/billing/cart/checkout
     */
    public function checkout(CheckoutCartRequest $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $validated = $request->validated();
        /** @var list<array{plan_id: string, quantity: int}> $items */
        $items = $validated['items'];

        $result = $this->billingService->checkoutCart($tenantId, $items);

        return response()->json([
            'success' => true,
            'message' => 'Add-on order checked out successfully.',
            'subscription' => $result['subscription'],
            'invoice' => $result['invoice'],
        ], Response::HTTP_OK);
    }
}
