<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Post } from '@/types/workspace';
import {
    X,
    CheckCircle2,
    XCircle,
    Clock,
    AlertTriangle,
    RotateCcw,
    Copy,
    Check,
    ExternalLink,
    Send,
    Radio,
    FileText,
    Calendar,
    Share2,
    Sparkles,
    Eye,
} from '@lucide/vue';
import { apiClient } from '../api/client';

const props = defineProps<{
    isOpen: boolean;
    post: Post | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated', updatedPost: Post): void;
    (e: 'reschedule', post: Post): void;
    (e: 'duplicate', post: Post): void;
}>();

const isRetrying = ref(false);
const copiedLink = ref(false);

const handleRetry = async () => {
    if (!props.post) return;
    isRetrying.value = true;
    try {
        let endpoint = `/posts/${props.post.id}/retry-failed`;
        if (props.post.status === 'dlq') {
            endpoint = `/dlq/${props.post.id}/replay`;
        }
        const res = await apiClient.post<Post>(endpoint);
        emit('updated', res.data);
    } catch (err: any) {
        alert(err?.response?.data?.message || 'Failed to retry publishing');
    } finally {
        isRetrying.value = false;
    }
};

const copyPostText = () => {
    if (!props.post?.content) return;
    navigator.clipboard.writeText(props.post.content);
    copiedLink.value = true;
    setTimeout(() => {
        copiedLink.value = false;
    }, 2000);
};

const formatTime = (dateStr?: string | null) => {
    if (!dateStr) return 'Unscheduled';
    const d = new Date(dateStr);
    return d.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'published':
            return {
                label: 'Published Live',
                class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            };
        case 'scheduled':
            return {
                label: 'Scheduled',
                class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            };
        case 'partial_failure':
            return {
                label: 'Action Needed on 1 Channel',
                class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            };
        case 'dlq':
            return {
                label: 'Delivery Paused',
                class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            };
        default:
            return {
                label: 'Draft',
                class: 'bg-muted text-muted-foreground border-border',
            };
    }
};
</script>

<template>
    <div>
        <!-- Backdrop -->
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
                @click="emit('close')"
                class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs"
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
            <div
                v-if="isOpen"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col border-l border-border bg-card shadow-2xl"
            >
                <!-- Drawer Header -->
                <div
                    class="flex items-center justify-between border-b border-border bg-muted/20 px-6 py-4"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Send class="size-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">
                                Post Details & Delivery Report
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Detailed delivery status across all selected
                                social networks.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="emit('close')"
                        class="cursor-pointer rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Drawer Body -->
                <div v-if="post" class="flex-1 space-y-5 overflow-y-auto p-6">
                    <!-- Status & Schedule Overview Card -->
                    <div
                        class="space-y-3 rounded-xl border border-border bg-card p-4 shadow-xs"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-muted-foreground"
                                >Delivery Status</span
                            >
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 font-mono text-xs font-bold tracking-wide uppercase shadow-xs"
                                :class="getStatusBadge(post.status).class"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-current"
                                />
                                {{ getStatusBadge(post.status).label }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between border-t border-border/60 pt-2.5 text-xs"
                        >
                            <span class="text-muted-foreground">
                                {{
                                    post.status === 'scheduled'
                                        ? 'Scheduled Publishing'
                                        : 'Posted Time'
                                }}
                            </span>
                            <span class="font-medium text-foreground">
                                {{
                                    formatTime(
                                        post.scheduled_at || post.created_at,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Post Content Preview -->
                    <div
                        class="space-y-3 rounded-xl border border-border bg-card p-4 shadow-xs"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Content Preview
                            </span>
                            <button
                                type="button"
                                @click="copyPostText"
                                class="inline-flex cursor-pointer items-center gap-1 text-xs text-primary hover:underline"
                            >
                                <Check
                                    v-if="copiedLink"
                                    class="size-3 text-emerald-500"
                                />
                                <Copy v-else class="size-3" />
                                <span>{{
                                    copiedLink ? 'Copied' : 'Copy Text'
                                }}</span>
                            </button>
                        </div>

                        <p
                            class="rounded-lg border border-border/60 bg-muted/40 p-3 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                        >
                            {{ post.content }}
                        </p>

                        <!-- Attached Media -->
                        <div v-if="post.media_url" class="space-y-1.5">
                            <span
                                class="text-[11px] font-semibold text-muted-foreground"
                                >Attached Media</span
                            >
                            <div
                                class="max-h-56 overflow-hidden rounded-lg border border-border bg-muted/40"
                            >
                                <img
                                    :src="post.media_url"
                                    alt="Post Media"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        </div>

                        <!-- OpenGraph Link Preview -->
                        <div
                            v-else-if="post.link_metadata"
                            class="space-y-1.5 overflow-hidden rounded-lg border border-border bg-muted/30 p-3"
                        >
                            <span class="text-[11px] font-semibold text-primary"
                                >Link Preview</span
                            >
                            <div class="flex gap-3">
                                <img
                                    v-if="post.link_metadata.image_url"
                                    :src="post.link_metadata.image_url"
                                    alt="Link Preview"
                                    class="size-14 rounded-md border border-border object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="truncate text-xs font-bold text-foreground"
                                    >
                                        {{ post.link_metadata.title }}
                                    </h4>
                                    <p
                                        class="mt-0.5 line-clamp-2 text-[11px] text-muted-foreground"
                                    >
                                        {{ post.link_metadata.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform-by-Platform Publishing Breakdown -->
                    <div class="space-y-3">
                        <h4
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Channels & Live Permalinks
                        </h4>

                        <div class="space-y-2">
                            <!-- Channel Deliveries -->
                            <div
                                v-for="accId in post.target_account_ids || []"
                                :key="accId"
                                class="flex items-center justify-between rounded-xl border border-border bg-card p-3.5 shadow-xs"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-8 items-center justify-center rounded-lg border border-border bg-muted font-mono text-xs font-bold text-foreground uppercase"
                                    >
                                        {{
                                            accId
                                                .replace('acc-', '')
                                                .split('-')[0]
                                        }}
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-bold text-foreground capitalize"
                                        >
                                            {{
                                                accId
                                                    .replace('acc-', '')
                                                    .split('-')[0]
                                            }}
                                            Channel
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{
                                                post.status === 'published'
                                                    ? 'Delivered successfully'
                                                    : post.status ===
                                                        'scheduled'
                                                      ? 'Queued for automatic dispatch'
                                                      : 'Rate limit backoff active'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <!-- Live Link if available -->
                                    <a
                                        v-if="
                                            post.live_urls &&
                                            post.live_urls[
                                                accId
                                                    .replace('acc-', '')
                                                    .split('-')[0]
                                            ]
                                        "
                                        :href="
                                            post.live_urls[
                                                accId
                                                    .replace('acc-', '')
                                                    .split('-')[0]
                                            ]
                                        "
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/60 px-2.5 py-1 text-xs font-medium text-primary transition hover:bg-muted"
                                    >
                                        <span>View Live</span>
                                        <ExternalLink class="size-3" />
                                    </a>
                                    <span
                                        v-else-if="post.status === 'published'"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        <span>Live</span>
                                    </span>
                                    <span
                                        v-else-if="post.status === 'scheduled'"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600"
                                    >
                                        <Clock class="size-3.5" />
                                        <span>Queued</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600"
                                    >
                                        <AlertTriangle class="size-3.5" />
                                        <span>Pending Retry</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Human-readable Publishing Progress -->
                    <div
                        class="space-y-3 rounded-xl border border-border bg-card p-4 shadow-xs"
                    >
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Publishing Pipeline Progress
                        </span>

                        <div class="space-y-3 pt-1">
                            <div class="flex items-center gap-3 text-xs">
                                <CheckCircle2
                                    class="size-4 shrink-0 text-emerald-500"
                                />
                                <span class="font-medium text-foreground"
                                    >Content validated and formatted</span
                                >
                            </div>
                            <div class="flex items-center gap-3 text-xs">
                                <CheckCircle2
                                    class="size-4 shrink-0 text-emerald-500"
                                />
                                <span class="font-medium text-foreground"
                                    >Media optimized for social aspect
                                    ratios</span
                                >
                            </div>
                            <div class="flex items-center gap-3 text-xs">
                                <CheckCircle2
                                    v-if="post.status === 'published'"
                                    class="size-4 shrink-0 text-emerald-500"
                                />
                                <AlertTriangle
                                    v-else-if="
                                        post.status === 'partial_failure' ||
                                        post.status === 'dlq'
                                    "
                                    class="size-4 shrink-0 text-amber-500"
                                />
                                <Clock
                                    v-else
                                    class="size-4 shrink-0 text-blue-500"
                                />
                                <span class="font-medium text-foreground">
                                    {{
                                        post.status === 'published'
                                            ? 'Delivered to social network APIs'
                                            : post.status === 'scheduled'
                                              ? 'Scheduled queue runner armed'
                                              : 'Channel delivery paused — retry ready'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer Actions -->
                <div
                    class="flex items-center justify-between border-t border-border bg-card/60 p-5"
                >
                    <button
                        type="button"
                        @click="emit('close')"
                        class="cursor-pointer rounded-lg border border-border px-3.5 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
                    >
                        Close
                    </button>

                    <div class="flex items-center gap-2">
                        <!-- Retry Button if failed -->
                        <button
                            v-if="
                                post?.status === 'partial_failure' ||
                                post?.status === 'dlq'
                            "
                            type="button"
                            @click="handleRetry"
                            :disabled="isRetrying"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-amber-700 disabled:opacity-50"
                        >
                            <RotateCcw
                                class="size-3.5"
                                :class="{ 'animate-spin': isRetrying }"
                            />
                            <span>{{
                                isRetrying
                                    ? 'Retrying...'
                                    : 'Retry Delivery Now'
                            }}</span>
                        </button>

                        <!-- Reschedule Button if scheduled -->
                        <button
                            v-if="post?.status === 'scheduled'"
                            type="button"
                            @click="emit('reschedule', post)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3.5 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
                        >
                            <Calendar class="size-3.5 text-primary" />
                            <span>Reschedule</span>
                        </button>

                        <!-- Duplicate to Composer -->
                        <button
                            type="button"
                            @click="emit('duplicate', post!)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90"
                        >
                            <Sparkles class="size-3.5" />
                            <span>Duplicate to Composer</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
