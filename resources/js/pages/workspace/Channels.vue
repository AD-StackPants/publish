<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { useBillingStore } from '@/stores/billing';
import { apiClient } from '@/api/client';
import type { SocialAccount, SocialProvider } from '@/types/workspace';
import UpgradePromptModal from '@/components/billing/UpgradePromptModal.vue';
import BillingCartDrawer from '@/components/billing/BillingCartDrawer.vue';
import {
    Radio,
    Plus,
    RefreshCw,
    Trash2,
    CheckCircle2,
    AlertTriangle,
    Clock,
    XCircle,
    ExternalLink,
    X,
    Shield,
    Key,
    Check,
    Zap,
    Users,
    Activity,
    Lock,
    Sparkles,
    ShieldCheck,
} from '@lucide/vue';

const workspaceStore = useWorkspaceStore();
const billingStore = useBillingStore();

const accounts = computed(() => workspaceStore.accounts);
const isConnecting = ref(false);
const isConnectModalOpen = ref(false);

// Connect Channel Form
const selectedProvider = ref<SocialProvider>('twitter');
const customAccountName = ref('');
const customHandle = ref('');
const isSimulatingOAuth = ref(false);
const oauthStep = ref<'select' | 'handshake' | 'complete'>('select');

const providers: {
    id: SocialProvider;
    name: string;
    description: string;
    avatar: string;
    color: string;
}[] = [
    {
        id: 'twitter',
        name: 'X (formerly Twitter)',
        description:
            'Post text updates, media cards, and track engagement with 280-char limits.',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
        color: 'bg-black text-white dark:bg-white dark:text-black',
    },
    {
        id: 'linkedin',
        name: 'LinkedIn Company Page',
        description:
            'Broadcast corporate thought-leadership, image carousels, and employee updates.',
        avatar: 'https://images.unsplash.com/photo-1572021335469-31706a17aaef?w=150',
        color: 'bg-[#0077b5] text-white',
    },
    {
        id: 'facebook',
        name: 'Facebook Business Page',
        description:
            'Publish community updates, product launches, and stories via Meta Graph API.',
        avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
        color: 'bg-[#1877f2] text-white',
    },
];

onMounted(async () => {
    await workspaceStore.fetchAccounts();
    await billingStore.fetchCatalog();
    await billingStore.fetchSubscription();
});

watch(
    () => workspaceStore.activeOrgId,
    async () => {
        await workspaceStore.fetchAccounts();
        await billingStore.fetchSubscription();
    },
);

const openConnectModal = () => {
    if (!billingStore.canAddChannel(accounts.value.length)) {
        billingStore.isUpgradeModalOpen = true;
        return;
    }
    selectedProvider.value = 'twitter';
    customAccountName.value = '';
    customHandle.value = '';
    oauthStep.value = 'select';
    isConnectModalOpen.value = true;
};

const triggerOAuthSimulation = async () => {
    isSimulatingOAuth.value = true;
    oauthStep.value = 'handshake';

    setTimeout(async () => {
        const prov = providers.find((p) => p.id === selectedProvider.value);
        const name =
            customAccountName.value ||
            `${workspaceStore.currentOrg?.name} ${prov?.name.split(' ')[0]}`;
        const handle =
            customHandle.value ||
            `@${workspaceStore.activeOrgSlug}_${selectedProvider.value}`;

        try {
            await apiClient.post('/accounts', {
                provider: selectedProvider.value,
                name: name,
                handle: handle,
                avatar_url: prov?.avatar,
            });
            await workspaceStore.fetchAccounts();
            oauthStep.value = 'complete';
            setTimeout(() => {
                isConnectModalOpen.value = false;
                isSimulatingOAuth.value = false;
            }, 1200);
        } catch (err: any) {
            alert(err?.response?.data?.message || 'OAuth Connection Failed');
            isSimulatingOAuth.value = false;
            oauthStep.value = 'select';
        }
    }, 1500);
};

const handleReconnect = async (accId: string) => {
    try {
        await apiClient.patch(`/accounts/${accId}/reconnect`);
        await workspaceStore.fetchAccounts();
    } catch (err) {
        console.error('Reconnect failed', err);
    }
};

const handleDisconnect = async (accId: string) => {
    if (
        !confirm(
            'Disconnect this profile? Future scheduled posts targeting this channel will be paused.',
        )
    ) {
        return;
    }
    try {
        await apiClient.delete(`/accounts/${accId}`);
        await workspaceStore.fetchAccounts();
    } catch (err) {
        console.error('Disconnect failed', err);
    }
};

const getProviderBadge = (provider: string) => {
    switch (provider) {
        case 'twitter':
            return {
                label: 'X (Twitter)',
                class: 'bg-black text-white dark:bg-white dark:text-black',
            };
        case 'linkedin':
            return { label: 'LinkedIn', class: 'bg-[#0077b5] text-white' };
        case 'facebook':
            return { label: 'Facebook', class: 'bg-[#1877f2] text-white' };
        default:
            return { label: provider, class: 'bg-muted text-muted-foreground' };
    }
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Connected Channels" />
        <h1 class="sr-only">Connected Channels</h1>

        <!-- Streamlined Action Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Quota Counter Pill -->
            <div
                class="flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-1.5 text-xs shadow-xs"
            >
                <Radio class="size-3.5 text-primary" />
                <span class="font-medium text-foreground">
                    <strong class="font-mono">{{ accounts.length }}</strong>
                    /
                    <span class="font-mono text-muted-foreground">{{
                        billingStore.totalChannelLimit
                    }}</span>
                    Channels Connected
                </span>
                <button
                    type="button"
                    @click="billingStore.isUpgradeModalOpen = true"
                    class="ml-1 cursor-pointer text-[11px] font-semibold text-primary hover:underline"
                >
                    Add Slots &rarr;
                </button>
            </div>

            <button
                type="button"
                @click="openConnectModal"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90"
            >
                <Plus class="size-3.5" />
                <span>Connect Profile</span>
            </button>
        </div>

        <!-- Channel Cards Grid -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="acc in accounts"
                :key="acc.id"
                class="flex flex-col justify-between space-y-4 rounded-2xl border border-border bg-card p-5 shadow-xs transition hover:border-border/80 hover:shadow-sm"
            >
                <!-- Card Header: Avatar & Channel Identity -->
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="relative">
                                <img
                                    :src="
                                        acc.avatar_url ||
                                        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150'
                                    "
                                    :alt="acc.name"
                                    class="size-12 shrink-0 rounded-xl border border-border object-cover shadow-2xs"
                                />
                                <span
                                    class="absolute -right-1 -bottom-1 flex size-5 items-center justify-center rounded-md font-mono text-[9px] font-bold uppercase shadow-xs"
                                    :class="
                                        getProviderBadge(acc.provider).class
                                    "
                                >
                                    {{ acc.provider[0] }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h3
                                    class="truncate text-sm font-bold text-foreground"
                                >
                                    {{ acc.name }}
                                </h3>
                                <p
                                    class="truncate font-mono text-xs text-muted-foreground"
                                >
                                    {{ acc.handle || acc.account_id }}
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <span
                            class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 font-mono text-[10px] font-bold tracking-wider uppercase shadow-xs"
                            :class="{
                                'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400':
                                    acc.status === 'healthy',
                                'border-rose-500/20 bg-rose-500/10 text-rose-600 dark:text-rose-400':
                                    acc.status === 'expiring' ||
                                    acc.status === 'revoked',
                                'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400':
                                    acc.status === 'cooling',
                            }"
                        >
                            <span class="size-1.5 rounded-full bg-current" />
                            {{
                                acc.status === 'healthy' ? 'Active' : acc.status
                            }}
                        </span>
                    </div>

                    <!-- Audience & Connection Meta -->
                    <div
                        class="mt-4 space-y-2 border-t border-border/60 pt-3 text-xs"
                    >
                        <div
                            class="flex items-center justify-between text-muted-foreground"
                        >
                            <span>Network</span>
                            <span
                                class="font-semibold text-foreground capitalize"
                                >{{ acc.provider }}</span
                            >
                        </div>

                        <div
                            class="flex items-center justify-between text-muted-foreground"
                        >
                            <span>Connection Health</span>
                            <span
                                class="font-medium"
                                :class="
                                    acc.status === 'healthy'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-amber-600'
                                "
                            >
                                {{
                                    acc.status === 'healthy'
                                        ? 'Synced & Ready'
                                        : acc.status === 'cooling'
                                          ? `Rate Limited (${acc.cooldown_resumes_in || '20m'})`
                                          : 'Token Expiring'
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between text-muted-foreground"
                        >
                            <span>Token Authentication</span>
                            <span class="font-mono text-[11px] text-foreground">
                                {{
                                    acc.token_expires_at
                                        ? new Date(
                                              acc.token_expires_at,
                                          ).toLocaleDateString()
                                        : 'Auto-Renewing'
                                }}
                            </span>
                        </div>

                        <!-- Cooldown notice -->
                        <div
                            v-if="acc.status === 'cooling'"
                            class="flex items-center gap-1.5 rounded-lg border border-amber-500/20 bg-amber-500/10 p-2 text-[11px] text-amber-800 dark:text-amber-300"
                        >
                            <Clock class="size-3.5 shrink-0" />
                            <span
                                >Temporary rate limit backoff active.
                                Auto-resumes in
                                {{ acc.cooldown_resumes_in || '20m' }}.</span
                            >
                        </div>

                        <!-- Expiring notice -->
                        <div
                            v-if="acc.status === 'expiring'"
                            class="flex items-center gap-1.5 rounded-lg border border-rose-500/20 bg-rose-500/10 p-2 text-[11px] text-rose-800 dark:text-rose-300"
                        >
                            <AlertTriangle class="size-3.5 shrink-0" />
                            <span
                                >Access token expires soon. Refresh now to
                                prevent scheduling delays.</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div
                    class="flex items-center justify-between gap-2 border-t border-border pt-3"
                >
                    <button
                        type="button"
                        @click="handleReconnect(acc.id)"
                        class="inline-flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs transition hover:bg-muted"
                    >
                        <RefreshCw class="size-3 text-primary" />
                        <span>Refresh Token</span>
                    </button>

                    <button
                        type="button"
                        @click="handleDisconnect(acc.id)"
                        class="cursor-pointer rounded-lg border border-border bg-card p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                        title="Disconnect Profile"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Connect Channel Modal with Simulated OAuth Wizard -->
        <Transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isConnectModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
                @click.self="!isSimulatingOAuth && (isConnectModalOpen = false)"
            >
                <div
                    class="w-full max-w-md animate-in space-y-5 rounded-2xl border border-border bg-card p-6 shadow-2xl duration-200 zoom-in-95 fade-in"
                >
                    <div
                        class="flex items-center justify-between border-b border-border pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <Key class="size-4 text-primary" />
                            <h3 class="text-sm font-bold text-foreground">
                                Connect Social Profile
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="isConnectModalOpen = false"
                            :disabled="isSimulatingOAuth"
                            class="rounded-md p-1 text-muted-foreground hover:bg-muted"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <!-- Step 1: Network Selection -->
                    <div v-if="oauthStep === 'select'" class="space-y-4">
                        <div class="space-y-2">
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Select Platform
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="prov in providers"
                                    :key="prov.id"
                                    type="button"
                                    @click="selectedProvider = prov.id"
                                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border p-3 transition select-none"
                                    :class="
                                        selectedProvider === prov.id
                                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                            : 'border-border bg-card hover:bg-muted'
                                    "
                                >
                                    <div
                                        class="flex size-8 items-center justify-center rounded-lg font-mono text-xs font-bold"
                                        :class="prov.color"
                                    >
                                        {{ prov.id[0].toUpperCase() }}
                                    </div>
                                    <span
                                        class="mt-2 max-w-full truncate text-xs font-semibold text-foreground"
                                    >
                                        {{ prov.name.split(' ')[0] }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Profile Details -->
                        <div class="space-y-3 pt-1">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-medium text-muted-foreground"
                                    >Account / Page Name</label
                                >
                                <input
                                    v-model="customAccountName"
                                    type="text"
                                    :placeholder="`e.g. Acme Studio (${selectedProvider})`"
                                    class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-medium text-muted-foreground"
                                    >Profile Handle</label
                                >
                                <input
                                    v-model="customHandle"
                                    type="text"
                                    :placeholder="`@${workspaceStore.activeOrgSlug}_brand`"
                                    class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                                />
                            </div>
                        </div>

                        <div
                            class="space-y-1 rounded-xl border border-border/70 bg-muted/40 p-3 text-xs text-muted-foreground"
                        >
                            <div
                                class="flex items-center gap-1.5 font-semibold text-foreground"
                            >
                                <ShieldCheck
                                    class="size-3.5 text-emerald-500"
                                />
                                <span>Official API Authorization</span>
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                Authorizes the workspace to publish scheduled
                                text, images, and video on behalf of your brand
                                profile.
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t border-border pt-3"
                        >
                            <button
                                type="button"
                                @click="isConnectModalOpen = false"
                                class="rounded-lg border border-border px-3.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="triggerOAuthSimulation"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                            >
                                <span>Authorize & Connect</span>
                                <ExternalLink class="size-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Handshake State -->
                    <div
                        v-else-if="oauthStep === 'handshake'"
                        class="space-y-4 py-6 text-center"
                    >
                        <div
                            class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <RefreshCw class="size-6 animate-spin" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">
                                Connecting with
                                {{ selectedProvider.toUpperCase() }}...
                            </h4>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Securely completing OAuth 2.0 authorization and
                                requesting publishing scopes...
                            </p>
                        </div>
                    </div>

                    <!-- Step 3: Complete State -->
                    <div
                        v-else-if="oauthStep === 'complete'"
                        class="space-y-3 py-6 text-center"
                    >
                        <div
                            class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"
                        >
                            <Check class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">
                                Profile Successfully Connected!
                            </h4>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Your social account is armed and ready for
                                automated publishing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Billing Modals & Add-on Cart -->
        <UpgradePromptModal />
        <BillingCartDrawer />
    </div>
</template>
