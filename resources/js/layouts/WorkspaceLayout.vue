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
            plan: 'Growth Pro',
            status: 'active',
            seats: 5,
            billing_period: 'monthly',
            renews_at: '2026-10-15',
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
    } else if (page.component === 'workspace/Billing') {
        items.push({
            title: 'Billing & Invoices',
            href: `/w/${currentSlug.value}/settings/billing`,
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
                                <span
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
                <!-- Tenant Subscription Card -->
                <Link
                    :href="`/w/${currentSlug}/settings/billing`"
                    class="block cursor-pointer space-y-1.5 rounded-lg border border-sidebar-border bg-sidebar-accent/50 p-2.5 text-xs text-sidebar-foreground transition group-data-[collapsible=icon]:hidden hover:border-primary/40 hover:bg-sidebar-accent/80"
                    title="Manage Subscription & Billing"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-semibold">
                            <CreditCard class="size-3.5 text-primary" />
                            <span>{{ currentSubscription.plan }}</span>
                        </div>
                        <span
                            class="py-0.2 inline-flex items-center rounded bg-emerald-500/10 px-1.5 font-mono text-[10px] font-medium text-emerald-600 uppercase dark:text-emerald-400"
                        >
                            {{ currentSubscription.status }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span
                            >{{ currentSubscription.seats }} Seats
                            Included</span
                        >
                        <span class="capitalize"
                            >{{
                                currentSubscription.billing_period
                            }}
                            billing</span
                        >
                    </div>
                </Link>

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
                                        <UserIcon class="size-4" />
                                    </div>
                                    <div
                                        class="grid min-w-0 flex-1 text-left text-sm leading-tight"
                                    >
                                        <span
                                            class="truncate font-semibold text-sidebar-foreground"
                                        >
                                            Workspace Admin
                                        </span>
                                        <span
                                            class="truncate text-xs text-sidebar-foreground/70"
                                        >
                                            admin@example.com
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
                                            <UserIcon class="size-3.5" />
                                        </div>
                                        <div
                                            class="grid flex-1 text-left text-xs"
                                        >
                                            <span class="truncate font-semibold"
                                                >Workspace Admin</span
                                            >
                                            <span
                                                class="truncate text-muted-foreground"
                                                >admin@example.com</span
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
