<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;

final class BillingCatalogController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * GET /api/v1/billing/plans
     */
    public function index(): JsonResponse
    {
        $catalog = $this->billingService->getPlansCatalog();

        return response()->json($catalog);
    }
}
