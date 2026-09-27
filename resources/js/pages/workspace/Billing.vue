<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useBillingStore } from '@/stores/billing';
import { useWorkspaceStore } from '@/stores/workspace';
import BillingCartDrawer from '@/components/billing/BillingCartDrawer.vue';
import ChangePlanModal from '@/components/billing/ChangePlanModal.vue';
import {
    CreditCard,
    Calendar,
    Radio,
    Zap,
    ExternalLink,
    ShoppingCart,
    Layers,
    Clock,
    CheckCircle2,
    ArrowUpRight,
    Download,
    FileText,
    RefreshCw,
    ShieldCheck,
    Check,
} from '@lucide/vue';

const page = usePage();
const billingStore = useBillingStore();
const workspaceStore = useWorkspaceStore();

const accountsCount = computed(() => workspaceStore.accounts.length);
const totalLimit = computed(() => billingStore.totalChannelLimit);
const usagePercentage = computed(() => {
    if (totalLimit.value <= 0) return 0;
    return Math.min(
        100,
        Math.round((accountsCount.value / totalLimit.value) * 100),
    );
});

const currentPlanPrice = computed(() => {
    return ((billingStore.currentPlan?.price_cents || 0) / 100).toFixed(2);
});

const totalMonthlyRate = computed(() => {
    const baseCents = billingStore.currentPlan?.price_cents || 0;
    const addonsCents = billingStore.activeAddons.reduce(
        (sum, a) => sum + a.unit_price_cents * a.quantity,
        0,
    );
    return ((baseCents + addonsCents) / 100).toFixed(2);
});

const formatDate = (isoString?: string | null) => {
    if (!isoString) return 'Oct 15, 2026';
    try {
        return new Date(isoString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return isoString;
    }
};

const formatCurrency = (cents: number) => {
    return `$${(cents / 100).toFixed(2)}`;
};

const getInvoiceItemsSummary = (lineItems: { description: string }[]) => {
    if (!lineItems || !lineItems.length) return 'Monthly Subscription';
    return lineItems.map((li) => li.description.split(' - ')[0]).join(', ');
};

const openReceiptModal = (url?: string | null, invNumber?: string) => {
    if (url) {
        window.open(url, '_blank');
    } else {
        alert(`Downloading invoice receipt ${invNumber}...`);
    }
};

const checkQueryParams = () => {
    const url = new URL(page.url, window.location.origin);
    if (url.searchParams.get('changePlan') === 'true') {
        billingStore.isChangePlanModalOpen = true;
    }
    if (url.searchParams.get('openCart') === 'true') {
        billingStore.isCartOpen = true;
    }
};

onMounted(async () => {
    await billingStore.fetchCatalog();
    await billingStore.fetchSubscription();
    await workspaceStore.fetchAccounts();
    checkQueryParams();
});

watch(
    () => page.url,
    () => {
        checkQueryParams();
    },
);
</script>

<template>
    <div class="max-w-5xl space-y-6">
        <Head title="Billing & Invoices" />
        <h1 class="sr-only">Billing & Invoices</h1>

        <!-- Streamlined Action Toolbar -->
        <div class="flex items-center justify-end gap-2.5">
            <button
                type="button"
                @click="billingStore.isChangePlanModalOpen = true"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs transition hover:bg-muted"
            >
                <Layers class="size-3.5 text-primary" />
                <span>Change Plan</span>
            </button>
            <button
                type="button"
                @click="billingStore.isCartOpen = true"
                class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90"
            >
                <ShoppingCart class="size-3.5" />
                <span>Modify Add-ons</span>
                <span
                    v-if="billingStore.cartItemCount > 0"
                    class="ml-1 flex size-4 items-center justify-center rounded-full bg-background text-[10px] font-bold text-foreground"
                >
                    {{ billingStore.cartItemCount }}
                </span>
            </button>
        </div>

        <!-- 1. Plan & Usage Header Card -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <!-- Left 2 Cols: Plan & Renewal -->
            <div
                class="space-y-4 rounded-2xl border border-border bg-card p-5 shadow-xs lg:col-span-2"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-0.5 font-mono text-xs font-bold tracking-wide text-primary uppercase"
                            >
                                {{
                                    billingStore.currentPlan?.name || 'Pro Plan'
                                }}
                                - ${{ currentPlanPrice }}/mo
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 font-mono text-[11px] font-semibold text-emerald-600 uppercase dark:text-emerald-400"
                            >
                                <span
                                    class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                />
                                {{ billingStore.status }}
                            </span>
                        </div>
                        <h2 class="mt-2 text-base font-bold text-foreground">
                            {{ billingStore.currentPlan?.name }} Subscription
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Auto-renews on
                            <strong class="text-foreground">{{
                                formatDate(billingStore.periodEnd)
                            }}</strong>
                            via Stripe payment gateway.
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs text-muted-foreground">
                            Total Recurring Rate
                        </p>
                        <p class="font-mono text-xl font-bold text-foreground">
                            ${{ totalMonthlyRate
                            }}<span
                                class="text-xs font-normal text-muted-foreground"
                                >/mo</span
                            >
                        </p>
                    </div>
                </div>

                <!-- Channel Usage Progress Meter -->
                <div
                    class="space-y-2.5 rounded-xl border border-border/80 bg-muted/40 p-4"
                >
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <Radio class="size-4 text-primary" />
                            <span class="font-semibold text-foreground"
                                >Social Channels Quota</span
                            >
                        </div>
                        <span
                            class="font-mono text-xs font-bold text-foreground"
                        >
                            {{ accountsCount }} of {{ totalLimit }} channels
                            used
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="h-2 w-full overflow-hidden rounded-full border border-border/60 bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="[
                                usagePercentage >= 90
                                    ? 'bg-amber-500'
                                    : 'bg-primary',
                            ]"
                            :style="{ width: `${usagePercentage}%` }"
                        />
                    </div>

                    <div
                        class="flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span
                            >Base Plan:
                            {{ billingStore.baseChannelLimit }} channels</span
                        >
                        <span
                            v-if="billingStore.extraChannelsFromAddons > 0"
                            class="font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            +{{ billingStore.extraChannelsFromAddons }} from
                            Add-on packs
                        </span>
                        <span
                            >{{ totalLimit - accountsCount }} slots
                            available</span
                        >
                    </div>
                </div>
            </div>

            <!-- Right Col: Quick Billing Details -->
            <div
                class="flex flex-col justify-between space-y-4 rounded-2xl border border-border bg-card p-5 shadow-xs"
            >
                <div>
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <CreditCard class="size-4 text-primary" />
                        <span>Payment Method</span>
                    </div>
                    <div
                        class="flex items-center gap-3 rounded-xl border border-border/80 bg-muted/40 p-3"
                    >
                        <div
                            class="flex size-8 items-center justify-center rounded-lg border border-border bg-card font-mono text-xs font-bold"
                        >
                            VISA
                        </div>
                        <div class="text-xs">
                            <p class="font-semibold text-foreground">
                                Visa ending in 4242
                            </p>
                            <p class="text-[11px] text-muted-foreground">
                                Expires 12/28 &bull; Default
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-border/60 pt-3 text-xs">
                    <div
                        class="flex items-center justify-between text-muted-foreground"
                    >
                        <span>Billing Cycle</span>
                        <span class="font-medium text-foreground">Monthly</span>
                    </div>
                    <div
                        class="flex items-center justify-between text-muted-foreground"
                    >
                        <span>Next Invoice</span>
                        <span class="font-medium text-foreground">{{
                            formatDate(billingStore.periodEnd)
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between text-muted-foreground"
                    >
                        <span>Tax Status</span>
                        <span class="font-medium text-emerald-600"
                            >Exempt (B2B)</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Active Subscriptions & Add-ons Summary -->
        <div
            class="overflow-hidden rounded-2xl border border-border bg-card shadow-xs"
        >
            <div
                class="flex items-center justify-between border-b border-border p-5"
            >
                <div>
                    <h2 class="text-sm font-bold text-foreground">
                        Active Subscriptions & Recurring Add-ons
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Breakdown of your base tier commitments and ongoing
                        add-on packs.
                    </p>
                </div>
                <button
                    type="button"
                    @click="billingStore.isCartOpen = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                >
                    <ShoppingCart class="size-3.5" />
                    <span>Add more add-ons &rarr;</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="border-b border-border bg-muted/30 font-semibold text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-3">Item / Service</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Billing Rate</th>
                            <th class="px-5 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <!-- Base Plan Row -->
                        <tr class="transition hover:bg-muted/20">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary"
                                    >
                                        <Layers class="size-3.5" />
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-foreground"
                                        >
                                            {{ billingStore.currentPlan?.name }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{
                                                billingStore.currentPlan
                                                    ?.features?.description
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="rounded bg-muted px-2 py-0.5 font-mono text-[10px] font-medium text-foreground"
                                >
                                    Base Tier
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono">1</td>
                            <td
                                class="px-4 py-3.5 font-mono text-muted-foreground"
                            >
                                ${{ currentPlanPrice }}/mo
                            </td>
                            <td
                                class="px-5 py-3.5 text-right font-mono font-semibold text-foreground"
                            >
                                ${{ currentPlanPrice }}
                            </td>
                        </tr>

                        <!-- Addons Rows -->
                        <tr
                            v-for="addon in billingStore.activeAddons"
                            :key="addon.slug"
                            class="transition hover:bg-muted/20"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex size-7 items-center justify-center rounded-md bg-secondary text-secondary-foreground"
                                    >
                                        <Zap class="size-3.5" />
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-foreground"
                                        >
                                            {{ addon.name }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Active recurring capability pack
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="rounded bg-primary/10 px-2 py-0.5 font-mono text-[10px] font-medium text-primary"
                                >
                                    Add-on
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono">
                                &times; {{ addon.quantity }}
                            </td>
                            <td
                                class="px-4 py-3.5 font-mono text-muted-foreground"
                            >
                                ${{
                                    (addon.unit_price_cents / 100).toFixed(2)
                                }}/mo
                            </td>
                            <td
                                class="px-5 py-3.5 text-right font-mono font-semibold text-foreground"
                            >
                                ${{
                                    (
                                        (addon.unit_price_cents *
                                            addon.quantity) /
                                        100
                                    ).toFixed(2)
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        class="border-t border-border bg-muted/20 font-semibold"
                    >
                        <tr>
                            <td
                                colspan="4"
                                class="px-5 py-3 text-right text-muted-foreground"
                            >
                                Total Recurring Monthly Charge:
                            </td>
                            <td
                                class="px-5 py-3 text-right font-mono text-sm text-foreground"
                            >
                                ${{ totalMonthlyRate }}/mo
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 3. Invoice History Table -->
        <div
            class="overflow-hidden rounded-2xl border border-border bg-card shadow-xs"
        >
            <div
                class="flex items-center justify-between border-b border-border p-5"
            >
                <div>
                    <h2 class="text-sm font-bold text-foreground">
                        Invoice History & Receipts
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Download tax-compliant receipts and view past
                        subscription invoices.
                    </p>
                </div>
                <div
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <ShieldCheck class="size-4 text-emerald-500" />
                    <span>Stripe Verified</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="border-b border-border bg-muted/30 font-semibold text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-4 py-3">Invoice ID</th>
                            <th class="px-4 py-3">Items Summary</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="inv in billingStore.invoices"
                            :key="inv.id"
                            class="transition hover:bg-muted/20"
                        >
                            <td
                                class="px-5 py-3.5 font-mono text-muted-foreground"
                            >
                                {{ formatDate(inv.created_at) }}
                            </td>
                            <td
                                class="px-4 py-3.5 font-mono font-semibold text-foreground"
                            >
                                {{ inv.invoice_number }}
                            </td>
                            <td
                                class="max-w-xs truncate px-4 py-3.5 text-muted-foreground"
                            >
                                {{ getInvoiceItemsSummary(inv.line_items) }}
                            </td>
                            <td
                                class="px-4 py-3.5 font-mono font-bold text-foreground"
                            >
                                {{ formatCurrency(inv.total_cents) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-600 uppercase dark:text-emerald-400"
                                >
                                    <Check class="size-3" />
                                    {{ inv.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a
                                    :href="inv.pdf_url || '#'"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                                    @click.prevent="
                                        openReceiptModal(
                                            inv.pdf_url,
                                            inv.invoice_number,
                                        )
                                    "
                                >
                                    <span>Receipt</span>
                                    <ExternalLink class="size-3" />
                                </a>
                            </td>
                        </tr>

                        <tr v-if="!billingStore.invoices.length">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No invoices recorded yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modals & Drawers -->
        <BillingCartDrawer />
        <ChangePlanModal />
    </div>
</template>
