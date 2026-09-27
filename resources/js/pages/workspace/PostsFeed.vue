<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link, usePage, Head } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { apiClient } from '@/api/client';
import PostInspectionDrawer from '@/components/PostInspectionDrawer.vue';
import type { Post } from '@/types/workspace';
import {
    Search,
    Calendar as CalendarIcon,
    Send,
    ExternalLink,
    Copy,
    Trash2,
    Clock,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Layers,
    Plus,
    Filter,
    RotateCcw,
    X,
    FileText,
    LayoutList,
    CalendarDays,
    Radio,
    Sparkles,
    Eye,
    ChevronLeft,
    ChevronRight,
    Share2,
    Check,
} from '@lucide/vue';

const page = usePage();
const workspaceStore = useWorkspaceStore();

const allPosts = ref<Post[]>([]);
const isLoading = ref(false);

// View mode: 'list' (Queue) or 'calendar' (Week Grid)
const viewMode = ref<'list' | 'calendar'>('list');

// Filters
const activeStatusFilter = ref<string>('all');
const activePlatformFilter = ref<string>('all');
const searchQuery = ref<string>('');

// Inspection Drawer State
const isDrawerOpen = ref(false);
const inspectedPost = ref<Post | null>(null);

// Reschedule Dialog State
const isRescheduleOpen = ref(false);
const postToReschedule = ref<Post | null>(null);
const newScheduleDate = ref('');
const newScheduleTime = ref('10:00');

// Status Tabs for SMM
const statusTabs = [
    { label: 'All Posts', value: 'all' },
    { label: 'Scheduled Queue', value: 'scheduled' },
    { label: 'Published', value: 'published' },
    { label: 'Drafts', value: 'draft' },
    { label: 'Needs Attention', value: 'failed' },
];

const platformOptions = [
    { label: 'All Networks', value: 'all' },
    { label: 'X / Twitter', value: 'twitter' },
    { label: 'LinkedIn', value: 'linkedin' },
    { label: 'Facebook', value: 'facebook' },
];

// Computed Filtered Posts (Instant reactive status, platform & search filtering)
const posts = computed(() => {
    let result = [...allPosts.value];

    // Status filter
    if (activeStatusFilter.value && activeStatusFilter.value !== 'all') {
        if (activeStatusFilter.value === 'failed') {
            result = result.filter(
                (p) => p.status === 'partial_failure' || p.status === 'dlq',
            );
        } else {
            result = result.filter((p) => p.status === activeStatusFilter.value);
        }
    }

    // Platform filter
    if (activePlatformFilter.value && activePlatformFilter.value !== 'all') {
        const matchingAccIds = workspaceStore.accounts
            .filter((a) => a.provider === activePlatformFilter.value)
            .map((a) => a.id);
        result = result.filter((p) =>
            p.target_account_ids?.some((id) => matchingAccIds.includes(id)),
        );
    }

    // Search query
    if (searchQuery.value && searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(
            (p) =>
                p.content.toLowerCase().includes(q) ||
                p.platform_overrides?.twitter?.content
                    ?.toLowerCase()
                    .includes(q) ||
                p.platform_overrides?.linkedin?.content
                    ?.toLowerCase()
                    .includes(q) ||
                p.platform_overrides?.facebook?.content
                    ?.toLowerCase()
                    .includes(q),
        );
    }

    return result;
});

// Quick metrics for SMM Dashboard (based on allPosts so totals remain constant during filtering)
const totalScheduled = computed(
    () => allPosts.value.filter((p) => p.status === 'scheduled').length,
);
const totalPublished = computed(
    () => allPosts.value.filter((p) => p.status === 'published').length,
);
const totalNeedsAttention = computed(
    () =>
        allPosts.value.filter(
            (p) => p.status === 'partial_failure' || p.status === 'dlq',
        ).length,
);
const nextScheduledPost = computed(() => {
    const scheduled = allPosts.value
        .filter((p) => p.status === 'scheduled' && p.scheduled_at)
        .sort(
            (a, b) =>
                new Date(a.scheduled_at!).getTime() -
                new Date(b.scheduled_at!).getTime(),
        );
    return scheduled[0] || null;
});

const currentSlug = computed(
    () =>
        (page.props.tenant_slug as string) ||
        workspaceStore.activeOrgSlug ||
        'acme-studio',
);

const fetchPosts = async () => {
    isLoading.value = true;
    try {
        const res = await apiClient.get<Post[]>('/posts');
        allPosts.value = Array.isArray(res.data) ? res.data : [];
    } catch (err) {
        console.error('Failed to load posts', err);
        allPosts.value = [];
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => workspaceStore.activeOrgId,
    () => {
        fetchPosts();
    },
);

onMounted(() => {
    fetchPosts();
});

const openInspection = (post: Post) => {
    inspectedPost.value = post;
    isDrawerOpen.value = true;
};

const handlePostUpdated = (updated: Post) => {
    const idx = allPosts.value.findIndex((p) => p.id === updated.id);
    if (idx !== -1) {
        allPosts.value[idx] = updated;
    }
    if (inspectedPost.value?.id === updated.id) {
        inspectedPost.value = updated;
    }
};

const duplicateToComposer = (post: Post) => {
    const draft = {
        content: post.content,
        selectedAccountIds: post.target_account_ids || [],
        mediaUrl: post.media_url || '',
        linkMetadata: post.link_metadata || null,
        hasTwitterOverride: !!post.platform_overrides?.twitter?.content,
        twitterContent: post.platform_overrides?.twitter?.content || '',
        hasLinkedInOverride: !!post.platform_overrides?.linkedin?.content,
        linkedinContent: post.platform_overrides?.linkedin?.content || '',
        isScheduled: false,
        savedAt: new Date().toLocaleTimeString(),
    };
    localStorage.setItem('workspace_composer_draft', JSON.stringify(draft));
    router.visit(`/w/${currentSlug.value}/composer`);
};

const openReschedule = (post: Post) => {
    postToReschedule.value = post;
    if (post.scheduled_at) {
        const d = new Date(post.scheduled_at);
        newScheduleDate.value = d.toISOString().split('T')[0];
        newScheduleTime.value = d.toTimeString().split(' ')[0].slice(0, 5);
    } else {
        const d = new Date(Date.now() + 24 * 60 * 60 * 1000);
        newScheduleDate.value = d.toISOString().split('T')[0];
    }
    isRescheduleOpen.value = true;
};

const setSchedulePreset = (hoursFromNow: number) => {
    const d = new Date(Date.now() + hoursFromNow * 60 * 60 * 1000);
    newScheduleDate.value = d.toISOString().split('T')[0];
    newScheduleTime.value = '10:00';
};

const submitReschedule = async () => {
    if (!postToReschedule.value || !newScheduleDate.value) return;
    const scheduledAt = new Date(
        `${newScheduleDate.value}T${newScheduleTime.value || '12:00'}:00`,
    ).toISOString();
    try {
        const res = await apiClient.patch<Post>(
            `/posts/${postToReschedule.value.id}`,
            {
                scheduled_at: scheduledAt,
                status: 'scheduled',
            },
        );
        handlePostUpdated(res.data);
        isRescheduleOpen.value = false;
    } catch (err: any) {
        alert(err?.response?.data?.message || 'Failed to reschedule post');
    }
};

const deletePost = async (id: string) => {
    if (!confirm('Are you sure you want to permanently delete this post?'))
        return;
    try {
        await apiClient.delete(`/posts/${id}`);
        allPosts.value = allPosts.value.filter((p) => p.id !== id);
    } catch (err: any) {
        alert(err?.response?.data?.message || 'Failed to delete post');
    }
};

const formatFriendlyDate = (dateStr?: string | null) => {
    if (!dateStr) return 'Unscheduled Draft';
    const d = new Date(dateStr);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();

    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 1);
    const isTomorrow = d.toDateString() === tomorrow.toDateString();

    const timeStr = d.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });

    if (isToday) return `Today at ${timeStr}`;
    if (isTomorrow) return `Tomorrow at ${timeStr}`;

    return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} at ${timeStr}`;
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'published':
            return {
                label: 'Published',
                class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            };
        case 'scheduled':
            return {
                label: 'Scheduled',
                class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            };
        case 'partial_failure':
            return {
                label: 'Partial Delivery',
                class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            };
        case 'dlq':
            return {
                label: 'Needs Attention',
                class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            };
        default:
            return {
                label: 'Draft',
                class: 'bg-muted text-muted-foreground border-border',
            };
    }
};

// Interactive Calendar State (Week & Month views)
const calendarViewType = ref<'week' | 'month'>('week');
const calendarOffsetWeeks = ref(0);
const calendarOffsetMonths = ref(0);

const getMonday = (d: Date) => {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(date.setDate(diff));
};

const currentMonday = computed(() => {
    const today = new Date();
    const monday = getMonday(today);
    monday.setDate(monday.getDate() + calendarOffsetWeeks.value * 7);
    monday.setHours(0, 0, 0, 0);
    return monday;
});

const weekDays = computed(() => {
    const days = [];
    const mon = new Date(currentMonday.value);
    const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    for (let i = 0; i < 7; i++) {
        const d = new Date(mon);
        d.setDate(mon.getDate() + i);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const dayNum = String(d.getDate()).padStart(2, '0');
        const fullDate = `${year}-${month}-${dayNum}`;
        const monthName = d.toLocaleDateString('en-US', { month: 'short' });
        const isToday = new Date().toDateString() === d.toDateString();

        days.push({
            name: dayNames[i],
            date: `${monthName} ${d.getDate()}`,
            fullDate,
            isToday,
            dateObj: d,
        });
    }
    return days;
});

const monthDays = computed(() => {
    const today = new Date();
    const target = new Date(
        today.getFullYear(),
        today.getMonth() + calendarOffsetMonths.value,
        1,
    );
    const year = target.getFullYear();
    const month = target.getMonth();

    const firstDay = new Date(year, month, 1);
    const dayOfWeek = firstDay.getDay(); // 0 is Sun, 1 is Mon
    const startOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

    const startDate = new Date(year, month, 1 - startOffset);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const totalCells = startOffset + daysInMonth > 35 ? 42 : 35;

    const days = [];
    for (let i = 0; i < totalCells; i++) {
        const d = new Date(startDate);
        d.setDate(startDate.getDate() + i);

        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dayNum = String(d.getDate()).padStart(2, '0');
        const fullDate = `${y}-${m}-${dayNum}`;
        const isCurrentMonth = d.getMonth() === month;
        const isToday = today.toDateString() === d.toDateString();

        days.push({
            dayNumber: d.getDate(),
            fullDate,
            isCurrentMonth,
            isToday,
            dateObj: d,
        });
    }
    return days;
});

const monthHeaderNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const calendarHeaderTitle = computed(() => {
    if (calendarViewType.value === 'week') {
        const days = weekDays.value;
        if (days.length === 0) return '';
        const first = days[0];
        const last = days[6];
        const year = first.dateObj.getFullYear();
        return `Week of ${first.date} - ${last.date}, ${year}`;
    } else {
        const today = new Date();
        const target = new Date(
            today.getFullYear(),
            today.getMonth() + calendarOffsetMonths.value,
            1,
        );
        return target.toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric',
        });
    }
});

const prevPeriod = () => {
    if (calendarViewType.value === 'week') {
        calendarOffsetWeeks.value--;
    } else {
        calendarOffsetMonths.value--;
    }
};

const nextPeriod = () => {
    if (calendarViewType.value === 'week') {
        calendarOffsetWeeks.value++;
    } else {
        calendarOffsetMonths.value++;
    }
};

const resetToCurrent = () => {
    calendarOffsetWeeks.value = 0;
    calendarOffsetMonths.value = 0;
};

const isNavigatedAway = computed(() => {
    return calendarViewType.value === 'week'
        ? calendarOffsetWeeks.value !== 0
        : calendarOffsetMonths.value !== 0;
});

const formatPostTime = (dateStr?: string | null) => {
    if (!dateStr) return '10:00 AM';
    try {
        const d = new Date(dateStr);
        return d.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    } catch {
        return '10:00 AM';
    }
};

const getPostsForDay = (dayIsoDate: string) => {
    return posts.value.filter((post) => {
        const targetDate = post.scheduled_at || post.created_at;
        if (!targetDate) return false;
        try {
            const d = new Date(targetDate);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const localIso = `${year}-${month}-${day}`;
            return localIso === dayIsoDate;
        } catch {
            return false;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Posts & Schedule" />
        <h1 class="sr-only">Posts & Schedule</h1>

        <!-- Streamlined Action Toolbar -->
        <div class="flex items-center justify-between gap-3">
            <!-- View Switcher: List vs Calendar -->
            <div
                class="flex items-center rounded-lg border border-border bg-card p-0.5 shadow-xs"
            >
                <button
                    type="button"
                    @click="viewMode = 'list'"
                    class="flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold transition"
                    :class="
                        viewMode === 'list'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    <LayoutList class="size-3.5" />
                    <span>Queue</span>
                </button>
                <button
                    type="button"
                    @click="viewMode = 'calendar'"
                    class="flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold transition"
                    :class="
                        viewMode === 'calendar'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    <CalendarDays class="size-3.5" />
                    <span>Calendar</span>
                </button>
            </div>

            <!-- Create Post CTA -->
            <Link
                :href="`/w/${currentSlug}/composer`"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90"
            >
                <Plus class="size-3.5" />
                <span>Create Post</span>
            </Link>
        </div>

        <!-- SMM KPI Stat Tiles -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <!-- 1. Scheduled in Queue -->
            <div
                class="space-y-1 rounded-xl border border-border bg-card p-4 shadow-xs"
            >
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-medium">Scheduled Queue</span>
                    <Clock class="size-4 text-primary" />
                </div>
                <p class="font-mono text-2xl font-bold text-foreground">
                    {{ totalScheduled }}
                </p>
                <p class="truncate text-[11px] text-muted-foreground">
                    {{
                        nextScheduledPost
                            ? `Next: ${formatFriendlyDate(nextScheduledPost.scheduled_at)}`
                            : 'No upcoming posts'
                    }}
                </p>
            </div>

            <!-- 2. Published Live -->
            <div
                class="space-y-1 rounded-xl border border-border bg-card p-4 shadow-xs"
            >
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-medium">Published Live</span>
                    <CheckCircle2 class="size-4 text-emerald-500" />
                </div>
                <p class="font-mono text-2xl font-bold text-foreground">
                    {{ totalPublished }}
                </p>
                <p
                    class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                >
                    Active on all channels
                </p>
            </div>

            <!-- 3. Needs Attention -->
            <div
                class="space-y-1 rounded-xl border p-4 shadow-xs"
                :class="
                    totalNeedsAttention > 0
                        ? 'border-amber-500/30 bg-amber-500/5'
                        : 'border-border bg-card'
                "
            >
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-medium">Needs Review</span>
                    <AlertTriangle class="size-4 text-amber-500" />
                </div>
                <p class="font-mono text-2xl font-bold text-foreground">
                    {{ totalNeedsAttention }}
                </p>
                <p class="text-[11px] text-muted-foreground">
                    {{
                        totalNeedsAttention > 0
                            ? 'Retry available'
                            : 'All channels healthy'
                    }}
                </p>
            </div>

            <!-- 4. Connected Channels -->
            <div
                class="space-y-1 rounded-xl border border-border bg-card p-4 shadow-xs"
            >
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-medium">Connected Accounts</span>
                    <Radio class="size-4 text-primary" />
                </div>
                <p class="font-mono text-2xl font-bold text-foreground">
                    {{ workspaceStore.accounts.length }}
                </p>
                <Link
                    :href="`/w/${currentSlug}/channels`"
                    class="block truncate text-[11px] font-semibold text-primary hover:underline"
                >
                    Manage Profiles &rarr;
                </Link>
            </div>
        </div>

        <!-- Filter Controls Bar -->
        <div
            class="space-y-3 rounded-xl border border-border bg-card p-4 shadow-xs"
        >
            <div
                class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
            >
                <!-- Search -->
                <div class="relative max-w-md flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search posts by message text, topics, hashtags..."
                        class="w-full rounded-lg border border-border bg-background py-1.5 pr-3 pl-9 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                    />
                </div>

                <!-- Network Filter -->
                <div class="flex items-center gap-2">
                    <Filter class="size-3.5 text-muted-foreground" />
                    <select
                        v-model="activePlatformFilter"
                        class="rounded-lg border border-border bg-background px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                    >
                        <option
                            v-for="opt in platformOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Status Tabs -->
            <div
                class="flex flex-wrap items-center gap-1 border-t border-border/60 pt-3"
            >
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    type="button"
                    @click="activeStatusFilter = tab.value"
                    class="cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold transition select-none"
                    :class="
                        activeStatusFilter === tab.value
                            ? 'border border-primary/20 bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- View 1: Queue / List View -->
        <div
            v-if="viewMode === 'list'"
            class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
        >
            <div
                v-if="isLoading"
                class="p-8 text-center text-xs text-muted-foreground"
            >
                <span
                    class="mr-2 inline-block size-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                ></span>
                Loading scheduled social content...
            </div>

            <div
                v-else-if="posts.length === 0"
                class="space-y-3 p-12 text-center"
            >
                <FileText
                    class="mx-auto size-8 text-muted-foreground opacity-60"
                />
                <p class="text-sm font-semibold text-foreground">
                    No posts found
                </p>
                <p class="mx-auto max-w-sm text-xs text-muted-foreground">
                    Create your first multi-channel broadcast or adjust your
                    search filter.
                </p>
                <Link
                    :href="`/w/${currentSlug}/composer`"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <Plus class="size-3.5" />
                    <span>Create Post</span>
                </Link>
            </div>

            <div v-else class="divide-y divide-border">
                <div
                    v-for="post in posts"
                    :key="post.id"
                    class="flex flex-col justify-between gap-4 p-4 transition hover:bg-muted/20 md:flex-row md:items-center md:p-5"
                >
                    <!-- Post Content & Media Thumbnail -->
                    <div class="flex min-w-0 flex-1 items-start gap-3.5">
                        <!-- Media Thumbnail -->
                        <div
                            v-if="post.media_url"
                            class="size-14 shrink-0 cursor-pointer overflow-hidden rounded-lg border border-border bg-muted/40 shadow-2xs"
                            @click="openInspection(post)"
                        >
                            <img
                                :src="post.media_url"
                                alt="Media"
                                class="size-full object-cover"
                            />
                        </div>
                        <div
                            v-else-if="post.link_metadata?.image_url"
                            class="size-14 shrink-0 cursor-pointer overflow-hidden rounded-lg border border-border bg-muted/40 shadow-2xs"
                            @click="openInspection(post)"
                        >
                            <img
                                :src="post.link_metadata.image_url"
                                alt="OG"
                                class="size-full object-cover"
                            />
                        </div>
                        <div
                            v-else
                            class="flex size-14 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/30 text-muted-foreground"
                        >
                            <FileText class="size-5 opacity-60" />
                        </div>

                        <!-- Snippet & Details -->
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Status Badge -->
                                <span
                                    class="rounded-md border px-2 py-0.5 font-mono text-[10px] font-bold tracking-wider uppercase shadow-xs"
                                    :class="getStatusBadge(post.status).class"
                                >
                                    {{ getStatusBadge(post.status).label }}
                                </span>

                                <!-- Timestamp -->
                                <span
                                    class="flex items-center gap-1 text-xs font-medium text-muted-foreground"
                                >
                                    <Clock class="size-3" />
                                    <span>{{
                                        formatFriendlyDate(
                                            post.scheduled_at ||
                                                post.created_at,
                                        )
                                    }}</span>
                                </span>
                            </div>

                            <!-- Post Text Snippet -->
                            <p
                                @click="openInspection(post)"
                                class="line-clamp-2 cursor-pointer text-xs leading-relaxed font-medium text-foreground underline-offset-2 hover:underline"
                            >
                                {{ post.content }}
                            </p>

                            <!-- Social Network Badges & Live Links -->
                            <div
                                class="flex flex-wrap items-center gap-1.5 pt-0.5"
                            >
                                <div
                                    v-for="accId in post.target_account_ids ||
                                    []"
                                    :key="accId"
                                    class="inline-flex items-center gap-1 rounded-md border border-border/80 bg-muted/60 px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground capitalize"
                                >
                                    <span>{{
                                        accId.replace('acc-', '').split('-')[0]
                                    }}</span>
                                    <CheckCircle2
                                        v-if="post.status === 'published'"
                                        class="size-2.5 text-emerald-500"
                                    />
                                    <Clock
                                        v-else-if="post.status === 'scheduled'"
                                        class="size-2.5 text-blue-500"
                                    />
                                    <AlertTriangle
                                        v-else-if="
                                            post.status === 'partial_failure' ||
                                            post.status === 'dlq'
                                        "
                                        class="size-2.5 text-amber-500"
                                    />
                                </div>

                                <!-- Live Permalinks Button -->
                                <template v-if="post.live_urls">
                                    <a
                                        v-for="(
                                            url, provider
                                        ) in post.live_urls"
                                        :key="provider"
                                        :href="url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="ml-1 inline-flex items-center gap-0.5 text-[10px] font-semibold text-primary hover:underline"
                                    >
                                        <span>View on {{ provider }}</span>
                                        <ExternalLink class="size-2.5" />
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Actions -->
                    <div
                        class="flex items-center gap-1.5 self-end md:self-center"
                    >
                        <button
                            type="button"
                            @click="openInspection(post)"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-foreground shadow-xs transition hover:bg-muted"
                        >
                            <Eye class="size-3.5 text-primary" />
                            <span>Details</span>
                        </button>

                        <button
                            v-if="post.status === 'scheduled'"
                            type="button"
                            @click="openReschedule(post)"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-foreground shadow-xs transition hover:bg-muted"
                            title="Reschedule post"
                        >
                            <CalendarIcon class="size-3.5 text-primary" />
                            <span>Reschedule</span>
                        </button>

                        <button
                            type="button"
                            @click="duplicateToComposer(post)"
                            class="cursor-pointer rounded-lg border border-border bg-card p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            title="Duplicate to Composer"
                        >
                            <Copy class="size-3.5" />
                        </button>

                        <button
                            type="button"
                            @click="deletePost(post.id)"
                            class="cursor-pointer rounded-lg border border-border bg-card p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                            title="Delete Post"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View 2: Calendar View (Week vs Month) -->
        <div
            v-else
            class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
        >
            <!-- Calendar Navigation & Type Switcher Bar -->
            <div
                class="flex flex-col gap-3 border-b border-border bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <CalendarDays class="size-4 text-primary" />
                    <h3 class="text-sm font-bold text-foreground">
                        {{ calendarHeaderTitle }}
                    </h3>
                    <button
                        v-if="isNavigatedAway"
                        type="button"
                        @click="resetToCurrent"
                        class="ml-2 cursor-pointer rounded-md border border-border bg-card px-2 py-0.5 text-[11px] font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        Today
                    </button>
                </div>

                <div class="flex items-center gap-3 self-end sm:self-center">
                    <!-- Week / Month Mode Switcher -->
                    <div
                        class="flex items-center rounded-lg border border-border bg-card p-0.5 shadow-xs"
                    >
                        <button
                            type="button"
                            @click="calendarViewType = 'week'"
                            class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition"
                            :class="
                                calendarViewType === 'week'
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            1 Week
                        </button>
                        <button
                            type="button"
                            @click="calendarViewType = 'month'"
                            class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition"
                            :class="
                                calendarViewType === 'month'
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            Month
                        </button>
                    </div>

                    <!-- Previous / Next Period Navigation -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="prevPeriod"
                            class="cursor-pointer rounded-md border border-border p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            :title="
                                calendarViewType === 'week'
                                    ? 'Previous Week'
                                    : 'Previous Month'
                            "
                        >
                            <ChevronLeft class="size-4" />
                        </button>
                        <button
                            type="button"
                            @click="nextPeriod"
                            class="cursor-pointer rounded-md border border-border p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            :title="
                                calendarViewType === 'week'
                                    ? 'Next Week'
                                    : 'Next Month'
                            "
                        >
                            <ChevronRight class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Type 1: 1-Week Columns View -->
            <div
                v-if="calendarViewType === 'week'"
                class="grid min-h-96 grid-cols-7 divide-x divide-border"
            >
                <div
                    v-for="day in weekDays"
                    :key="day.fullDate"
                    class="flex flex-col space-y-2 p-2 transition-colors"
                    :class="
                        day.isToday ? 'bg-primary/5 dark:bg-primary/5' : 'bg-card'
                    "
                >
                    <div
                        class="border-b pb-2 text-center"
                        :class="
                            day.isToday
                                ? 'border-primary/40'
                                : 'border-border/50'
                        "
                    >
                        <p
                            class="text-[11px] font-semibold uppercase"
                            :class="
                                day.isToday
                                    ? 'text-primary'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ day.name }}
                        </p>
                        <p
                            class="font-mono text-xs font-bold"
                            :class="
                                day.isToday
                                    ? 'text-primary'
                                    : 'text-foreground'
                            "
                        >
                            {{ day.date }}
                        </p>
                    </div>

                    <!-- Filter posts for this day or preview cards -->
                    <div class="flex-1 space-y-2">
                        <div
                            v-for="post in getPostsForDay(day.fullDate)"
                            :key="post.id"
                            @click="openInspection(post)"
                            class="cursor-pointer rounded-lg border border-border/80 bg-muted/40 p-2 text-left shadow-xs transition hover:border-primary/60"
                        >
                            <span
                                class="py-0.2 rounded px-1 font-mono text-[9px] font-bold uppercase"
                                :class="getStatusBadge(post.status).class"
                            >
                                {{ post.status }}
                            </span>
                            <p
                                class="mt-1 line-clamp-2 text-[11px] font-medium text-foreground"
                            >
                                {{ post.content }}
                            </p>
                            <div
                                class="mt-1 flex items-center justify-between text-[10px] text-muted-foreground"
                            >
                                <span class="font-mono">
                                    {{
                                        formatPostTime(
                                            post.scheduled_at || post.created_at,
                                        )
                                    }}
                                </span>
                                <span class="capitalize">{{
                                    post.target_account_ids?.[0]
                                        ?.replace('acc-', '')
                                        ?.split('-')[0]
                                }}</span>
                            </div>
                        </div>

                        <div
                            v-if="getPostsForDay(day.fullDate).length === 0"
                            class="flex h-20 items-center justify-center rounded-lg border border-dashed border-border/40 text-[10px] text-muted-foreground/50 select-none"
                        >
                            No posts
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type 2: Month Grid View -->
            <div v-else class="flex flex-col">
                <!-- Day Names Header -->
                <div
                    class="grid grid-cols-7 border-b border-border bg-muted/30 text-center text-[11px] font-semibold text-muted-foreground"
                >
                    <div
                        v-for="name in monthHeaderNames"
                        :key="name"
                        class="py-2 uppercase tracking-wider"
                    >
                        {{ name }}
                    </div>
                </div>

                <!-- Month Grid Cells -->
                <div class="grid grid-cols-7 divide-x divide-y divide-border">
                    <div
                        v-for="cell in monthDays"
                        :key="cell.fullDate"
                        class="group min-h-24 p-1.5 transition-colors sm:min-h-28"
                        :class="[
                            cell.isToday
                                ? 'bg-primary/5'
                                : cell.isCurrentMonth
                                  ? 'bg-card'
                                  : 'bg-muted/15',
                        ]"
                    >
                        <!-- Day Number & Indicator -->
                        <div class="flex items-center justify-between pb-1">
                            <span
                                class="flex size-5.5 items-center justify-center rounded-full text-[11px] font-semibold"
                                :class="[
                                    cell.isToday
                                        ? 'bg-primary font-bold text-primary-foreground'
                                        : cell.isCurrentMonth
                                          ? 'text-foreground'
                                          : 'text-muted-foreground/60',
                                ]"
                            >
                                {{ cell.dayNumber }}
                            </span>
                            <span
                                v-if="getPostsForDay(cell.fullDate).length > 0"
                                class="font-mono text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ getPostsForDay(cell.fullDate).length }}
                            </span>
                        </div>

                        <!-- Posts for this Day -->
                        <div class="space-y-1">
                            <div
                                v-for="post in getPostsForDay(cell.fullDate).slice(0, 2)"
                                :key="post.id"
                                @click="openInspection(post)"
                                class="flex cursor-pointer items-center gap-1 rounded border border-border/70 bg-background/90 px-1.5 py-0.5 text-[10px] shadow-2xs transition hover:border-primary/60 hover:bg-muted"
                                :title="post.content"
                            >
                                <span
                                    class="size-1.5 shrink-0 rounded-full"
                                    :class="{
                                        'bg-emerald-500':
                                            post.status === 'published',
                                        'bg-blue-500':
                                            post.status === 'scheduled',
                                        'bg-amber-500':
                                            post.status === 'partial_failure' ||
                                            post.status === 'dlq',
                                        'bg-slate-400': post.status === 'draft',
                                    }"
                                ></span>
                                <span
                                    class="truncate font-medium text-foreground"
                                >
                                    {{ post.content }}
                                </span>
                            </div>

                            <button
                                v-if="getPostsForDay(cell.fullDate).length > 2"
                                type="button"
                                @click="
                                    openInspection(
                                        getPostsForDay(cell.fullDate)[2],
                                    )
                                "
                                class="w-full text-left text-[9px] font-semibold text-primary hover:underline px-0.5"
                            >
                                +{{ getPostsForDay(cell.fullDate).length - 2 }}
                                more
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reschedule Dialog Modal -->
        <Transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isRescheduleOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
                @click.self="isRescheduleOpen = false"
            >
                <div
                    class="w-full max-w-md space-y-5 rounded-2xl border border-border bg-card p-6 shadow-2xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-border pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <CalendarIcon class="size-4 text-primary" />
                            <h3 class="text-sm font-bold text-foreground">
                                Reschedule Post
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="isRescheduleOpen = false"
                            class="rounded-md p-1 text-muted-foreground hover:bg-muted"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <!-- Presets -->
                    <div class="space-y-1.5">
                        <span class="text-xs font-medium text-muted-foreground"
                            >Quick Presets</span
                        >
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="setSchedulePreset(24)"
                                class="rounded-lg border border-border p-2 text-center text-xs font-semibold transition hover:bg-muted"
                            >
                                Tomorrow 10am
                            </button>
                            <button
                                type="button"
                                @click="setSchedulePreset(48)"
                                class="rounded-lg border border-border p-2 text-center text-xs font-semibold transition hover:bg-muted"
                            >
                                In 2 Days
                            </button>
                            <button
                                type="button"
                                @click="setSchedulePreset(168)"
                                class="rounded-lg border border-border p-2 text-center text-xs font-semibold transition hover:bg-muted"
                            >
                                Next Week
                            </button>
                        </div>
                    </div>

                    <!-- Custom Date & Time -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Date</label
                            >
                            <input
                                v-model="newScheduleDate"
                                type="date"
                                class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                            />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Time</label
                            >
                            <input
                                v-model="newScheduleTime"
                                type="time"
                                class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-border pt-4"
                    >
                        <button
                            type="button"
                            @click="isRescheduleOpen = false"
                            class="rounded-lg border border-border px-3.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="submitReschedule"
                            class="rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                        >
                            Save Schedule
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Post Inspection / Details Drawer -->
        <PostInspectionDrawer
            :is-open="isDrawerOpen"
            :post="inspectedPost"
            @close="isDrawerOpen = false"
            @updated="handlePostUpdated"
            @reschedule="openReschedule"
            @duplicate="duplicateToComposer"
        />
    </div>
</template>
