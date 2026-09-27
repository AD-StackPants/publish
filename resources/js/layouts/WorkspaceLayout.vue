<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { apiClient } from '@/api/client';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type { BreadcrumbItem } from '@/types';
import type { SystemHealthStatus } from '@/types/workspace';
import {
    Calendar,
    Send,
    Radio,
    Settings,
    AlertTriangle,
    Bell,
    ChevronsUpDown,
    LogOut,
    User as UserIcon,
    RefreshCw,
    X,
    CreditCard,
    Zap,
    ArrowUpRight,
} from '@lucide/vue';
import {
    SidebarProvider,
    Sidebar,
    SidebarHeader,
    SidebarContent,
    SidebarFooter,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
    SidebarInset,
    SidebarTrigger,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const workspaceStore = useWorkspaceStore();

const isNotificationsOpen = ref(false);
const systemHealth = ref<SystemHealthStatus | null>(null);

const notifications = ref([
    {
        id: 'notif-1',
        title: 'Posting Cooldown Active (Meta)',
        message:
            'Post scheduled for Acme Studio hit Facebook temporary rate limits. Auto-resumes in 15m.',
        time: '15m ago',
        type: 'warning',
    },
    {
        id: 'notif-2',
        title: 'Channel Needs Re-Authentication',
        message:
            '1 post paused because your Facebook Page token requires renewal. Click to reconnect.',
        time: '1h ago',
        type: 'error',
    },
]);

const currentSlug = computed(() => {
    return (
        (page.props.tenant_slug as string) ||
        workspaceStore.activeOrgSlug ||
        'acme-studio'
    );
});

const currentSubscription = computed(() => {
    return (
        workspaceStore.currentOrg?.subscription || {
            plan: 'Pro Plan',
            status: 'active' as const,
            seats: 5,
            billing_period: 'monthly' as const,
            renews_at: '2026-10-15',
            cancel_at_period_end: false,
            scheduled_posts_limit: 30,
            scheduled_posts_used: 18,
        }
    );
});

const isFreePlan = computed(() => {
    const plan = (currentSubscription.value.plan || '').toLowerCase();
    return plan.includes('free');
});

const isCancelled = computed(() => {
    const status = currentSubscription.value.status;
    return (
        status === 'canceled' ||
        Boolean(currentSubscription.value.cancel_at_period_end)
    );
});

const daysRemaining = computed(() => {
    if (!currentSubscription.value.renews_at) return 14;
    const renewDate = new Date(currentSubscription.value.renews_at);
    const now = new Date();
    const diffMs = renewDate.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
});

const postsQuota = computed(() => {
    if (isFreePlan.value) {
        return {
            used: currentSubscription.value.scheduled_posts_used ?? 3,
            limit: currentSubscription.value.scheduled_posts_limit ?? 10,
            label: '10 Scheduled Posts per month',
        };
    }
    const plan = (currentSubscription.value.plan || '').toLowerCase();
    if (plan.includes('agency')) {
        return {
            used: currentSubscription.value.scheduled_posts_used ?? 64,
            limit: currentSubscription.value.scheduled_posts_limit ?? 150,
            label: '150 Scheduled Posts per month',
        };
    }
    // Default Pro Plan: exactly 30 Scheduled Posts per month
    return {
        used: currentSubscription.value.scheduled_posts_used ?? 18,
        limit: currentSubscription.value.scheduled_posts_limit ?? 30,
        label: '30 Scheduled Posts per month',
    };
});

const postsUsagePercentage = computed(() => {
    return Math.min(
        100,
        Math.round((postsQuota.value.used / postsQuota.value.limit) * 100),
    );
});

const channelUsage = computed(() => {
    const connected = workspaceStore.accounts.length;
    const planName = (currentSubscription.value.plan || '').toLowerCase();
    const limit = planName.includes('agency')
        ? 15
        : planName.includes('free')
          ? 1
          : 5;
    const percentage = Math.min(100, Math.round((connected / limit) * 100));
    return {
        connected,
        limit,
        percentage,
    };
});

const currentUser = computed(() => {
    return (
        (
            page.props.auth as
                | { user?: { name: string; email: string } }
                | undefined
        )?.user || {
            name: 'Workspace Admin',
            email: 'admin@example.com',
        }
    );
});

const fetchHealth = async () => {
    try {
        const res = await apiClient.get<SystemHealthStatus>('/system/health');
        systemHealth.value = res.data;
    } catch {
        // fallback
    }
};

onMounted(async () => {
    await workspaceStore.fetchOrganizations();
    if (page.props.tenant_slug && typeof page.props.tenant_slug === 'string') {
        await workspaceStore.setOrganizationBySlug(page.props.tenant_slug);
    }
    await workspaceStore.fetchAccounts();
    await fetchHealth();
});

watch(
    () => page.props.tenant_slug,
    async (newSlug) => {
        if (
            newSlug &&
            typeof newSlug === 'string' &&
            newSlug !== workspaceStore.activeOrgSlug
        ) {
            await workspaceStore.setOrganizationBySlug(newSlug);
        }
    },
);

const handleRefreshAccount = (accId?: string) => {
    router.visit(`/w/${currentSlug.value}/channels`);
};

const dismissNotification = (id: string) => {
    notifications.value = notifications.value.filter((n) => n.id !== id);
};

const handleLogout = () => {
    router.post('/logout');
};

const defaultBreadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [];

    if (page.component === 'workspace/PostsFeed') {
        items.push({
            title: 'Posts & Schedule',
            href: `/w/${currentSlug.value}/posts`,
        });
    } else if (page.component === 'workspace/Composer') {
        items.push({
            title: 'Universal Composer',
            href: `/w/${currentSlug.value}/composer`,
        });
    } else if (page.component === 'workspace/Channels') {
        items.push({
            title: 'Connected Channels',
            href: `/w/${currentSlug.value}/channels`,
        });
    } else if (page.component === 'workspace/Settings') {
        items.push({
            title: 'Settings',
            href: `/w/${currentSlug.value}/settings`,
        });
    }

    return items;
});

const activeBreadcrumbs = computed(() => {
    return props.breadcrumbs && props.breadcrumbs.length > 0
        ? props.breadcrumbs
        : defaultBreadcrumbs.value;
});
</script>

<template>
    <SidebarProvider>
        <!-- Laravel Default Sidebar -->
        <Sidebar collapsible="icon" variant="inset">
            <!-- Sidebar Header: Static Workspace Brand -->
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            as="div"
                            class="cursor-default select-none hover:bg-transparent focus-visible:ring-0 active:bg-transparent"
                        >
                            <div
                                class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground shadow-xs"
                            >
                                {{
                                    (workspaceStore.currentOrg?.name || 'P')[0]
                                }}
                            </div>
                            <div
                                class="grid min-w-0 flex-1 text-left text-sm leading-tight"
                            >
                                <!-- !FIXME: Hydration text content mismatch -->
                                <span
                                    data-allow-mismatch
                                    class="truncate font-semibold tracking-tight text-sidebar-foreground"
                                >
                                    {{
                                        workspaceStore.currentOrg?.name ||
                                        'Workspace'
                                    }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="truncate font-mono text-xs text-muted-foreground"
                                    >
                                        /w/{{ currentSlug }}
                                    </span>
                                </div>
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <!-- Sidebar Content: Main Navigation Links -->
            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Publishing</SidebarGroupLabel>
                    <SidebarMenu>
                        <!-- 1. Posts & Schedule (Main Page) -->
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                as-child
                                :is-active="
                                    page.component === 'workspace/PostsFeed'
                                "
                                tooltip="Posts & Schedule"
                            >
                                <Link :href="`/w/${currentSlug}/posts`">
                                    <Calendar class="size-4" />
                                    <span>Posts & Schedule</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <!-- 2. Universal Composer -->
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                as-child
                                :is-active="
                                    page.component === 'workspace/Composer'
                                "
                                tooltip="Universal Composer"
                            >
                                <Link :href="`/w/${currentSlug}/composer`">
                                    <Send class="size-4" />
                                    <span>Universal Composer</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <!-- 3. Connected Channels Hub -->
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                as-child
                                :is-active="
                                    page.component === 'workspace/Channels'
                                "
                                tooltip="Connected Channels"
                            >
                                <Link :href="`/w/${currentSlug}/channels`">
                                    <Radio class="size-4" />
                                    <span>Connected Channels</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <!-- Sidebar Footer: Tenant Subscription, Settings at Bottom & Logout -->
            <SidebarFooter class="gap-2">
                <!-- Tenant Subscription & Social Publishing Usage (Flat Layout, Entire Area Clickable) -->
                <Link
                    :href="`/w/${currentSlug}/settings?tab=billing`"
                    class="group/sub block cursor-pointer space-y-2.5 rounded-lg px-2 py-1.5 text-xs transition-colors group-data-[collapsible=icon]:hidden hover:bg-sidebar-accent/50"
                    title="Manage Subscription & Billing"
                >
                    <!-- 1. Plan Title & Status / Upgrade / Arrow -->
                    <div class="flex items-center justify-between gap-1.5">
                        <div class="flex min-w-0 items-center gap-1.5">
                            <span
                                class="truncate font-semibold tracking-tight text-sidebar-foreground transition-colors group-hover/sub:text-primary"
                            >
                                {{ currentSubscription.plan }}
                            </span>
                            <span
                                v-if="!isFreePlan && !isCancelled"
                                class="text-[10px] text-muted-foreground capitalize"
                            >
                                · {{ currentSubscription.billing_period }}
                            </span>
                        </div>

                        <!-- If Cancelled: Show Days Left Pill -->
                        <span
                            v-if="isCancelled"
                            class="inline-flex items-center gap-1 rounded-md border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 font-mono text-[10px] font-medium text-amber-600 dark:text-amber-400"
                        >
                            <span
                                class="size-1.5 animate-pulse rounded-full bg-amber-500"
                            />
                            {{ daysRemaining }}d left
                        </span>

                        <!-- If Free Plan: Mini Upgrade Tag -->
                        <span
                            v-else-if="isFreePlan"
                            class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold text-primary"
                        >
                            <span>Upgrade</span>
                            <ArrowUpRight class="size-3" />
                        </span>

                        <!-- Subtle hover arrow indicator -->
                        <ArrowUpRight
                            v-else
                            class="size-3.5 text-muted-foreground/40 transition-all group-hover/sub:translate-x-0.5 group-hover/sub:-translate-y-0.5 group-hover/sub:text-sidebar-foreground"
                        />
                    </div>

                    <!-- 2. SaaS Social Media Publishing Quota: 30 Scheduled Posts per month -->
                    <div class="space-y-1">
                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <Calendar
                                    class="size-3 text-muted-foreground/80"
                                />
                                <span>Scheduled Posts</span>
                            </span>
                            <span class="font-mono text-xs">
                                <span
                                    class="font-semibold text-sidebar-foreground"
                                    >{{ postsQuota.used }}</span
                                >
                                <span class="text-muted-foreground/70">
                                    / {{ postsQuota.limit }}</span
                                >
                            </span>
                        </div>
                        <!-- Micro Progress Bar -->
                        <div
                            class="h-1.5 w-full overflow-hidden rounded-full bg-sidebar-accent"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500 ease-out"
                                :class="[
                                    postsUsagePercentage >= 90
                                        ? 'bg-amber-500'
                                        : 'bg-primary',
                                ]"
                                :style="{ width: `${postsUsagePercentage}%` }"
                            />
                        </div>
                        <div
                            class="flex items-center justify-between text-[10px] text-muted-foreground"
                        >
                            <span>{{ postsQuota.label }}</span>
                            <span class="font-mono"
                                >{{ postsUsagePercentage }}%</span
                            >
                        </div>
                    </div>

                    <!-- 3. Connected Channels Quota -->
                    <div
                        class="flex items-center justify-between pt-0.5 text-[11px] text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5">
                            <Radio class="size-3 text-muted-foreground/80" />
                            <span>Channels Reach</span>
                        </span>
                        <span class="font-mono text-xs text-sidebar-foreground">
                            {{ channelUsage.connected }} /
                            {{ channelUsage.limit }}
                        </span>
                    </div>

                    <!-- 4. Dynamic Actions / State Banners (Flat) -->
                    <!-- Free Plan Full Upgrade Button -->
                    <div v-if="isFreePlan" class="pt-0.5">
                        <div
                            class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition group-hover/sub:bg-primary/90"
                        >
                            <Zap class="size-3.5 fill-current" />
                            <span>Upgrade to Pro</span>
                            <ArrowUpRight class="size-3.5" />
                        </div>
                    </div>

                    <!-- Cancelled State Warning Notice with Days Remaining -->
                    <div
                        v-else-if="isCancelled"
                        class="flex items-center justify-between rounded-md border border-amber-500/20 bg-amber-500/10 px-2 py-1.5 text-[11px] text-amber-700 dark:text-amber-400"
                    >
                        <span class="truncate"
                            >Cancels in {{ daysRemaining }} days</span
                        >
                        <span
                            class="ml-1 shrink-0 font-semibold underline hover:text-amber-800 dark:hover:text-amber-200"
                        >
                            Renew
                        </span>
                    </div>
                </Link>

                <!-- Collapsed State Icon Trigger -->
                <div class="hidden group-data-[collapsible=icon]:block">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                as-child
                                :tooltip="
                                    isCancelled
                                        ? `Subscription cancels in ${daysRemaining} days`
                                        : isFreePlan
                                          ? 'Free Plan — Upgrade to Pro'
                                          : `${currentSubscription.plan} (${postsQuota.used}/${postsQuota.limit} posts)`
                                "
                            >
                                <Link
                                    :href="`/w/${currentSlug}/settings?tab=billing`"
                                >
                                    <Zap
                                        :class="[
                                            'size-4',
                                            isCancelled
                                                ? 'text-amber-500'
                                                : 'text-primary',
                                        ]"
                                    />
                                    <span class="sr-only"
                                        >Billing & Subscription</span
                                    >
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </div>

                <SidebarSeparator />

                <!-- Settings at Bottom -->
                <SidebarMenu>
                    <!-- User Profile & Log Out Menu -->
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <SidebarMenuButton
                                    size="lg"
                                    class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                >
                                    <div
                                        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-xs font-semibold text-foreground"
                                    >
                                        <span v-if="currentUser.name">{{
                                            currentUser.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}</span>
                                        <UserIcon v-else class="size-4" />
                                    </div>
                                    <div
                                        class="grid min-w-0 flex-1 text-left text-sm leading-tight"
                                    >
                                        <span
                                            class="truncate font-semibold text-sidebar-foreground"
                                        >
                                            {{ currentUser.name }}
                                        </span>
                                        <span
                                            class="truncate text-xs text-sidebar-foreground/70"
                                        >
                                            {{ currentUser.email }}
                                        </span>
                                    </div>
                                    <ChevronsUpDown
                                        class="ml-auto size-4 shrink-0 text-sidebar-foreground/70"
                                    />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                                align="end"
                                :side-offset="4"
                            >
                                <DropdownMenuLabel class="p-0 font-normal">
                                    <div
                                        class="flex items-center gap-2 px-2 py-1.5 text-left text-sm"
                                    >
                                        <div
                                            class="flex size-7 items-center justify-center rounded-full border border-border bg-muted text-xs font-medium"
                                        >
                                            <span v-if="currentUser.name">{{
                                                currentUser.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}</span>
                                            <UserIcon v-else class="size-3.5" />
                                        </div>
                                        <div
                                            class="grid flex-1 text-left text-xs"
                                        >
                                            <span
                                                class="truncate font-semibold"
                                                >{{ currentUser.name }}</span
                                            >
                                            <span
                                                class="truncate text-muted-foreground"
                                                >{{ currentUser.email }}</span
                                            >
                                        </div>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="`/w/${currentSlug}/settings`"
                                        class="flex w-full cursor-pointer items-center gap-2"
                                    >
                                        <Settings class="size-4" />
                                        <span>Settings</span>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    @click="handleLogout"
                                    class="flex cursor-pointer items-center gap-2 text-destructive focus:text-destructive"
                                >
                                    <LogOut class="size-4" />
                                    <span>Log out</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>

        <!-- Sidebar Inset Main Application Area -->
        <SidebarInset>
            <!-- Global Channel Warning Banner -->
            <div
                v-if="workspaceStore.hasCriticalAccountWarning"
                class="border-b border-amber-500/20 bg-amber-500/10 px-4 py-2 text-xs text-amber-900 transition-all md:text-sm dark:text-amber-200"
            >
                <div
                    class="mx-auto flex max-w-7xl items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2 font-medium">
                        <AlertTriangle
                            class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <span>
                            <template
                                v-for="(
                                    acc, idx
                                ) in workspaceStore.expiringAccounts"
                                :key="acc.id"
                            >
                                <span>
                                    <strong>{{ acc.name }}</strong> ({{
                                        acc.provider
                                    }})
                                    <span v-if="acc.status === 'expiring'">
                                        token expires soon
                                    </span>
                                    <span v-else
                                        >token revoked / disconnected</span
                                    >
                                </span>
                                <span
                                    v-if="
                                        idx <
                                        workspaceStore.expiringAccounts.length -
                                            1
                                    "
                                    >,
                                </span>
                            </template>
                            — Re-authenticate to ensure scheduled posts publish
                            without interruption.
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="
                                handleRefreshAccount(
                                    workspaceStore.expiringAccounts[0]?.id,
                                )
                            "
                            class="inline-flex items-center gap-1.5 rounded bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-amber-700"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                            Refresh Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Header with Laravel Default SidebarTrigger & Breadcrumbs -->
            <header
                class="flex h-14 shrink-0 items-center justify-between border-b border-border bg-card/60 px-4 backdrop-blur-md md:px-6"
            >
                <div class="flex items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                    <Breadcrumbs :breadcrumbs="activeBreadcrumbs" />
                </div>

                <div class="flex items-center gap-3">
                    <!-- System Notifications Popover -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isNotificationsOpen = !isNotificationsOpen"
                            class="relative rounded-md p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            title="Platform Notifications"
                        >
                            <Bell class="h-4 w-4" />
                            <span
                                v-if="notifications.length > 0"
                                class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-card"
                            ></span>
                        </button>

                        <div
                            v-if="isNotificationsOpen"
                            class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-border bg-popover py-2 text-popover-foreground shadow-xl"
                        >
                            <div
                                class="flex items-center justify-between border-b border-border px-3 py-1.5"
                            >
                                <span
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    System Alerts
                                </span>
                                <span class="text-[11px] text-muted-foreground">
                                    {{ notifications.length }} active
                                </span>
                            </div>
                            <div
                                class="max-h-72 divide-y divide-border overflow-y-auto"
                            >
                                <div
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    class="flex items-start gap-2.5 p-3 transition hover:bg-muted/50"
                                >
                                    <AlertTriangle
                                        class="mt-0.5 h-4 w-4 shrink-0"
                                        :class="
                                            notif.type === 'error'
                                                ? 'text-rose-500'
                                                : 'text-amber-500'
                                        "
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-xs font-semibold text-foreground"
                                        >
                                            {{ notif.title }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[11px] leading-relaxed text-muted-foreground"
                                        >
                                            {{ notif.message }}
                                        </p>
                                        <p
                                            class="mt-1 font-mono text-[10px] text-muted-foreground/70"
                                        >
                                            {{ notif.time }}
                                        </p>
                                    </div>
                                    <button
                                        @click="dismissNotification(notif.id)"
                                        class="p-1 text-muted-foreground hover:text-foreground"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <div
                                    v-if="notifications.length === 0"
                                    class="p-4 text-center text-xs text-muted-foreground"
                                >
                                    No active system alerts. All queues running
                                    nominal.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page View Slot -->
            <main class="mx-auto w-full max-w-7xl flex-1 p-4 md:p-6">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
