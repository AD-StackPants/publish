<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { apiClient } from '@/api/client';
import {
    Building2,
    Clock,
    Copy,
    Check,
    Users,
    UserPlus,
    Shield,
    Sliders,
    Save,
    Trash2,
    AlertTriangle,
    Search,
    ChevronDown,
    Loader2,
    X,
} from '@lucide/vue';

export interface WorkspaceMember {
    id: string;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const props = withDefaults(
    defineProps<{
        members?: WorkspaceMember[];
        totalSeats?: number;
    }>(),
    {
        members: () => [],
        totalSeats: 5,
    },
);

const workspaceStore = useWorkspaceStore();

// Organization profile state
const orgName = ref(workspaceStore.currentOrg?.name || '');
const orgSlug = ref(workspaceStore.currentOrg?.slug || '');
const selectedTimezone = ref(workspaceStore.currentOrg?.timezone || 'UTC');
const timezoneSearch = ref('');
const isTimezoneDropdownOpen = ref(false);

const isSaving = ref(false);
const saveSuccess = ref(false);
const saveError = ref<string | null>(null);
const copiedId = ref(false);

// Team members state
const localMembers = ref<WorkspaceMember[]>([...props.members]);
const isInviteModalOpen = ref(false);
const inviteEmail = ref('');
const inviteRole = ref<'Admin' | 'Editor' | 'Contributor'>('Editor');
const isInviting = ref(false);
const inviteSuccess = ref(false);
const inviteError = ref<string | null>(null);
const isRemovingMember = ref<string | null>(null);

const isSeatLimitReached = computed(() => {
    return localMembers.value.length >= props.totalSeats;
});

const openInviteModal = () => {
    inviteEmail.value = '';
    inviteRole.value = 'Editor';
    inviteError.value = null;
    inviteSuccess.value = false;
    isInviteModalOpen.value = true;
};

const fetchMembers = async () => {
    if (!workspaceStore.currentOrg?.id) return;
    try {
        const res = await apiClient.get<WorkspaceMember[]>(
            `/organizations/${workspaceStore.currentOrg.id}/members`,
        );
        if (Array.isArray(res.data) && res.data.length > 0) {
            localMembers.value = res.data;
        }
    } catch {
        // Fallback to props
    }
};

onMounted(async () => {
    if (props.members && props.members.length > 0) {
        localMembers.value = [...props.members];
    } else {
        await fetchMembers();
    }
});

watch(
    () => props.members,
    (newMembers) => {
        if (newMembers && newMembers.length > 0) {
            localMembers.value = [...newMembers];
        }
    },
    { immediate: true },
);

// Publishing defaults state
const defaultPostGap = ref('15');
const autoUtmTracking = ref(true);
const autoRetryRateLimits = ref(true);
const requirePostApproval = ref(false);

// Danger zone state
const isDeleteModalOpen = ref(false);
const deleteConfirmationText = ref('');
const isDeleting = ref(false);

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

const currentTimeInZone = computed(() => {
    try {
        return new Intl.DateTimeFormat('en-US', {
            timeZone: selectedTimezone.value,
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
            timeZoneName: 'short',
        }).format(new Date());
    } catch {
        return 'Local time unavailable';
    }
});

const seatsUsed = computed(() => {
    return Math.max(localMembers.value.length, 1);
});

const seatsPercentage = computed(() => {
    return Math.min(
        100,
        Math.round((seatsUsed.value / props.totalSeats) * 100),
    );
});

const copyTenantId = async () => {
    if (!workspaceStore.currentOrg?.id) return;
    try {
        await navigator.clipboard.writeText(workspaceStore.currentOrg.id);
        copiedId.value = true;
        setTimeout(() => {
            copiedId.value = false;
        }, 2000);
    } catch {
        // clipboard fallback
    }
};

const handleSaveProfile = async () => {
    if (!orgName.value.trim()) return;
    isSaving.value = true;
    saveSuccess.value = false;
    saveError.value = null;

    try {
        await workspaceStore.updateOrgSettings({
            name: orgName.value.trim(),
            timezone: selectedTimezone.value,
        });
        saveSuccess.value = true;
        setTimeout(() => {
            saveSuccess.value = false;
        }, 3500);
    } catch (err) {
        saveError.value =
            'Failed to update organization profile. Please try again.';
        console.error('Failed to update workspace settings', err);
    } finally {
        isSaving.value = false;
    }
};

const handleSendInvite = async () => {
    const email = inviteEmail.value.trim().toLowerCase();
    if (!email || !email.includes('@')) {
        inviteError.value = 'Please enter a valid email address.';
        return;
    }

    if (localMembers.value.some((m) => m.email.toLowerCase() === email)) {
        inviteError.value = 'This user is already a member of this workspace.';
        return;
    }

    if (isSeatLimitReached.value) {
        inviteError.value = `Workspace seat limit of ${props.totalSeats} reached. Upgrade your plan or add seats in billing.`;
        return;
    }

    const orgId = workspaceStore.currentOrg?.id;
    if (!orgId) {
        inviteError.value = 'No active organization selected.';
        return;
    }

    isInviting.value = true;
    inviteError.value = null;

    try {
        const res = await apiClient.post<WorkspaceMember>(
            `/organizations/${orgId}/members`,
            {
                email,
                role: inviteRole.value,
            },
        );

        localMembers.value.push(res.data);
        inviteSuccess.value = true;
        inviteEmail.value = '';

        setTimeout(() => {
            inviteSuccess.value = false;
            isInviteModalOpen.value = false;
        }, 1200);
    } catch (err: unknown) {
        const axiosErr = err as {
            response?: {
                data?: {
                    message?: string;
                    errors?: Record<string, string[]>;
                };
            };
        };
        const msg =
            axiosErr.response?.data?.errors?.email?.[0] ||
            axiosErr.response?.data?.message ||
            'Failed to send workspace invitation. Please try again.';
        inviteError.value = msg;
    } finally {
        isInviting.value = false;
    }
};

const handleRemoveMember = async (id: string) => {
    if (
        !confirm(
            "Are you sure you want to revoke this member's access to the workspace?",
        )
    ) {
        return;
    }

    const orgId = workspaceStore.currentOrg?.id;
    if (!orgId) return;

    isRemovingMember.value = id;
    try {
        await apiClient.delete(`/organizations/${orgId}/members/${id}`);
        localMembers.value = localMembers.value.filter((m) => m.id !== id);
    } catch (err: unknown) {
        const axiosErr = err as {
            response?: {
                data?: {
                    message?: string;
                };
            };
        };
        alert(
            axiosErr.response?.data?.message ||
                'Failed to remove member. Please try again.',
        );
    } finally {
        isRemovingMember.value = null;
    }
};
</script>

<template>
    <div class="space-y-8">
        <!-- Toast feedback -->
        <div
            v-if="saveSuccess"
            class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-800 transition-all dark:text-emerald-200"
        >
            <Check
                class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
            />
            <span
                >Workspace profile and timezone preferences saved
                successfully.</span
            >
        </div>

        <div
            v-if="saveError"
            class="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-xs font-semibold text-destructive transition-all"
        >
            <AlertTriangle class="h-4 w-4 shrink-0" />
            <span>{{ saveError }}</span>
        </div>

        <!-- 1. Workspace Profile & Identity -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div
                class="flex items-center justify-between border-b border-border pb-4"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Building2 class="h-4 w-4 text-primary" />
                        <h2 class="text-sm font-bold text-foreground">
                            Organization Profile
                        </h2>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Manage your workspace branding, slug identifier, and
                        scheduling dispatch timezone.
                    </p>
                </div>

                <!-- Tenant UUID badge with copy button -->
                <button
                    type="button"
                    @click="copyTenantId"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-border bg-muted/60 px-2.5 py-1 font-mono text-[11px] text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    title="Click to copy Workspace UUID"
                >
                    <span
                        >ID:
                        {{
                            workspaceStore.currentOrg?.id
                                ? workspaceStore.currentOrg.id.slice(0, 8) +
                                  '...'
                                : '...'
                        }}</span
                    >
                    <Check
                        v-if="copiedId"
                        class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                    />
                    <Copy v-else class="h-3 w-3" />
                </button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Org Name -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-foreground">
                        Organization / Workspace Name
                    </label>
                    <input
                        v-model="orgName"
                        type="text"
                        placeholder="e.g. Acme Studio"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs transition focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                    />
                    <p class="text-[11px] text-muted-foreground">
                        Displayed across your team switcher and publishing
                        previews.
                    </p>
                </div>

                <!-- Org Slug -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-foreground">
                        Workspace Slug (URL namespace)
                    </label>
                    <div class="flex items-center">
                        <span
                            class="rounded-l-lg border border-r-0 border-border bg-muted px-3 py-2 font-mono text-xs text-muted-foreground"
                        >
                            /w/
                        </span>
                        <input
                            v-model="orgSlug"
                            type="text"
                            disabled
                            class="flex-1 cursor-not-allowed rounded-r-lg border border-border bg-muted/40 px-3 py-2 font-mono text-xs text-muted-foreground"
                            title="Workspace slug is established during onboarding"
                        />
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Workspace path identifier used in all dashboard routes.
                    </p>
                </div>
            </div>

            <!-- Timezone selector -->
            <div class="mt-6 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-foreground">
                        Default Scheduling Timezone
                    </label>
                    <span class="font-mono text-[11px] text-muted-foreground">
                        Current time:
                        <strong class="text-foreground">{{
                            currentTimeInZone
                        }}</strong>
                    </span>
                </div>
                <p class="text-[11px] text-muted-foreground">
                    All scheduled social posts will calculate publication
                    dispatch times against this timezone.
                </p>

                <div class="relative max-w-md">
                    <button
                        type="button"
                        @click="
                            isTimezoneDropdownOpen = !isTimezoneDropdownOpen
                        "
                        class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-border bg-background px-3 py-2.5 text-left text-xs transition hover:bg-accent/40 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                    >
                        <span class="flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                            <span class="font-medium text-foreground">{{
                                selectedTimezone
                            }}</span>
                        </span>
                        <ChevronDown
                            class="h-3.5 w-3.5 text-muted-foreground transition-transform"
                            :class="{ 'rotate-180': isTimezoneDropdownOpen }"
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
                                placeholder="Search timezone (e.g. Tokyo, London)..."
                                class="w-full rounded-md border border-border bg-background py-1.5 pr-2 pl-8 text-xs focus:ring-1 focus:ring-primary focus:outline-none"
                                @click.stop
                            />
                        </div>

                        <div
                            class="max-h-52 divide-y divide-border/60 overflow-y-auto"
                        >
                            <button
                                v-for="tz in filteredTimezones"
                                :key="tz"
                                type="button"
                                @click="
                                    selectedTimezone = tz;
                                    isTimezoneDropdownOpen = false;
                                "
                                class="flex w-full cursor-pointer items-center justify-between rounded px-2.5 py-1.5 text-left text-xs transition hover:bg-accent"
                                :class="
                                    selectedTimezone === tz
                                        ? 'bg-accent font-semibold text-primary'
                                        : 'text-foreground'
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

            <!-- Save action button -->
            <div class="mt-6 flex justify-end border-t border-border pt-4">
                <button
                    type="button"
                    @click="handleSaveProfile"
                    :disabled="isSaving"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                >
                    <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                    <Save v-else class="h-3.5 w-3.5" />
                    <span>{{
                        isSaving ? 'Saving Changes...' : 'Save Profile Changes'
                    }}</span>
                </button>
            </div>
        </section>

        <!-- 2. Team Members & Seats Allocation -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div
                class="flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Users class="h-4 w-4 text-primary" />
                        <h2 class="text-sm font-bold text-foreground">
                            Team Members & Seat Quota
                        </h2>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Collaborate on publishing, drafting, and social campaign
                        approvals.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openInviteModal"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs transition hover:bg-accent"
                >
                    <UserPlus class="h-3.5 w-3.5 text-primary" />
                    <span>Invite Member</span>
                </button>
            </div>

            <!-- Seat Quota Progress Meter -->
            <div
                class="mt-5 rounded-lg border border-border/80 bg-muted/30 p-4"
            >
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-foreground">
                        Seat Utilization:
                        <strong class="text-primary"
                            >{{ seatsUsed }} of
                            {{ props.totalSeats }} Seats</strong
                        >
                    </span>
                    <span class="font-mono text-xs text-muted-foreground"
                        >{{ seatsPercentage }}% Allocated</span
                    >
                </div>
                <div
                    class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-all duration-300"
                        :style="{ width: `${seatsPercentage}%` }"
                    />
                </div>
            </div>

            <!-- Members List Table -->
            <div
                class="mt-5 divide-y divide-border overflow-hidden rounded-lg border border-border"
            >
                <div
                    v-for="member in localMembers"
                    :key="member.id"
                    class="flex items-center justify-between bg-background p-3.5 transition hover:bg-muted/30"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary"
                        >
                            {{ member.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-semibold text-foreground"
                                    >{{ member.name }}</span
                                >
                                <span
                                    class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                                    :class="
                                        member.role === 'Owner'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border border-border bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ member.role }}
                                </span>
                            </div>
                            <span class="text-[11px] text-muted-foreground">{{
                                member.email
                            }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="hidden text-[11px] text-muted-foreground sm:inline"
                        >
                            Joined {{ member.created_at }}
                        </span>
                        <button
                            v-if="member.role !== 'Owner'"
                            type="button"
                            @click="handleRemoveMember(member.id)"
                            :disabled="isRemovingMember === member.id"
                            class="cursor-pointer text-xs text-destructive hover:underline disabled:opacity-50"
                        >
                            {{
                                isRemovingMember === member.id
                                    ? 'Removing...'
                                    : 'Remove'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Social Publishing Defaults -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <Sliders class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Publishing & Delivery Defaults
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Configure universal automation defaults applied across
                        all scheduled posts.
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-5">
                <!-- Minimum Dispatch Interval -->
                <div
                    class="flex flex-col gap-2 border-b border-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Queue Dispatch Gap</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Minimum spacing interval between sequential posts on
                            the same social network.
                        </span>
                    </div>
                    <select
                        v-model="defaultPostGap"
                        class="rounded-lg border border-border bg-background px-3 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-none"
                    >
                        <option value="5">5 Minutes</option>
                        <option value="15">15 Minutes (Recommended)</option>
                        <option value="30">30 Minutes</option>
                        <option value="60">1 Hour</option>
                    </select>
                </div>

                <!-- UTM Campaign Auto-Tagging -->
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Auto-Append Campaign UTM Tags</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Automatically appends utm_source, utm_medium, and
                            campaign identifiers to outbound link attachments.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="autoUtmTracking = !autoUtmTracking"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="autoUtmTracking ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                autoUtmTracking
                                    ? 'translate-x-4'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <!-- Auto-Retry on Rate Limits -->
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Auto-Retry Rate Limited Posts</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            When an external API (e.g. Meta Graph or X) issues
                            429 Too Many Requests, automatically reschedule with
                            jitter.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="autoRetryRateLimits = !autoRetryRateLimits"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="autoRetryRateLimits ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                autoRetryRateLimits
                                    ? 'translate-x-4'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <!-- Approval Workflow -->
                <div class="flex items-center justify-between">
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Require Admin Approval on Scheduled Posts</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Contributors can stage posts, but an Owner or Admin
                            must approve them before publication.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="requirePostApproval = !requirePostApproval"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="requirePostApproval ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                requirePostApproval
                                    ? 'translate-x-4'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>
            </div>
        </section>

        <!-- 4. Organization Danger Zone -->
        <section
            class="rounded-xl border border-destructive/20 bg-destructive/5 p-6 shadow-xs"
        >
            <div
                class="flex items-center gap-2 border-b border-destructive/20 pb-3"
            >
                <AlertTriangle class="h-4 w-4 text-destructive" />
                <h2 class="text-sm font-bold text-destructive">
                    Workspace Danger Zone
                </h2>
            </div>

            <div
                class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-0.5">
                    <span class="block text-xs font-semibold text-foreground"
                        >Delete Workspace</span
                    >
                    <span class="text-[11px] text-muted-foreground">
                        Permanently purge all connected social accounts,
                        scheduled posts, and historical media for this tenant.
                    </span>
                </div>
                <button
                    type="button"
                    @click="isDeleteModalOpen = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-destructive/30 bg-destructive px-3.5 py-2 text-xs font-semibold text-destructive-foreground shadow-xs transition hover:bg-destructive/90"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Delete Workspace</span>
                </button>
            </div>
        </section>

        <!-- Invite Member Modal -->
        <div
            v-if="isInviteModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md space-y-4 rounded-xl border border-border bg-card p-6 shadow-xl"
            >
                <div
                    class="flex items-center justify-between border-b border-border pb-3"
                >
                    <h3 class="text-sm font-bold text-foreground">
                        Invite Team Member
                    </h3>
                    <button
                        type="button"
                        @click="isInviteModalOpen = false"
                        class="cursor-pointer text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Toast / Alert States -->
                <div
                    v-if="inviteSuccess"
                    class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-800 dark:text-emerald-200"
                >
                    Invitation sent successfully!
                </div>

                <div
                    v-if="inviteError"
                    class="flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 p-3 text-xs font-semibold text-destructive"
                >
                    <AlertTriangle class="h-4 w-4 shrink-0" />
                    <span>{{ inviteError }}</span>
                </div>

                <!-- Seat Limit Warning Notice -->
                <div
                    v-if="isSeatLimitReached"
                    class="rounded-lg border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300"
                >
                    <p class="font-semibold">Seat Quota Reached</p>
                    <p
                        class="mt-0.5 text-[11px] text-amber-700 dark:text-amber-400"
                    >
                        All {{ props.totalSeats }} seats are currently
                        allocated. To add more teammates, please add seats or
                        upgrade in Billing.
                    </p>
                    <div class="mt-2">
                        <Link
                            :href="`/w/${workspaceStore.currentOrg?.slug || 'workspace'}/settings?tab=billing`"
                            class="inline-flex items-center gap-1 font-semibold text-primary underline"
                        >
                            Manage Seats in Billing &rarr;
                        </Link>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Email Address</label
                        >
                        <input
                            v-model="inviteEmail"
                            type="email"
                            placeholder="colleague@company.com"
                            @keyup.enter="handleSendInvite"
                            :disabled="isInviting || isSeatLimitReached"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs focus:ring-1 focus:ring-primary focus:outline-none disabled:opacity-50"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Assigned Role</label
                        >
                        <select
                            v-model="inviteRole"
                            :disabled="isInviting || isSeatLimitReached"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs focus:ring-1 focus:ring-primary focus:outline-none disabled:opacity-50"
                        >
                            <option value="Admin">
                                Admin (Full channel & billing access)
                            </option>
                            <option value="Editor">
                                Editor (Create, schedule, & publish)
                            </option>
                            <option value="Contributor">
                                Contributor (Draft posts only)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-border pt-2">
                    <button
                        type="button"
                        @click="isInviteModalOpen = false"
                        class="cursor-pointer rounded-lg border border-border px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleSendInvite"
                        :disabled="
                            isInviting || !inviteEmail || isSeatLimitReached
                        "
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isInviting"
                            class="h-3 w-3 animate-spin"
                        />
                        <span>{{
                            isInviting ? 'Sending...' : 'Send Invitation'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="isDeleteModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md space-y-4 rounded-xl border border-destructive/30 bg-card p-6 shadow-xl"
            >
                <div class="flex items-center gap-2 text-destructive">
                    <AlertTriangle class="h-5 w-5" />
                    <h3 class="text-sm font-bold">
                        Confirm Workspace Deletion
                    </h3>
                </div>

                <p class="text-xs text-muted-foreground">
                    This action cannot be undone. All social tokens, scheduled
                    posts, and historical analytics will be deleted.
                </p>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-foreground"
                    >
                        Please type
                        <strong class="font-mono text-destructive">{{
                            orgSlug
                        }}</strong>
                        to confirm:
                    </label>
                    <input
                        v-model="deleteConfirmationText"
                        type="text"
                        :placeholder="orgSlug"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs focus:ring-1 focus:ring-destructive focus:outline-none"
                    />
                </div>

                <div class="flex justify-end gap-2 border-t border-border pt-2">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        class="cursor-pointer rounded-lg border border-border px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="deleteConfirmationText !== orgSlug"
                        class="cursor-pointer rounded-lg bg-destructive px-4 py-1.5 text-xs font-semibold text-destructive-foreground hover:bg-destructive/90 disabled:opacity-40"
                    >
                        Permanently Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
