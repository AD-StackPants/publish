<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import OrganizationTab, {
    type WorkspaceMember,
} from '@/components/settings/OrganizationTab.vue';
import BillingTab from '@/components/settings/BillingTab.vue';
import UserSettingsTab from '@/components/settings/UserSettingsTab.vue';
import AppearanceTab from '@/components/settings/AppearanceTab.vue';
import SystemHealthTab from '@/components/settings/SystemHealthTab.vue';
import type { Passkey } from '@/types/auth';
import {
    Building2,
    CreditCard,
    User,
    Palette,
    Activity,
    Layers,
    ExternalLink,
} from '@lucide/vue';

interface Props {
    tenant_slug: string;
    mustVerifyEmail?: boolean;
    status?: string;
    canManageTwoFactor?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
    passwordRules?: string;
    members?: WorkspaceMember[];
}

const props = withDefaults(defineProps<Props>(), {
    mustVerifyEmail: false,
    status: '',
    canManageTwoFactor: false,
    canManagePasskeys: false,
    passkeys: () => [],
    twoFactorEnabled: false,
    requiresConfirmation: false,
    passwordRules: '',
    members: () => [],
});

const page = usePage();
const workspaceStore = useWorkspaceStore();

type TabKey = 'organization' | 'billing' | 'user' | 'appearance' | 'system';

const activeTab = ref<TabKey>('organization');

const tabs = [
    {
        key: 'organization' as TabKey,
        label: 'Organization',
        description: 'Profile, team seats & publishing defaults',
        icon: Building2,
    },
    {
        key: 'billing' as TabKey,
        label: 'Subscription & Billing',
        description: 'Plan tier, capacity limits & invoices',
        icon: CreditCard,
    },
    {
        key: 'user' as TabKey,
        label: 'My Profile & Security',
        description: 'Account details, password, 2FA & passkeys',
        icon: User,
    },
    {
        key: 'appearance' as TabKey,
        label: 'Appearance',
        description: 'Theme modes, feed density & alerts',
        icon: Palette,
    },
    {
        key: 'system' as TabKey,
        label: 'Automations & Health',
        description: 'Queue runner & social API connector telemetry',
        icon: Activity,
    },
];

// Initialize tab from URL query param if present
const readTabFromUrl = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const requestedTab = params.get('tab');
    if (requestedTab && tabs.some((t) => t.key === requestedTab)) {
        activeTab.value = requestedTab as TabKey;
    }
};

const setTab = (key: TabKey) => {
    activeTab.value = key;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.replaceState({}, '', url.toString());
    }
};

onMounted(async () => {
    readTabFromUrl();
    await workspaceStore.fetchOrganizations();
    if (props.tenant_slug) {
        await workspaceStore.setOrganizationBySlug(props.tenant_slug);
    }
});

watch(
    () => page.url,
    () => {
        readTabFromUrl();
    },
);

const currentOrgName = computed(
    () => workspaceStore.currentOrg?.name || 'Workspace',
);
const currentOrgSlug = computed(
    () => props.tenant_slug || workspaceStore.activeOrgSlug || 'workspace',
);
const currentPlanName = computed(
    () => workspaceStore.currentOrg?.subscription?.plan || 'Growth Pro',
);
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-6 pb-16">
        <Head title="Settings" />

        <!-- Header Context -->
        <header
            class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1
                        class="text-2xl font-bold tracking-tight text-foreground"
                    >
                        Settings
                    </h1>
                    <span
                        class="rounded-full border border-border bg-muted/60 px-2.5 py-0.5 font-mono text-xs font-medium text-muted-foreground"
                    >
                        /w/{{ currentOrgSlug }}
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    Managing settings for
                    <strong class="text-foreground">{{
                        currentOrgName
                    }}</strong>
                    &mdash; unified workspace configuration and user
                    preferences.
                </p>
            </div>

            <!-- Quick Plan Badge -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="setTab('billing')"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-2xs transition hover:bg-accent"
                >
                    <Layers class="h-3.5 w-3.5 text-primary" />
                    <span
                        >Plan: <strong>{{ currentPlanName }}</strong></span
                    >
                </button>
            </div>
        </header>

        <!-- Navigation Tabs Bar -->
        <nav
            class="flex scrollbar-none space-x-1 overflow-x-auto rounded-xl border border-border bg-muted/40 p-1.5"
            aria-label="Settings Tabs"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="setTab(tab.key)"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-medium transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                :class="[
                    activeTab === tab.key
                        ? 'border border-border bg-background font-semibold text-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-background/50 hover:text-foreground',
                ]"
            >
                <component
                    :is="tab.icon"
                    class="h-4 w-4"
                    :class="
                        activeTab === tab.key
                            ? 'text-primary'
                            : 'text-muted-foreground'
                    "
                />
                <span>{{ tab.label }}</span>
            </button>
        </nav>

        <!-- Tab Content Panes -->
        <main class="mt-6">
            <!-- 1. Organization Settings -->
            <OrganizationTab
                v-if="activeTab === 'organization'"
                :members="props.members"
                :total-seats="
                    workspaceStore.currentOrg?.subscription?.seats || 5
                "
            />

            <!-- 2. Subscription & Billing -->
            <BillingTab v-else-if="activeTab === 'billing'" />

            <!-- 3. User Profile & Security -->
            <UserSettingsTab
                v-else-if="activeTab === 'user'"
                :must-verify-email="props.mustVerifyEmail"
                :status="props.status"
                :can-manage-two-factor="props.canManageTwoFactor"
                :can-manage-passkeys="props.canManagePasskeys"
                :passkeys="props.passkeys"
                :two-factor-enabled="props.twoFactorEnabled"
                :requires-confirmation="props.requiresConfirmation"
                :password-rules="props.passwordRules"
            />

            <!-- 4. Appearance & Interface Preferences -->
            <AppearanceTab v-else-if="activeTab === 'appearance'" />

            <!-- 5. Publishing Automations & System Health -->
            <SystemHealthTab v-else-if="activeTab === 'system'" />
        </main>
    </div>
</template>
