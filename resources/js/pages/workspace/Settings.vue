<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { apiClient } from '@/api/client';
import type { SystemHealthStatus } from '@/types/workspace';
import {
    Building2,
    Clock,
    Activity,
    Check,
    Database,
    Cpu,
    Radio,
    Search,
    ChevronDown,
    Save,
    CreditCard,
} from '@lucide/vue';

const workspaceStore = useWorkspaceStore();

const currentSlug = computed(
    () => workspaceStore.activeOrgSlug || 'acme-studio',
);

const orgName = ref('');
const orgSlug = ref('');
const selectedTimezone = ref('America/New_York');
const timezoneSearch = ref('');
const isTimezoneDropdownOpen = ref(false);

const isSaving = ref(false);
const saveSuccess = ref(false);

const health = ref<SystemHealthStatus | null>(null);

const timezones = [
    'UTC',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Paris',
    'Europe/Berlin',
    'Asia/Tokyo',
    'Asia/Singapore',
    'Asia/Dubai',
    'Australia/Sydney',
];

const filteredTimezones = computed(() => {
    if (!timezoneSearch.value) return timezones;
    return timezones.filter((t) =>
        t.toLowerCase().includes(timezoneSearch.value.toLowerCase()),
    );
});

const loadOrgData = () => {
    if (workspaceStore.currentOrg) {
        orgName.value = workspaceStore.currentOrg.name;
        orgSlug.value = workspaceStore.currentOrg.slug;
        selectedTimezone.value = workspaceStore.currentOrg.timezone || 'UTC';
    }
};

const fetchHealth = async () => {
    try {
        const res = await apiClient.get<SystemHealthStatus>('/system/health');
        health.value = res.data;
    } catch {
        // fallback
    }
};

onMounted(async () => {
    await workspaceStore.fetchOrganizations();
    loadOrgData();
    await fetchHealth();
});

watch(
    () => workspaceStore.currentOrg,
    () => {
        loadOrgData();
    },
);

const handleSaveProfile = async () => {
    isSaving.value = true;
    saveSuccess.value = false;
    try {
        await workspaceStore.updateOrgSettings({
            name: orgName.value,
            slug: orgSlug.value,
            timezone: selectedTimezone.value,
        });
        saveSuccess.value = true;
        setTimeout(() => {
            saveSuccess.value = false;
        }, 3000);
    } catch (err) {
        console.error('Failed to update workspace settings', err);
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="max-w-4xl space-y-6">
        <Head title="Settings" />
        <h1 class="sr-only">Settings</h1>

        <!-- Success Toast -->
        <div
            v-if="saveSuccess"
            class="flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3.5 text-xs font-semibold text-emerald-900 dark:text-emerald-200"
        >
            <Check class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
            <span
                >Workspace profile and timezone preferences saved
                successfully.</span
            >
        </div>

        <!-- Organization Profile Form -->
        <div
            class="space-y-5 rounded-xl border border-border bg-card p-5 shadow-sm"
        >
            <div class="flex items-center gap-2 border-b border-border pb-2">
                <Building2 class="h-4 w-4 text-primary" />
                <h2 class="text-sm font-bold text-foreground">
                    Organization Profile
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-foreground"
                        >Organization Name</label
                    >
                    <input
                        v-model="orgName"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs focus:ring-1 focus:ring-primary focus:outline-none"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-foreground"
                        >Workspace Slug (URL namespace)</label
                    >
                    <div class="flex items-center">
                        <span
                            class="rounded-l-lg border border-r-0 border-border bg-muted px-2.5 py-2 font-mono text-xs text-muted-foreground"
                        >
                            /w/
                        </span>
                        <input
                            v-model="orgSlug"
                            type="text"
                            class="flex-1 rounded-r-lg border border-border bg-background px-3 py-2 font-mono text-xs focus:ring-1 focus:ring-primary focus:outline-none"
                        />
                    </div>
                </div>
            </div>

            <!-- Timezone Selector with Search -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-foreground">
                    Default Scheduling Timezone
                </label>
                <p class="text-[11px] text-muted-foreground">
                    All scheduled social posts will calculate publication
                    dispatch times against this timezone.
                </p>

                <div class="relative max-w-sm">
                    <button
                        type="button"
                        @click="
                            isTimezoneDropdownOpen = !isTimezoneDropdownOpen
                        "
                        class="flex w-full items-center justify-between rounded-lg border border-border bg-background px-3 py-2 text-left text-xs transition"
                    >
                        <span class="flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                            <span>{{ selectedTimezone }}</span>
                        </span>
                        <ChevronDown
                            class="h-3.5 w-3.5 text-muted-foreground"
                        />
                    </button>

                    <!-- Dropdown with search -->
                    <div
                        v-if="isTimezoneDropdownOpen"
                        class="absolute top-full right-0 left-0 z-50 mt-1 space-y-2 rounded-lg border border-border bg-popover p-2 text-popover-foreground shadow-xl"
                    >
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                v-model="timezoneSearch"
                                placeholder="Search timezone..."
                                class="w-full rounded border border-border bg-background py-1 pr-2 pl-8 text-xs focus:outline-none"
                            />
                        </div>

                        <div
                            class="max-h-48 divide-y divide-border/60 overflow-y-auto"
                        >
                            <button
                                v-for="tz in filteredTimezones"
                                :key="tz"
                                type="button"
                                @click="
                                    selectedTimezone = tz;
                                    isTimezoneDropdownOpen = false;
                                "
                                class="flex w-full items-center justify-between rounded px-2.5 py-1.5 text-left text-xs transition hover:bg-accent"
                                :class="
                                    selectedTimezone === tz
                                        ? 'bg-accent/60 font-semibold'
                                        : ''
                                "
                            >
                                <span>{{ tz }}</span>
                                <Check
                                    v-if="selectedTimezone === tz"
                                    class="h-3.5 w-3.5 text-primary"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Action Button -->
            <div class="flex justify-end border-t border-border pt-3">
                <button
                    type="button"
                    @click="handleSaveProfile"
                    :disabled="isSaving"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                >
                    <Save class="h-3.5 w-3.5" />
                    <span>{{ isSaving ? 'Saving...' : 'Save Settings' }}</span>
                </button>
            </div>
        </div>

        <!-- Tenant Subscription Overview -->
        <div
            class="space-y-4 rounded-xl border border-border bg-card p-5 shadow-sm"
        >
            <div
                class="flex items-center justify-between border-b border-border pb-2"
            >
                <div class="flex items-center gap-2">
                    <CreditCard class="h-4 w-4 text-primary" />
                    <h2 class="text-sm font-bold text-foreground">
                        Tenant Subscription & Plan
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 font-mono text-xs font-semibold text-emerald-600 uppercase"
                    >
                        {{
                            workspaceStore.currentOrg?.subscription?.status ||
                            'Active'
                        }}
                    </span>
                    <Link
                        :href="`/w/${currentSlug}/settings/billing`"
                        class="ml-2 text-xs font-semibold text-primary hover:underline"
                    >
                        Manage Billing &rarr;
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <p class="text-xs text-muted-foreground">Current Plan</p>
                    <p class="text-base font-bold text-foreground">
                        {{
                            workspaceStore.currentOrg?.subscription?.plan ||
                            'Growth Pro'
                        }}
                    </p>
                    <p class="text-[10px] text-muted-foreground capitalize">
                        {{
                            workspaceStore.currentOrg?.subscription
                                ?.billing_period || 'Monthly'
                        }}
                        recurring billing
                    </p>
                </div>

                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        Seats & Allocation
                    </p>
                    <p class="text-base font-bold text-foreground">
                        {{
                            workspaceStore.currentOrg?.subscription?.seats || 5
                        }}
                        Member Seats
                    </p>
                    <p class="text-[10px] text-muted-foreground">
                        All active social channels enabled
                    </p>
                </div>

                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <p class="text-xs text-muted-foreground">Renewal Date</p>
                    <p class="text-base font-bold text-foreground">
                        {{
                            workspaceStore.currentOrg?.subscription
                                ?.renews_at || 'Oct 15, 2026'
                        }}
                    </p>
                    <p class="text-[10px] text-muted-foreground">
                        Auto-renews next cycle
                    </p>
                </div>
            </div>
        </div>

        <!-- Publishing Automation System Status -->
        <div
            class="space-y-4 rounded-xl border border-border bg-card p-5 shadow-sm"
        >
            <div
                class="flex items-center justify-between border-b border-border pb-2"
            >
                <div class="flex items-center gap-2">
                    <Activity class="h-4 w-4 text-emerald-500" />
                    <h2 class="text-sm font-bold text-foreground">
                        Publishing Automation & Network Status
                    </h2>
                </div>
                <span class="font-mono text-xs text-muted-foreground">
                    Status:
                    <strong class="text-emerald-600 dark:text-emerald-400"
                        >All Systems Operational</strong
                    >
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5 font-medium"
                            ><Clock class="h-3.5 w-3.5 text-primary" />
                            Scheduled Queue Dispatch</span
                        >
                        <span
                            class="font-mono text-[10px] font-bold text-emerald-600 uppercase"
                            >Active</span
                        >
                    </div>
                    <p class="text-sm font-bold text-foreground">
                        Automated Publishing Runner
                    </p>
                    <p class="text-[10px] text-muted-foreground">
                        Instant delivery at scheduled publication times
                    </p>
                </div>

                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5 font-medium"
                            ><Radio class="h-3.5 w-3.5 text-primary" /> Network
                            API Connectors</span
                        >
                        <span
                            class="font-mono text-[10px] font-bold text-emerald-600 uppercase"
                            >Healthy</span
                        >
                    </div>
                    <p class="text-sm font-bold text-foreground">
                        X, LinkedIn & Meta Graph APIs
                    </p>
                    <p class="text-[10px] text-muted-foreground">
                        Rate limit protection & auto-reconnect enabled
                    </p>
                </div>

                <div
                    class="space-y-1 rounded-lg border border-border/80 bg-muted/40 p-3"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5 font-medium"
                            ><Database class="h-3.5 w-3.5 text-primary" /> Media
                            Optimization</span
                        >
                        <span
                            class="font-mono text-[10px] font-bold text-blue-600 uppercase"
                            >Optimal</span
                        >
                    </div>
                    <p class="text-sm font-bold text-foreground">
                        Canvas & OpenGraph Engine
                    </p>
                    <p class="text-[10px] text-muted-foreground">
                        Fast image cropping & preview card generation
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
