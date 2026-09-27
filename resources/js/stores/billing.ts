import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apiClient } from '../api/client';
import type {
    Plan,
    SubscriptionDetails,
    ActiveAddon,
    CartItem,
    Invoice,
} from '../types/billing';

export const useBillingStore = defineStore('billing', () => {
    // Plans catalog
    const plans = ref<Plan[]>([]);
    const basePlans = computed(() =>
        plans.value.filter((p) => p.type === 'base_plan'),
    );
    const addonPlans = computed(() =>
        plans.value.filter((p) => p.type === 'addon'),
    );

    // Active subscription state
    const currentPlan = ref<Plan | null>(null);
    const status = ref<'active' | 'past_due' | 'canceled' | 'trialing' | null>(
        null,
    );
    const periodStart = ref<string | null>(null);
    const periodEnd = ref<string | null>(null);
    const cancelAtPeriodEnd = ref<boolean>(false);
    const activeAddons = ref<ActiveAddon[]>([]);
    const invoices = ref<Invoice[]>([]);
    const isBypassed = ref<boolean>(false);

    // Multi-item interactive Cart
    const cart = ref<CartItem[]>([]);
    const isCartOpen = ref(false);
    const isUpgradeModalOpen = ref(false);
    const isChangePlanModalOpen = ref(false);

    // Loading / error states
    const isLoading = ref(false);
    const isCheckingOut = ref(false);
    const error = ref<string | null>(null);

    // Entitlement calculations
    const baseChannelLimit = computed(() => {
        if (isBypassed.value) {
            return 999999;
        }
        return (currentPlan.value?.features?.channels_limit as number) ?? 5;
    });

    const extraChannelsFromAddons = computed(() => {
        return activeAddons.value
            .filter((a) => a.slug === 'addon-extra-channels')
            .reduce((total, a) => total + a.quantity * 3, 0);
    });

    const totalChannelLimit = computed(() => {
        if (isBypassed.value) {
            return 999999;
        }
        return baseChannelLimit.value + extraChannelsFromAddons.value;
    });

    const canAddChannel = (currentCount: number): boolean => {
        if (isBypassed.value) {
            return true;
        }
        return currentCount < totalChannelLimit.value;
    };

    // Cart calculations
    const cartSubtotalCents = computed(() => {
        return cart.value.reduce(
            (sum, item) => sum + item.unit_price_cents * item.quantity,
            0,
        );
    });

    const cartItemCount = computed(() => {
        return cart.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    // Cart actions
    const addToCart = (addon: Plan, qty = 1) => {
        const existing = cart.value.find(
            (item) => item.plan_id === addon.id || item.slug === addon.slug,
        );
        if (existing) {
            existing.quantity += qty;
        } else {
            cart.value.push({
                plan_id: addon.id,
                slug: addon.slug,
                name: addon.name,
                unit_price_cents: addon.price_cents,
                quantity: qty,
            });
        }
        isCartOpen.value = true;
    };

    const updateCartQuantity = (planIdOrSlug: string, quantity: number) => {
        if (quantity <= 0) {
            removeFromCart(planIdOrSlug);
            return;
        }
        const existing = cart.value.find(
            (item) =>
                item.plan_id === planIdOrSlug || item.slug === planIdOrSlug,
        );
        if (existing) {
            existing.quantity = quantity;
        }
    };

    const removeFromCart = (planIdOrSlug: string) => {
        cart.value = cart.value.filter(
            (item) =>
                item.plan_id !== planIdOrSlug && item.slug !== planIdOrSlug,
        );
    };

    const clearCart = () => {
        cart.value = [];
    };

    // Remote / Mock API fetch actions
    const fetchCatalog = async () => {
        try {
            const res = await apiClient.get('/billing/plans');
            if (res.data?.plans) {
                plans.value = res.data.plans;
            }
        } catch (e) {
            console.warn('Using fallback catalog data', e);
        }
    };

    const fetchSubscription = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await apiClient.get<SubscriptionDetails>(
                '/billing/subscription',
            );
            if (res.data) {
                currentPlan.value = res.data.currentPlan;
                status.value = res.data.status;
                periodStart.value = res.data.periodStart;
                periodEnd.value = res.data.periodEnd;
                cancelAtPeriodEnd.value = res.data.cancelAtPeriodEnd;
                activeAddons.value = res.data.activeAddons || [];
                invoices.value = res.data.invoices || [];
                isBypassed.value = Boolean(
                    res.data.is_bypassed || res.data.can_bypass_subscription,
                );
            }
        } catch (e: any) {
            error.value = e?.message || 'Failed to fetch subscription';
        } finally {
            isLoading.value = false;
        }
    };

    const changePlan = async (planIdOrSlug: string) => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await apiClient.post('/billing/change-plan', {
                plan_id: planIdOrSlug,
            });
            if (res.data?.subscription) {
                currentPlan.value = res.data.subscription.currentPlan;
                status.value = res.data.subscription.status;
                periodEnd.value = res.data.subscription.periodEnd;
                invoices.value = res.data.subscription.invoices || [];
            }
            return res.data;
        } catch (e: any) {
            error.value = e?.response?.data?.message || 'Failed to change plan';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const checkoutCart = async () => {
        if (!cart.value.length) return;
        isCheckingOut.value = true;
        error.value = null;
        try {
            const res = await apiClient.post('/billing/cart/checkout', {
                items: cart.value,
            });
            if (res.data?.subscription) {
                activeAddons.value = res.data.subscription.activeAddons || [];
                invoices.value = res.data.subscription.invoices || [];
            }
            clearCart();
            isCartOpen.value = false;
            return res.data;
        } catch (e: any) {
            error.value =
                e?.response?.data?.message || 'Failed to checkout cart';
            throw e;
        } finally {
            isCheckingOut.value = false;
        }
    };

    return {
        // State
        plans,
        basePlans,
        addonPlans,
        currentPlan,
        status,
        periodStart,
        periodEnd,
        cancelAtPeriodEnd,
        activeAddons,
        invoices,
        isBypassed,
        cart,
        isCartOpen,
        isUpgradeModalOpen,
        isChangePlanModalOpen,
        isLoading,
        isCheckingOut,
        error,

        // Computed
        baseChannelLimit,
        extraChannelsFromAddons,
        totalChannelLimit,
        cartSubtotalCents,
        cartItemCount,

        // Methods
        canAddChannel,
        addToCart,
        updateCartQuantity,
        removeFromCart,
        clearCart,
        fetchCatalog,
        fetchSubscription,
        changePlan,
        checkoutCart,
    };
});
