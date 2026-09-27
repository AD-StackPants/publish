<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * GET /api/v1/billing/invoices
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $invoices = $this->billingService->getInvoices($tenantId);

        return response()->json($invoices);
    }

    /**
     * GET /api/v1/billing/invoices/{id}/download
     */
    public function download(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->attributes->get('tenant_id');
        $downloadInfo = $this->billingService->getInvoiceDownload($tenantId, $id);

        return response()->json($downloadInfo);
    }
}
