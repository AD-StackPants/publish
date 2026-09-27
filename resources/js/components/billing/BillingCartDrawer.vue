<script setup lang="ts">
import { computed } from 'vue';
import { useBillingStore } from '../../stores/billing';
import type { Plan } from '../../types/billing';
import {
    ShoppingCart,
    X,
    Plus,
    Minus,
    Trash2,
    Zap,
    Radio,
    Globe,
    Check,
    Loader2,
    ArrowRight,
    CreditCard,
} from '@lucide/vue';

const billingStore = useBillingStore();

const isOpen = computed({
    get: () => billingStore.isCartOpen,
    set: (val: boolean) => {
        billingStore.isCartOpen = val;
    },
});

const addonPlans = computed(() => billingStore.addonPlans);
const cart = computed(() => billingStore.cart);
const cartSubtotal = computed(() =>
    (billingStore.cartSubtotalCents / 100).toFixed(2),
);

const getAddonIcon = (slug: string) => {
    switch (slug) {
        case 'addon-extra-channels':
            return Radio;
        case 'addon-priority-worker':
            return Zap;
        case 'addon-custom-domain':
            return Globe;
        default:
            return CreditCard;
    }
};

const getCartQuantity = (addon: Plan): number => {
    const item = cart.value.find(
        (i) => i.plan_id === addon.id || i.slug === addon.slug,
    );
    return item ? item.quantity : 0;
};

const handleIncrement = (addon: Plan) => {
    billingStore.addToCart(addon, 1);
};

const handleDecrement = (addon: Plan) => {
    const qty = getCartQuantity(addon);
    if (qty > 1) {
        billingStore.updateCartQuantity(addon.id, qty - 1);
    } else {
        billingStore.removeFromCart(addon.id);
    }
};

const handleCheckout = async () => {
    try {
        await billingStore.checkoutCart();
    } catch {
        // error handled in store
    }
};
</script>

<template>
    <!-- Slide-over Drawer Backdrop -->
    <Transition
        enter-active-class="transition-opacity ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs"
            @click="isOpen = false"
        />
    </Transition>

    <!-- Slide-over Drawer Panel -->
    <Transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transform transition ease-in duration-200"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <aside
            v-if="isOpen"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-border bg-background shadow-2xl"
        >
            <!-- Header -->
            <header
                class="flex items-center justify-between border-b border-border px-5 py-4"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <ShoppingCart class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-foreground">
                            Add-on Selection & Cart
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Expand capabilities without changing your base tier.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="isOpen = false"
                    class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                >
                    <X class="size-4" />
                </button>
            </header>

            <!-- Error Banner -->
            <div
                v-if="billingStore.error"
                class="mx-5 mt-4 rounded-md border border-destructive/20 bg-destructive/10 p-3 text-xs text-destructive"
            >
                {{ billingStore.error }}
            </div>

            <!-- Scrollable Content: Add-on Catalog -->
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <div class="flex items-center justify-between">
                    <h3
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Available Add-ons
                    </h3>
                    <span class="text-xs text-muted-foreground"
                        >Billed monthly with prorated adjustment</span
                    >
                </div>

                <!-- Addon List -->
                <div class="space-y-3">
                    <div
                        v-for="addon in addonPlans"
                        :key="addon.id"
                        class="rounded-xl border border-border bg-card p-4 shadow-xs transition hover:border-primary/50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-primary"
                                >
                                    <component
                                        :is="getAddonIcon(addon.slug)"
                                        class="size-4"
                                    />
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ addon.name }}
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
                                    >
                                        {{ addon.features?.description }}
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            class="font-mono text-sm font-bold text-foreground"
                                        >
                                            ${{
                                                (
                                                    addon.price_cents / 100
                                                ).toFixed(2)
                                            }}
                                        </span>
                                        <span
                                            class="text-[11px] text-muted-foreground"
                                            >/ month</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add to Cart or Stepper -->
                        <div
                            class="mt-3.5 flex items-center justify-end border-t border-border/60 pt-3"
                        >
                            <div
                                v-if="getCartQuantity(addon) > 0"
                                class="flex items-center gap-2"
                            >
                                <span class="mr-1 text-xs text-muted-foreground"
                                    >In Cart:</span
                                >
                                <div
                                    class="flex items-center rounded-lg border border-border bg-muted/60 p-0.5"
                                >
                                    <button
                                        type="button"
                                        @click="handleDecrement(addon)"
                                        class="flex size-7 items-center justify-center rounded-md text-foreground transition hover:bg-card"
                                    >
                                        <Minus class="size-3.5" />
                                    </button>
                                    <span
                                        class="w-8 text-center font-mono text-xs font-bold text-foreground"
                                    >
                                        {{ getCartQuantity(addon) }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="handleIncrement(addon)"
                                        class="flex size-7 items-center justify-center rounded-md text-foreground transition hover:bg-card"
                                    >
                                        <Plus class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="handleIncrement(addon)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground transition hover:bg-secondary/80"
                            >
                                <Plus class="size-3.5" />
                                <span>Add to Cart</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Addons in Subscription note -->
                <div
                    v-if="billingStore.activeAddons.length > 0"
                    class="rounded-lg border border-border/80 bg-muted/30 p-3 text-xs text-muted-foreground"
                >
                    <p class="mb-1 font-semibold text-foreground">
                        Currently Active on Workspace:
                    </p>
                    <ul class="space-y-1 font-mono text-[11px]">
                        <li
                            v-for="act in billingStore.activeAddons"
                            :key="act.slug"
                            class="flex items-center justify-between"
                        >
                            <span
                                >{{ act.name }} &times; {{ act.quantity }}</span
                            >
                            <span
                                >+${{
                                    (
                                        (act.unit_price_cents * act.quantity) /
                                        100
                                    ).toFixed(2)
                                }}/mo</span
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Cart Summary Panel (Bottom) -->
            <footer class="space-y-4 border-t border-border bg-card/60 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted-foreground"
                        >Order Summary</span
                    >
                    <button
                        v-if="cart.length > 0"
                        type="button"
                        @click="billingStore.clearCart()"
                        class="text-xs text-muted-foreground transition hover:text-destructive"
                    >
                        Clear Cart
                    </button>
                </div>

                <!-- Cart Line Items -->
                <div
                    v-if="cart.length > 0"
                    class="max-h-40 space-y-2 overflow-y-auto pr-1"
                >
                    <div
                        v-for="item in cart"
                        :key="item.slug"
                        class="flex items-center justify-between border-b border-border/40 py-1 text-xs last:border-0"
                    >
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="billingStore.removeFromCart(item.slug)"
                                class="text-muted-foreground hover:text-destructive"
                            >
                                <Trash2 class="size-3.5" />
                            </button>
                            <span class="font-medium text-foreground">{{
                                item.name
                            }}</span>
                            <span class="font-mono text-muted-foreground"
                                >&times; {{ item.quantity }}</span
                            >
                        </div>
                        <span class="font-mono font-semibold text-foreground">
                            ${{
                                (
                                    (item.unit_price_cents * item.quantity) /
                                    100
                                ).toFixed(2)
                            }}
                        </span>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-dashed border-border p-4 text-center text-xs text-muted-foreground"
                >
                    No add-ons in cart yet. Select items from the catalog above.
                </div>

                <!-- Subtotal Calculator -->
                <div
                    class="flex items-center justify-between border-t border-border pt-3"
                >
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Immediate Monthly Adjustment
                        </p>
                        <p class="text-[11px] text-muted-foreground/80">
                            Renews with base plan
                        </p>
                    </div>
                    <div class="text-right">
                        <span
                            class="font-mono text-lg font-bold text-foreground"
                            >+${{ cartSubtotal }}</span
                        >
                        <span class="text-xs text-muted-foreground"> /mo</span>
                    </div>
                </div>

                <!-- Apply & Checkout Button -->
                <button
                    type="button"
                    @click="handleCheckout"
                    :disabled="cart.length === 0 || billingStore.isCheckingOut"
                    class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Loader2
                        v-if="billingStore.isCheckingOut"
                        class="size-4 animate-spin"
                    />
                    <span v-else class="flex items-center gap-1.5">
                        Apply & Checkout
                        <ArrowRight class="size-4" />
                    </span>
                </button>
            </footer>
        </aside>
    </Transition>
</template>
