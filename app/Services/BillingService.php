<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\SocialAccount;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class BillingService
{
    /**
     * @return array{plans: Collection<int, Plan>, base_plans: Collection<int, Plan>, addons: Collection<int, Plan>}
     */
    public function getPlansCatalog(): array
    {
        $allPlans = Plan::query()->active()->get();
        $basePlans = Plan::query()->active()->basePlans()->get();
        $addons = Plan::query()->active()->addons()->get();

        return [
            'plans' => $allPlans,
            'base_plans' => $basePlans,
            'addons' => $addons,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubscriptionDetails(string $tenantId): array
    {
        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->where('organization_id', $tenantId)
            ->with(['subscriptionItems.plan', 'invoices.lineItems'])
            ->first();

        if (! $subscription) {
            /** @var Plan|null $defaultPlan */
            $defaultPlan = Plan::query()->where('slug', 'plan-pro')->first()
                ?? Plan::query()->basePlans()->first();

            $subscription = Subscription::query()->create([
                'organization_id' => $tenantId,
                'provider' => 'stripe',
                'customer_id' => 'cus_'.Str::random(14),
                'subscription_id' => 'sub_'.Str::random(14),
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'cancel_at_period_end' => false,
            ]);

            if ($defaultPlan) {
                $subscription->subscriptionItems()->create([
                    'plan_id' => $defaultPlan->id,
                    'quantity' => 1,
                ]);
            }

            $subscription->load(['subscriptionItems.plan', 'invoices.lineItems']);
        }

        $basePlan = $subscription->basePlan();
        $channelsUsed = SocialAccount::query()->where('organization_id', $tenantId)->count();
        $channelsLimit = $subscription->totalChannelsAllowed();

        /** @var User|null $currentUser */
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser instanceof User && $currentUser->canBypassSubscription();

        if ($isSuperAdmin) {
            $channelsLimit = 999999;
        }

        $activeAddons = $subscription->activeAddons()->map(function (SubscriptionItem $item) {
            $plan = $item->plan;

            return [
                'id' => $item->id,
                'plan_id' => $item->plan_id,
                'slug' => $plan !== null ? $plan->slug : '',
                'name' => $plan !== null ? $plan->name : 'Addon',
                'quantity' => $item->quantity,
                'unit_price_cents' => $plan !== null ? $plan->price_cents : 0,
            ];
        });

        $invoices = $subscription->invoices()->orderByDesc('created_at')->limit(10)->get();

        $periodEnd = Carbon::parse($subscription->current_period_end)->toISOString();
        $periodStart = Carbon::parse($subscription->current_period_start)->toISOString();

        return [
            'plan' => $basePlan ? [
                'id' => $basePlan->id,
                'slug' => $basePlan->slug,
                'name' => $basePlan->name,
                'price_cents' => $basePlan->price_cents,
                'billing_interval' => $basePlan->billing_interval,
            ] : null,
            'currentPlan' => $basePlan ? [
                'id' => $basePlan->id,
                'slug' => $basePlan->slug,
                'name' => $basePlan->name,
                'price_cents' => $basePlan->price_cents,
                'billing_interval' => $basePlan->billing_interval,
            ] : null,
            'status' => $subscription->status,
            'renews_at' => $periodEnd,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'cancelAtPeriodEnd' => (bool) $subscription->cancel_at_period_end,
            'channels_limit' => $channelsLimit,
            'channels_used' => $channelsUsed,
            'is_bypassed' => $isSuperAdmin,
            'can_bypass_subscription' => $isSuperAdmin,
            'addons' => $activeAddons,
            'activeAddons' => $activeAddons,
            'invoices' => $invoices,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function changeBasePlan(string $tenantId, string $targetPlanId): array
    {
        /** @var Plan $newPlan */
        $newPlan = Plan::query()
            ->where(function ($q) use ($targetPlanId) {
                $q->where('id', $targetPlanId)->orWhere('slug', $targetPlanId);
            })
            ->where('type', 'base_plan')
            ->firstOrFail();

        /** @var Subscription $subscription */
        $subscription = Subscription::query()
            ->where('organization_id', $tenantId)
            ->firstOrFail();

        // Remove old base plan item
        $oldBaseItems = $subscription->subscriptionItems()
            ->whereHas('plan', fn ($q) => $q->where('type', 'base_plan'))
            ->get();

        foreach ($oldBaseItems as $item) {
            $item->delete();
        }

        // Add new base plan item
        $subscription->subscriptionItems()->create([
            'plan_id' => $newPlan->id,
            'quantity' => 1,
        ]);

        // Generate paid invoice for the plan change
        $invoiceNumber = 'INV-'.date('Y').'-'.strtoupper(Str::random(6));

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->create([
            'organization_id' => $tenantId,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'external_invoice_id' => 'in_'.Str::random(16),
            'subtotal_cents' => $newPlan->price_cents,
            'total_cents' => $newPlan->price_cents,
            'currency' => 'USD',
            'status' => 'paid',
            'pdf_url' => "https://example.com/invoices/{$invoiceNumber}.pdf",
            'paid_at' => now(),
        ]);

        $invoice->invoiceLineItems()->create([
            'plan_id' => $newPlan->id,
            'description' => "{$newPlan->name} - Monthly Subscription",
            'quantity' => 1,
            'unit_price_cents' => $newPlan->price_cents,
            'amount_cents' => $newPlan->price_cents,
        ]);

        return $this->getSubscriptionDetails($tenantId);
    }

    /**
     * @param  list<array{plan_id: string, quantity: int}>  $items
     * @return array{subscription: array<string, mixed>, invoice: Invoice}
     */
    public function checkoutCart(string $tenantId, array $items): array
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::query()
            ->where('organization_id', $tenantId)
            ->firstOrFail();

        $invNumber = 'INV-'.date('Y').'-'.strtoupper(Str::random(6));

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->create([
            'organization_id' => $tenantId,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invNumber,
            'external_invoice_id' => 'in_'.Str::random(16),
            'subtotal_cents' => 0,
            'total_cents' => 0,
            'currency' => 'USD',
            'status' => 'paid',
            'pdf_url' => "https://example.com/invoices/{$invNumber}.pdf",
            'paid_at' => now(),
        ]);

        $runningTotalCents = 0;

        foreach ($items as $item) {
            /** @var Plan $addonPlan */
            $addonPlan = Plan::query()
                ->where(function ($q) use ($item) {
                    $q->where('id', $item['plan_id'])->orWhere('slug', $item['plan_id']);
                })
                ->firstOrFail();

            $qty = (int) $item['quantity'];
            $unitPrice = (int) $addonPlan->price_cents;
            $lineAmount = $unitPrice * $qty;
            $runningTotalCents += $lineAmount;

            /** @var SubscriptionItem|null $existingSubItem */
            $existingSubItem = $subscription->subscriptionItems()
                ->where('plan_id', $addonPlan->id)
                ->first();

            if ($existingSubItem) {
                $existingSubItem->increment('quantity', $qty);
            } else {
                $subscription->subscriptionItems()->create([
                    'plan_id' => $addonPlan->id,
                    'quantity' => $qty,
                ]);
            }

            $invoice->invoiceLineItems()->create([
                'plan_id' => $addonPlan->id,
                'description' => "{$addonPlan->name} (x{$qty})",
                'quantity' => $qty,
                'unit_price_cents' => $unitPrice,
                'amount_cents' => $lineAmount,
            ]);
        }

        $invoice->update([
            'subtotal_cents' => $runningTotalCents,
            'total_cents' => $runningTotalCents,
        ]);

        $updatedSub = $this->getSubscriptionDetails($tenantId);

        return [
            'subscription' => $updatedSub,
            'invoice' => $invoice->load('lineItems'),
        ];
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(string $tenantId): Collection
    {
        return Invoice::query()
            ->where('organization_id', $tenantId)
            ->with('invoiceLineItems')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array{invoice_id: string, invoice_number: string, pdf_url: string, download_url: string}
     */
    public function getInvoiceDownload(string $tenantId, string $invoiceId): array
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::query()
            ->where('organization_id', $tenantId)
            ->where('id', $invoiceId)
            ->firstOrFail();

        $url = $invoice->pdf_url ?? "https://example.com/invoices/{$invoice->invoice_number}.pdf";

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'pdf_url' => $url,
            'download_url' => $url,
        ];
    }
}
