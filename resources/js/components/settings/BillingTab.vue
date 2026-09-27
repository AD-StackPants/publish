<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useBillingStore } from '@/stores/billing';
import { useWorkspaceStore } from '@/stores/workspace';
import ChangePlanModal from '@/components/billing/ChangePlanModal.vue';
import BillingCartDrawer from '@/components/billing/BillingCartDrawer.vue';
import {
    CreditCard,
    Zap,
    Download,
    Layers,
    ShoppingCart,
    Check,
    Calendar,
    ArrowUpRight,
    ShieldCheck,
    Radio,
    Clock,
    Sparkles,
} from '@lucide/vue';

const billingStore = useBillingStore();
const workspaceStore = useWorkspaceStore();

onMounted(async () => {
    await billingStore.fetchCatalog();
    await billingStore.fetchSubscription();
    await workspaceStore.fetchAccounts();
});

const currentPlan = computed(() => billingStore.currentPlan);
const activeAddons = computed(() => billingStore.activeAddons);
const invoices = computed(() => billingStore.invoices);

const accountsCount = computed(() => workspaceStore.accounts.length);
const totalChannelLimit = computed(() => billingStore.totalChannelLimit);

const channelUsagePercent = computed(() => {
    if (totalChannelLimit.value <= 0) return 0;
    return Math.min(
        100,
        Math.round((accountsCount.value / totalChannelLimit.value) * 100),
    );
});

const formattedPrice = computed(() => {
    const cents = currentPlan.value?.price_cents || 4900;
    return `$${(cents / 100).toFixed(2)}`;
});

const formatCurrency = (cents: number) => {
    return `$${(cents / 100).toFixed(2)}`;
};

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

const handleDownloadInvoice = (
    invoiceId: string,
    invNumber: string,
    pdfUrl?: string | null,
) => {
    if (pdfUrl) {
        window.open(pdfUrl, '_blank');
    } else {
        alert(`Downloading receipt for invoice ${invNumber}...`);
    }
};
</script>

<template>
    <div class="space-y-8">
        <!-- 1. Current Subscription Plan Card -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div
                class="flex flex-col gap-4 border-b border-border pb-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <CreditCard class="h-4 w-4 text-primary" />
                        <h2 class="text-sm font-bold text-foreground">
                            Active Subscription Plan
                        </h2>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Manage your tenant subscription tier, billing period,
                        and capacity allocations.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 font-mono text-xs font-semibold text-emerald-600 uppercase dark:text-emerald-400"
                    >
                        {{ billingStore.status || 'Active' }}
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Plan Name & Price -->
                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/30 p-4"
                >
                    <span class="text-xs text-muted-foreground"
                        >Current Plan</span
                    >
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-lg font-bold text-foreground">
                            {{ currentPlan?.name || 'Growth Pro' }}
                        </h3>
                        <span class="text-xs font-semibold text-foreground">
                            {{ formattedPrice }} / month
                        </span>
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Includes multi-account unified publishing & analytics.
                    </p>
                </div>

                <!-- Renewal Date -->
                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/30 p-4"
                >
                    <span class="text-xs text-muted-foreground"
                        >Next Renewal Cycle</span
                    >
                    <p class="text-lg font-bold text-foreground">
                        {{ formatDate(billingStore.periodEnd) }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Automatic renewal via payment method on file.
                    </p>
                </div>

                <!-- Plan Actions -->
                <div
                    class="flex flex-col justify-center gap-2 rounded-lg border border-border/80 bg-muted/30 p-4"
                >
                    <button
                        type="button"
                        @click="billingStore.isChangePlanModalOpen = true"
                        class="inline-flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90"
                    >
                        <Zap class="h-3.5 w-3.5" />
                        <span>Change Plan Tier</span>
                    </button>
                    <button
                        type="button"
                        @click="billingStore.isCartOpen = true"
                        class="inline-flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-border bg-background px-3 py-2 text-xs font-semibold text-foreground shadow-xs transition hover:bg-accent"
                    >
                        <ShoppingCart class="h-3.5 w-3.5 text-primary" />
                        <span>Add-ons Store</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- 2. Resource Utilization & Capacity Meters -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <Layers class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Resource Entitlements & Capacity
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Real-time tracking of connected accounts and seats
                        allocated to this tenant.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Connected Social Channels Meter -->
                <div
                    class="space-y-3 rounded-lg border border-border/80 bg-muted/20 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Radio class="h-4 w-4 text-primary" />
                            <span class="text-xs font-semibold text-foreground"
                                >Connected Channels</span
                            >
                        </div>
                        <span
                            class="font-mono text-xs font-bold text-foreground"
                        >
                            {{ accountsCount }} / {{ totalChannelLimit }} Used
                        </span>
                    </div>

                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="
                                channelUsagePercent >= 90
                                    ? 'bg-amber-500'
                                    : 'bg-primary'
                            "
                            :style="{ width: `${channelUsagePercent}%` }"
                        />
                    </div>

                    <div
                        class="flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span
                            >{{ totalChannelLimit - accountsCount }} channels
                            remaining</span
                        >
                        <button
                            type="button"
                            @click="billingStore.isCartOpen = true"
                            class="cursor-pointer font-semibold text-primary hover:underline"
                        >
                            + Add Channel Pack
                        </button>
                    </div>
                </div>

                <!-- Priority Dispatch Engine -->
                <div
                    class="space-y-3 rounded-lg border border-border/80 bg-muted/20 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Clock class="h-4 w-4 text-emerald-500" />
                            <span class="text-xs font-semibold text-foreground"
                                >Automated Queue Runner</span
                            >
                        </div>
                        <span
                            class="rounded bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-600 uppercase"
                        >
                            Dedicated
                        </span>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Your plan runs high-throughput publishing workers with
                        zero queue delays and automatic rate limit management.
                    </p>

                    <div
                        class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-400"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>SLA: 99.9% On-Time Scheduled Dispatch</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Active Add-ons -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div
                class="flex items-center justify-between border-b border-border pb-4"
            >
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-primary" />
                        <h2 class="text-sm font-bold text-foreground">
                            Active Add-on Subscriptions
                        </h2>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Specialized modules and capacity expansions attached to
                        this workspace.
                    </p>
                </div>

                <button
                    type="button"
                    @click="billingStore.isCartOpen = true"
                    class="cursor-pointer text-xs font-semibold text-primary hover:underline"
                >
                    Browse Catalog &rarr;
                </button>
            </div>

            <div
                v-if="activeAddons.length > 0"
                class="mt-4 divide-y divide-border overflow-hidden rounded-lg border border-border"
            >
                <div
                    v-for="addon in activeAddons"
                    :key="addon.plan_id"
                    class="flex items-center justify-between bg-background p-3.5 transition hover:bg-muted/30"
                >
                    <div class="space-y-0.5">
                        <span class="text-xs font-semibold text-foreground">{{
                            addon.name
                        }}</span>
                        <span class="block text-[11px] text-muted-foreground"
                            >Quantity: {{ addon.quantity }}</span
                        >
                    </div>
                    <div class="text-right">
                        <span
                            class="font-mono text-xs font-bold text-foreground"
                        >
                            {{
                                formatCurrency(
                                    addon.unit_price_cents * addon.quantity,
                                )
                            }}
                            / mo
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="mt-4 rounded-lg border border-dashed border-border p-6 text-center text-xs text-muted-foreground"
            >
                No active add-ons. Need additional channel connections or
                priority runners?
                <button
                    type="button"
                    @click="billingStore.isCartOpen = true"
                    class="ml-1 cursor-pointer font-semibold text-primary underline"
                >
                    Open Add-ons Store
                </button>
            </div>
        </section>

        <!-- 4. Billing History & Invoices -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div
                class="flex items-center justify-between border-b border-border pb-4"
            >
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Billing History & Invoices
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Download official tax receipts and review historical
                        subscription invoices.
                    </p>
                </div>
            </div>

            <div
                class="mt-4 divide-y divide-border overflow-hidden rounded-lg border border-border"
            >
                <div
                    v-for="inv in invoices"
                    :key="inv.id"
                    class="flex items-center justify-between bg-background p-3.5 transition hover:bg-muted/30"
                >
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-xs font-semibold text-foreground"
                                >{{ inv.invoice_number }}</span
                            >
                            <span
                                class="rounded bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-emerald-600 uppercase"
                            >
                                {{ inv.status }}
                            </span>
                        </div>
                        <span class="text-[11px] text-muted-foreground">{{
                            formatDate(inv.created_at)
                        }}</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <span
                            class="font-mono text-xs font-bold text-foreground"
                        >
                            {{ formatCurrency(inv.total_cents) }}
                        </span>
                        <button
                            type="button"
                            @click="
                                handleDownloadInvoice(
                                    inv.id,
                                    inv.invoice_number,
                                    inv.pdf_url,
                                )
                            "
                            class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-border bg-muted/40 px-2 py-1 text-[11px] font-medium text-foreground transition hover:bg-accent"
                            title="Download PDF Receipt"
                        >
                            <Download class="h-3 w-3" />
                            <span class="hidden sm:inline">Receipt</span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="invoices.length === 0"
                    class="p-4 text-center text-xs text-muted-foreground"
                >
                    No invoices generated yet for this billing cycle.
                </div>
            </div>
        </section>

        <!-- Embedded Modals -->
        <ChangePlanModal />
        <BillingCartDrawer />
    </div>
</template>
