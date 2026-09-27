<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useBillingStore } from '@/stores/billing';
import { useWorkspaceStore } from '@/stores/workspace';
import {
    Sparkles,
    X,
    Radio,
    Zap,
    Check,
    ArrowUpRight,
    Plus,
} from '@lucide/vue';

const billingStore = useBillingStore();
const workspaceStore = useWorkspaceStore();

const isOpen = computed({
    get: () => billingStore.isUpgradeModalOpen,
    set: (val: boolean) => {
        billingStore.isUpgradeModalOpen = val;
    },
});

const currentSlug = computed(() => {
    return workspaceStore.activeOrgSlug || 'acme-studio';
});

const currentPlanName = computed(
    () => billingStore.currentPlan?.name || 'Pro Plan',
);
const limit = computed(() => billingStore.totalChannelLimit);

const handleUpgradeBasePlan = () => {
    isOpen.value = false;
    router.visit(
        `/w/${currentSlug.value}/settings?tab=billing&changePlan=true`,
    );
};

const handleAddExtraChannelsPack = () => {
    isOpen.value = false;
    const extraChannelsAddon = billingStore.addonPlans.find(
        (a) => a.slug === 'addon-extra-channels',
    );
    if (extraChannelsAddon) {
        billingStore.addToCart(extraChannelsAddon, 1);
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition-opacity ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md animate-in space-y-5 rounded-2xl border border-border bg-card p-6 shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                    >
                        <Radio class="size-5" />
                    </div>
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-foreground">
                        Channel Limit Reached
                    </h3>
                    <p
                        class="mt-1 text-sm leading-relaxed text-muted-foreground"
                    >
                        You've reached your limit of
                        <strong class="text-foreground"
                            >{{ limit }} channels</strong
                        >
                        on your current
                        <span class="font-semibold text-primary">{{
                            currentPlanName
                        }}</span
                        >.
                    </p>
                </div>

                <!-- Info Box -->
                <div
                    class="space-y-2 rounded-xl border border-border/70 bg-muted/40 p-3.5 text-xs"
                >
                    <div
                        class="flex items-center justify-between text-muted-foreground"
                    >
                        <span>Current Base Limit:</span>
                        <span class="font-mono font-semibold text-foreground"
                            >{{ billingStore.baseChannelLimit }} channels</span
                        >
                    </div>
                    <div
                        v-if="billingStore.extraChannelsFromAddons > 0"
                        class="flex items-center justify-between text-muted-foreground"
                    >
                        <span>Active Addon Expansion:</span>
                        <span class="font-mono font-semibold text-emerald-600"
                            >+{{
                                billingStore.extraChannelsFromAddons
                            }}
                            channels</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-border/60 pt-2 font-semibold"
                    >
                        <span class="text-foreground">Total Quota:</span>
                        <span class="font-mono text-primary"
                            >{{ limit }} connected accounts</span
                        >
                    </div>
                </div>

                <!-- Action Options -->
                <div class="space-y-2.5">
                    <!-- Option 1: Add +3 Channels Pack ($5/mo) -->
                    <button
                        type="button"
                        @click="handleAddExtraChannelsPack"
                        class="group flex w-full cursor-pointer items-center justify-between rounded-xl border border-primary/40 bg-primary/5 p-3.5 text-left transition hover:border-primary hover:bg-primary/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-8 items-center justify-center rounded-lg bg-primary font-bold text-primary-foreground"
                            >
                                <Plus class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-foreground">
                                    Add +3 Channels Pack
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Pre-fills cart with immediate expansion
                                </p>
                            </div>
                        </div>
                        <span
                            class="font-mono text-sm font-bold text-primary group-hover:underline"
                        >
                            +$5/mo
                        </span>
                    </button>

                    <!-- Option 2: Upgrade Base Plan -->
                    <button
                        type="button"
                        @click="handleUpgradeBasePlan"
                        class="group flex w-full cursor-pointer items-center justify-between rounded-xl border border-border bg-card p-3.5 text-left transition hover:bg-muted"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-8 items-center justify-center rounded-lg border border-border bg-muted text-foreground"
                            >
                                <Zap class="size-4 text-amber-500" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-foreground">
                                    Upgrade Base Plan
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Unlock higher quotas, priority dispatch &
                                    team seats
                                </p>
                            </div>
                        </div>
                        <ArrowUpRight
                            class="size-4 text-muted-foreground transition group-hover:text-foreground"
                        />
                    </button>
                </div>

                <!-- Footer -->
                <div class="flex justify-end pt-1">
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="text-xs text-muted-foreground transition hover:text-foreground"
                    >
                        Maybe Later
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
