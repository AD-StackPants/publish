<script setup lang="ts">
import { ref, computed } from 'vue';
import { useBillingStore } from '../../stores/billing';
import type { Plan } from '../../types/billing';
import {
    Check,
    X,
    Sparkles,
    Zap,
    Shield,
    Loader2,
    Calendar,
    Radio,
    Users,
} from '@lucide/vue';

const billingStore = useBillingStore();

const isOpen = computed({
    get: () => billingStore.isChangePlanModalOpen,
    set: (val: boolean) => {
        billingStore.isChangePlanModalOpen = val;
    },
});

const basePlans = computed(() => billingStore.basePlans);
const currentPlan = computed(() => billingStore.currentPlan);
const selectedPlanId = ref<string>('');
const isSwitching = ref(false);
const switchSuccess = ref(false);

const openWithCurrent = () => {
    selectedPlanId.value = currentPlan.value?.id || '';
    switchSuccess.value = false;
};

const handleSelect = (plan: Plan) => {
    selectedPlanId.value = plan.id;
};

const handleConfirmPlanChange = async () => {
    if (
        !selectedPlanId.value ||
        selectedPlanId.value === currentPlan.value?.id
    ) {
        return;
    }
    isSwitching.value = true;
    try {
        await billingStore.changePlan(selectedPlanId.value);
        switchSuccess.value = true;
        setTimeout(() => {
            isOpen.value = false;
            switchSuccess.value = false;
        }, 1200);
    } catch {
        // handled in store
    } finally {
        isSwitching.value = false;
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
            @click.self="isOpen = false"
        >
            <div
                class="w-full max-w-3xl animate-in space-y-6 rounded-2xl border border-border bg-card p-6 shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-border pb-4"
                >
                    <div>
                        <h2 class="text-lg font-bold text-foreground">
                            Select Subscription Plan
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Upgrade or downgrade your workspace base publishing
                            capacity.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Success Alert -->
                <div
                    v-if="switchSuccess"
                    class="flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-900 dark:text-emerald-200"
                >
                    <Check
                        class="size-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <span
                        >Plan changed successfully! Your billing proration has
                        been recorded.</span
                    >
                </div>

                <!-- Plans Grid -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div
                        v-for="plan in basePlans"
                        :key="plan.id"
                        @click="handleSelect(plan)"
                        class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition select-none"
                        :class="[
                            selectedPlanId === plan.id
                                ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                : 'border-border bg-card hover:border-border/80 hover:bg-muted/30',
                            currentPlan?.id === plan.id
                                ? 'ring-1 ring-border'
                                : '',
                        ]"
                    >
                        <!-- Active Badge -->
                        <div
                            v-if="currentPlan?.id === plan.id"
                            class="absolute -top-2.5 right-3 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold tracking-wider text-primary-foreground uppercase"
                        >
                            Current
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-foreground">
                                    {{ plan.name }}
                                </h3>
                                <div
                                    class="flex size-4 items-center justify-center rounded-full border"
                                    :class="
                                        selectedPlanId === plan.id
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted-foreground/40'
                                    "
                                >
                                    <Check
                                        v-if="selectedPlanId === plan.id"
                                        class="size-3"
                                    />
                                </div>
                            </div>

                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{ plan.features?.description }}
                            </p>

                            <div class="mt-4 flex items-baseline gap-1">
                                <span
                                    class="font-mono text-2xl font-bold text-foreground"
                                >
                                    ${{ (plan.price_cents / 100).toFixed(0) }}
                                </span>
                                <span class="text-xs text-muted-foreground"
                                    >/ month</span
                                >
                            </div>

                            <div
                                class="mt-4 space-y-2 border-t border-border/60 pt-3 text-xs"
                            >
                                <div
                                    class="flex items-center gap-2 text-foreground"
                                >
                                    <Radio
                                        class="size-3.5 shrink-0 text-primary"
                                    />
                                    <span
                                        ><strong>{{
                                            plan.features?.channels_limit
                                        }}</strong>
                                        Channels Included</span
                                    >
                                </div>
                                <div
                                    class="flex items-center gap-2 text-foreground"
                                >
                                    <Calendar
                                        class="size-3.5 shrink-0 text-primary"
                                    />
                                    <span>{{
                                        plan.features?.allows_scheduling
                                            ? 'Universal Scheduling'
                                            : 'Immediate Publishing Only'
                                    }}</span>
                                </div>
                                <div
                                    v-if="plan.features?.team_members"
                                    class="flex items-center gap-2 text-foreground"
                                >
                                    <Users
                                        class="size-3.5 shrink-0 text-primary"
                                    />
                                    <span
                                        ><strong>{{
                                            plan.features?.team_members
                                        }}</strong>
                                        Team Seats</span
                                    >
                                </div>
                                <div
                                    v-if="plan.features?.priority_worker"
                                    class="flex items-center gap-2 font-medium text-emerald-600 dark:text-emerald-400"
                                >
                                    <Zap class="size-3.5 shrink-0" />
                                    <span>Dedicated Priority Worker</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-border/40 pt-3">
                            <button
                                type="button"
                                :disabled="currentPlan?.id === plan.id"
                                class="w-full rounded-lg py-1.5 text-xs font-semibold transition"
                                :class="
                                    currentPlan?.id === plan.id
                                        ? 'cursor-default bg-muted text-muted-foreground'
                                        : selectedPlanId === plan.id
                                          ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                          : 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                                "
                            >
                                {{
                                    currentPlan?.id === plan.id
                                        ? 'Current Tier'
                                        : 'Select Tier'
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="flex items-center justify-between border-t border-border pt-4"
                >
                    <p class="text-xs text-muted-foreground">
                        Prorated charges or credits will be immediately adjusted
                        on your subscription invoice.
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isOpen = false"
                            class="rounded-lg border border-border px-3.5 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="handleConfirmPlanChange"
                            :disabled="
                                !selectedPlanId ||
                                selectedPlanId === currentPlan?.id ||
                                isSwitching
                            "
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Loader2
                                v-if="isSwitching"
                                class="size-3.5 animate-spin"
                            />
                            <span>Confirm Plan Change</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
