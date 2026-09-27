<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import { apiClient } from '@/api/client';
import ImageCropperModal from '@/components/ImageCropperModal.vue';
import type {
    SocialAccount,
    LinkMetadata,
    PlatformOverrides,
} from '@/types/workspace';
import {
    Send,
    Calendar,
    Clock,
    Link2,
    Image as ImageIcon,
    Crop,
    Check,
    AlertCircle,
    Info,
    Trash2,
    ExternalLink,
    MessageSquare,
    Repeat2,
    Heart,
    Share,
    ThumbsUp,
    MessageCircle,
    CheckCircle2,
    Share2,
    X,
    AlertTriangle,
} from '@lucide/vue';

const workspaceStore = useWorkspaceStore();

// Form state
const content = ref('');
const selectedAccountIds = ref<string[]>([]);
const mediaUrl = ref<string>('');
const linkMetadata = ref<LinkMetadata | null>(null);
const linkUrlInput = ref('');
const isFetchingLink = ref(false);

// Platform overrides (Twitter, LinkedIn, and Facebook)
const activeTab = ref<'base' | 'twitter' | 'linkedin' | 'facebook'>('base');
const hasTwitterOverride = ref(false);
const twitterContent = ref('');
const hasLinkedInOverride = ref(false);
const linkedinContent = ref('');
const hasFacebookOverride = ref(false);
const facebookContent = ref('');

// Scheduling & Idempotency
const isScheduled = ref(false);
const scheduledDate = ref('');
const scheduledTime = ref('');
const isSubmitting = ref(false);
const dispatchSuccessMessage = ref<string | null>(null);

// Access feedback banner state
const revokedAccountAlert = ref<SocialAccount | null>(null);

// Image Cropper Modal & Link Input Drawer
const isCropperOpen = ref(false);
const isLinkInputOpen = ref(false);

// Auto-Save & Storage Keys
const lastSavedAt = ref<string | null>(null);
const DRAFT_STORAGE_KEY = 'workspace_composer_draft';
const STORAGE_SELECTED_ACCOUNTS_PREFIX =
    'socialsync_composer_selected_accounts_';

// Live Preview platform toggle
const previewPlatform = ref<'twitter' | 'linkedin' | 'facebook'>('twitter');

// Auto-sync live preview when switching composer override tab
watch(activeTab, (tab) => {
    if (tab === 'twitter') {
        previewPlatform.value = 'twitter';
        if (
            hasTwitterOverride.value &&
            !twitterContent.value &&
            content.value
        ) {
            twitterContent.value = content.value;
        }
    } else if (tab === 'linkedin') {
        previewPlatform.value = 'linkedin';
        if (
            hasLinkedInOverride.value &&
            !linkedinContent.value &&
            content.value
        ) {
            linkedinContent.value = content.value;
        }
    } else if (tab === 'facebook') {
        previewPlatform.value = 'facebook';
        if (
            hasFacebookOverride.value &&
            !facebookContent.value &&
            content.value
        ) {
            facebookContent.value = content.value;
        }
    }
});

// Platform override helper actions
const copyFromUniversal = (platform: 'twitter' | 'linkedin' | 'facebook') => {
    if (platform === 'twitter') {
        hasTwitterOverride.value = true;
        twitterContent.value = content.value;
    } else if (platform === 'linkedin') {
        hasLinkedInOverride.value = true;
        linkedinContent.value = content.value;
    } else if (platform === 'facebook') {
        hasFacebookOverride.value = true;
        facebookContent.value = content.value;
    }
};

const resetToUniversal = (platform: 'twitter' | 'linkedin' | 'facebook') => {
    if (platform === 'twitter') {
        hasTwitterOverride.value = false;
        twitterContent.value = '';
    } else if (platform === 'linkedin') {
        hasLinkedInOverride.value = false;
        linkedinContent.value = '';
    } else if (platform === 'facebook') {
        hasFacebookOverride.value = false;
        facebookContent.value = '';
    }
};

// Generate client-side UUIDv4 idempotency key
const generateUUID = () => {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
};

// Target accounts
const accounts = computed<SocialAccount[]>(() => workspaceStore.accounts);

const healthyAccounts = computed(() => {
    return accounts.value.filter((a) => a.status !== 'revoked');
});

const activeAccounts = computed(() => {
    return accounts.value.filter((a) =>
        selectedAccountIds.value.includes(a.id),
    );
});

const activeAccountAvatars = computed<string[]>(() => {
    return activeAccounts.value
        .map((a) => a.avatar_url)
        .filter((url): url is string => Boolean(url));
});

// Specific platform accounts for feed preview cards
const activeTwitterAccount = computed(() => {
    return (
        activeAccounts.value.find((a) => a.provider === 'twitter') ||
        accounts.value.find((a) => a.provider === 'twitter')
    );
});

const activeLinkedInAccount = computed(() => {
    return (
        activeAccounts.value.find((a) => a.provider === 'linkedin') ||
        accounts.value.find((a) => a.provider === 'linkedin')
    );
});

const activeFacebookAccount = computed(() => {
    return (
        activeAccounts.value.find((a) => a.provider === 'facebook') ||
        accounts.value.find((a) => a.provider === 'facebook')
    );
});

// Real-time Platform Content Resolution with Fallback
const resolvedTwitterText = computed(() => {
    if (hasTwitterOverride.value && twitterContent.value) {
        return twitterContent.value;
    }
    return content.value;
});

const resolvedLinkedInText = computed(() => {
    if (hasLinkedInOverride.value && linkedinContent.value) {
        return linkedinContent.value;
    }
    return content.value;
});

const resolvedFacebookText = computed(() => {
    if (hasFacebookOverride.value && facebookContent.value) {
        return facebookContent.value;
    }
    return content.value;
});

// Selected platforms & Solo Targeting
const selectedPlatforms = computed(() => {
    const platforms = new Set<'twitter' | 'linkedin' | 'facebook'>();
    healthyAccounts.value
        .filter((a) => selectedAccountIds.value.includes(a.id))
        .forEach((a) => platforms.add(a.provider));
    return platforms;
});

const isSinglePlatform = computed(() => selectedPlatforms.value.size === 1);
const isTwitterOnly = computed(
    () =>
        selectedPlatforms.value.size === 1 &&
        selectedPlatforms.value.has('twitter'),
);
const isLinkedInOnly = computed(
    () =>
        selectedPlatforms.value.size === 1 &&
        selectedPlatforms.value.has('linkedin'),
);
const isFacebookOnly = computed(
    () =>
        selectedPlatforms.value.size === 1 &&
        selectedPlatforms.value.has('facebook'),
);

// Character limits (platform-aware: unselected platforms do not trigger false limit warnings)
const twitterCharCount = computed(() => resolvedTwitterText.value.length);
const twitterLimit = 280;
const isTwitterOver = computed(() => {
    if (
        selectedAccountIds.value.length > 0 &&
        !selectedPlatforms.value.has('twitter')
    ) {
        return false;
    }
    return twitterCharCount.value > twitterLimit;
});

const linkedinCharCount = computed(() => resolvedLinkedInText.value.length);
const linkedinLimit = 3000;
const isLinkedInOver = computed(() => {
    if (
        selectedAccountIds.value.length > 0 &&
        !selectedPlatforms.value.has('linkedin')
    ) {
        return false;
    }
    return linkedinCharCount.value > linkedinLimit;
});

const facebookCharCount = computed(() => resolvedFacebookText.value.length);
const facebookLimit = 63206;
const isFacebookOver = computed(() => {
    if (
        selectedAccountIds.value.length > 0 &&
        !selectedPlatforms.value.has('facebook')
    ) {
        return false;
    }
    return facebookCharCount.value > facebookLimit;
});

// Platform Solo Selection
const selectPlatformOnly = (platform: 'twitter' | 'linkedin' | 'facebook') => {
    const matchingAccounts = healthyAccounts.value.filter(
        (a) => a.provider === platform,
    );
    if (matchingAccounts.length === 0) return;

    selectedAccountIds.value = matchingAccounts.map((a) => a.id);
    persistSelectedChannels();
    previewPlatform.value = platform;
    activeTab.value = 'base';
};

// Select a single individual account
const selectSingleAccount = (accountId: string) => {
    const acc = healthyAccounts.value.find((a) => a.id === accountId);
    if (!acc) return;

    selectedAccountIds.value = [accountId];
    persistSelectedChannels();
    previewPlatform.value = acc.provider;
    activeTab.value = 'base';
};

// Add platform accounts to currently selected channels
const addPlatformAccounts = (platform: 'twitter' | 'linkedin' | 'facebook') => {
    const platformAccountIds = healthyAccounts.value
        .filter((a) => a.provider === platform)
        .map((a) => a.id);

    const merged = new Set([
        ...selectedAccountIds.value,
        ...platformAccountIds,
    ]);
    selectedAccountIds.value = Array.from(merged);
    persistSelectedChannels();
    previewPlatform.value = platform;
};

// Persistent Channel Selection
const persistSelectedChannels = () => {
    if (!workspaceStore.activeOrgId) return;
    const key = `${STORAGE_SELECTED_ACCOUNTS_PREFIX}${workspaceStore.activeOrgId}`;
    localStorage.setItem(key, JSON.stringify(selectedAccountIds.value));
};

const restoreSelectedChannels = () => {
    if (!workspaceStore.activeOrgId) return;
    const key = `${STORAGE_SELECTED_ACCOUNTS_PREFIX}${workspaceStore.activeOrgId}`;
    const raw = localStorage.getItem(key);
    const validAvailableIds = healthyAccounts.value.map((a) => a.id);

    if (raw) {
        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed) && parsed.length > 0) {
                const restored = parsed.filter((id) =>
                    validAvailableIds.includes(id),
                );
                if (restored.length > 0) {
                    selectedAccountIds.value = restored;
                    return;
                }
            }
        } catch {
            // fallback
        }
    }
    // Default fallback: select all healthy / non-revoked accounts
    selectedAccountIds.value = [...validAvailableIds];
};

// Account toggle with access feedback
const toggleAccount = (acc: SocialAccount) => {
    if (acc.status === 'revoked') {
        revokedAccountAlert.value = acc;
        return;
    }

    if (revokedAccountAlert.value?.id === acc.id) {
        revokedAccountAlert.value = null;
    }

    if (selectedAccountIds.value.includes(acc.id)) {
        selectedAccountIds.value = selectedAccountIds.value.filter(
            (id) => id !== acc.id,
        );
    } else {
        selectedAccountIds.value.push(acc.id);
    }
    persistSelectedChannels();
};

const selectAllAvailable = () => {
    selectedAccountIds.value = healthyAccounts.value.map((a) => a.id);
    persistSelectedChannels();
};

const deselectAll = () => {
    selectedAccountIds.value = [];
    persistSelectedChannels();
};

// Preset helpers for schedule
const setSchedulePreset = (hoursFromNow: number) => {
    const d = new Date(Date.now() + hoursFromNow * 60 * 60 * 1000);
    scheduledDate.value = d.toISOString().split('T')[0];
    scheduledTime.value = '10:00';
};

// Clear draft
const clearComposer = () => {
    if (!confirm('Discard current post draft?')) return;
    content.value = '';
    mediaUrl.value = '';
    linkMetadata.value = null;
    linkUrlInput.value = '';
    hasTwitterOverride.value = false;
    twitterContent.value = '';
    hasLinkedInOverride.value = false;
    linkedinContent.value = '';
    hasFacebookOverride.value = false;
    facebookContent.value = '';
    isScheduled.value = false;
    scheduledDate.value = '';
    scheduledTime.value = '';
    localStorage.removeItem(DRAFT_STORAGE_KEY);
    lastSavedAt.value = null;
};

// Auto-detect link pasted in content
const detectUrl = (text: string) => {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const matches = text.match(urlRegex);
    if (
        matches &&
        matches.length > 0 &&
        !linkMetadata.value &&
        !linkUrlInput.value
    ) {
        linkUrlInput.value = matches[0];
        fetchLinkMetadata(matches[0]);
    }
};

watch(content, (newVal) => {
    detectUrl(newVal);
});

const fetchLinkMetadata = async (urlToFetch?: string) => {
    const target = urlToFetch || linkUrlInput.value;
    if (!target) return;

    isFetchingLink.value = true;
    try {
        const res = await apiClient.get<LinkMetadata>('/tools/preview-link', {
            params: { url: target },
        });
        linkMetadata.value = res.data;
    } catch (err) {
        console.error('Failed to preview link', err);
    } finally {
        isFetchingLink.value = false;
    }
};

const removeLinkPreview = () => {
    linkMetadata.value = null;
    linkUrlInput.value = '';
};

// Image upload simulator
const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        mediaUrl.value = event.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const handleCropped = (dataUrl: string) => {
    mediaUrl.value = dataUrl;
};

const removeImage = () => {
    mediaUrl.value = '';
};

// Auto-save logic
const saveDraft = () => {
    const draft = {
        content: content.value,
        selectedAccountIds: selectedAccountIds.value,
        mediaUrl: mediaUrl.value,
        linkMetadata: linkMetadata.value,
        hasTwitterOverride: hasTwitterOverride.value,
        twitterContent: twitterContent.value,
        hasLinkedInOverride: hasLinkedInOverride.value,
        linkedinContent: linkedinContent.value,
        hasFacebookOverride: hasFacebookOverride.value,
        facebookContent: facebookContent.value,
        isScheduled: isScheduled.value,
        scheduledDate: scheduledDate.value,
        scheduledTime: scheduledTime.value,
        savedAt: new Date().toLocaleTimeString(),
    };
    localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draft));
    lastSavedAt.value = draft.savedAt;
};

const loadDraft = () => {
    try {
        const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (draft.content) content.value = draft.content;
        if (draft.selectedAccountIds?.length)
            selectedAccountIds.value = draft.selectedAccountIds;
        if (draft.mediaUrl) mediaUrl.value = draft.mediaUrl;
        if (draft.linkMetadata) linkMetadata.value = draft.linkMetadata;
        if (draft.hasTwitterOverride !== undefined)
            hasTwitterOverride.value = draft.hasTwitterOverride;
        if (draft.twitterContent) twitterContent.value = draft.twitterContent;
        if (draft.hasLinkedInOverride !== undefined)
            hasLinkedInOverride.value = draft.hasLinkedInOverride;
        if (draft.linkedinContent)
            linkedinContent.value = draft.linkedinContent;
        if (draft.hasFacebookOverride !== undefined)
            hasFacebookOverride.value = draft.hasFacebookOverride;
        if (draft.facebookContent)
            facebookContent.value = draft.facebookContent;
        lastSavedAt.value = draft.savedAt || null;
    } catch {
        // ignore
    }
};

let autoSaveTimer: any = null;

onMounted(async () => {
    if (workspaceStore.accounts.length === 0) {
        await workspaceStore.fetchAccounts();
    }
    restoreSelectedChannels();
    loadDraft();

    // Auto-save every 5s
    autoSaveTimer = setInterval(() => {
        if (content.value || mediaUrl.value || linkMetadata.value) {
            saveDraft();
        }
    }, 5000);
});

watch(
    () => workspaceStore.activeOrgId,
    async () => {
        await workspaceStore.fetchAccounts();
        restoreSelectedChannels();
    },
);

onUnmounted(() => {
    if (autoSaveTimer) clearInterval(autoSaveTimer);
});

// Dispatch / Publish Post
const handleDispatch = async (publishImmediate = false) => {
    if (!content.value.trim() && !mediaUrl.value && !linkMetadata.value) {
        alert('Please write content or attach media before publishing.');
        return;
    }

    if (selectedAccountIds.value.length === 0) {
        alert('Please select at least one social channel.');
        return;
    }

    isSubmitting.value = true;
    dispatchSuccessMessage.value = null;

    let scheduledAt: string | null = null;
    if (!publishImmediate && isScheduled.value && scheduledDate.value) {
        const timePart = scheduledTime.value || '12:00';
        scheduledAt = new Date(
            `${scheduledDate.value}T${timePart}:00`,
        ).toISOString();
    }

    const overrides: PlatformOverrides = {};
    if (hasTwitterOverride.value && twitterContent.value) {
        overrides.twitter = { content: twitterContent.value };
    }
    if (hasLinkedInOverride.value && linkedinContent.value) {
        overrides.linkedin = { content: linkedinContent.value };
    }
    if (hasFacebookOverride.value && facebookContent.value) {
        overrides.facebook = { content: facebookContent.value };
    }

    const idempotencyKey = `idem_${generateUUID()}`;

    try {
        const payload = {
            organization_id: workspaceStore.activeOrgId,
            content: content.value,
            platform_overrides:
                Object.keys(overrides).length > 0 ? overrides : null,
            media_url: mediaUrl.value || null,
            link_metadata: linkMetadata.value || null,
            status: scheduledAt ? 'scheduled' : 'published',
            scheduled_at: scheduledAt,
            target_account_ids: selectedAccountIds.value,
            idempotency_key: idempotencyKey,
        };

        await apiClient.post('/social/posts', payload);

        dispatchSuccessMessage.value = scheduledAt
            ? `Post successfully scheduled for ${new Date(scheduledAt).toLocaleString()}!`
            : `Post successfully published across ${selectedAccountIds.value.length} channels!`;

        // Clear draft
        localStorage.removeItem(DRAFT_STORAGE_KEY);
        content.value = '';
        twitterContent.value = '';
        linkedinContent.value = '';
        facebookContent.value = '';
        mediaUrl.value = '';
        linkMetadata.value = null;
        linkUrlInput.value = '';
        hasTwitterOverride.value = false;
        hasLinkedInOverride.value = false;
        hasFacebookOverride.value = false;
        lastSavedAt.value = null;

        // Redirect to feed after 1.5s
        setTimeout(() => {
            router.visit(`/w/${workspaceStore.activeOrgSlug}/posts`);
        }, 1500);
    } catch (err: any) {
        console.error('Dispatch failed', err);
        alert(err?.response?.data?.message || 'Failed to dispatch post.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Universal Composer" />
        <h1 class="sr-only">Universal Composer</h1>

        <!-- Top Status & Actions Bar -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <p
                    v-if="lastSavedAt"
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Check class="size-3.5 text-emerald-500" />
                    <span>Auto-saved {{ lastSavedAt }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="saveDraft"
                    class="cursor-pointer rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-2xs transition hover:bg-muted"
                >
                    Save Draft
                </button>
                <button
                    v-if="content || mediaUrl || linkMetadata"
                    type="button"
                    @click="clearComposer"
                    class="cursor-pointer rounded-lg border border-transparent px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:text-destructive"
                    title="Discard all content"
                >
                    Discard
                </button>
            </div>
        </div>

        <!-- Success Toast Alert -->
        <div
            v-if="dispatchSuccessMessage"
            class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-900 shadow-xs transition-all dark:text-emerald-200"
        >
            <div class="flex items-center gap-2.5 font-medium">
                <CheckCircle2
                    class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <span>{{ dispatchSuccessMessage }}</span>
            </div>
            <Link
                :href="`/w/${workspaceStore.activeOrgSlug}/posts`"
                class="text-xs font-semibold text-emerald-700 underline underline-offset-2 hover:opacity-80 dark:text-emerald-300"
            >
                View in Posts Feed &rarr;
            </Link>
        </div>

        <!-- 2-Column Composer Grid: Compose & Channels on Left, Native Preview on Right -->
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            <!-- Left Side (lg:col-span-7): Compose Canvas on Top, Channels Dock Below -->
            <div class="space-y-6 lg:col-span-7">
                <!-- 1. Unified Social Compose Canvas Studio -->
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition-all focus-within:border-primary/50 focus-within:ring-2 focus-within:ring-primary/10"
                >
                    <!-- Composer Header: Author Profile & Social Channels Status -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 bg-muted/20 px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                :src="
                                    activeAccountAvatars[0] ||
                                    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100'
                                "
                                alt="Author avatar"
                                class="size-9 shrink-0 rounded-full border border-border object-cover shadow-2xs"
                            />
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-xs font-bold text-foreground"
                                    >
                                        {{
                                            workspaceStore.currentOrg?.name ||
                                            'Acme Studio'
                                        }}
                                    </span>
                                    <span
                                        class="rounded bg-muted px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground"
                                    >
                                        {{ selectedAccountIds.length }} /
                                        {{ healthyAccounts.length }} active
                                    </span>
                                </div>
                                <p
                                    class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
                                >
                                    <template v-if="isTwitterOnly">
                                        <span
                                            class="inline-flex items-center gap-1 font-semibold text-foreground"
                                        >
                                            <span>𝕏</span> Twitter Only Mode
                                        </span>
                                        <span>· 280 max characters</span>
                                    </template>
                                    <template v-else-if="isLinkedInOnly">
                                        <span
                                            class="inline-flex items-center gap-1 font-semibold text-[#0077b5]"
                                        >
                                            <span>in</span> LinkedIn Only Mode
                                        </span>
                                        <span>· 3,000 max characters</span>
                                    </template>
                                    <template v-else-if="isFacebookOnly">
                                        <span
                                            class="inline-flex items-center gap-1 font-semibold text-[#1877f2]"
                                        >
                                            <span>f</span> Facebook Only Mode
                                        </span>
                                        <span>· Community post mode</span>
                                    </template>
                                    <template v-else>
                                        <span
                                            >Universal social post across active
                                            channels</span
                                        >
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- Active Channel Micro-Avatars / Overlap Stack with Quick Reset -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="isSinglePlatform"
                                type="button"
                                @click="selectAllAvailable"
                                class="cursor-pointer text-[11px] font-medium text-primary hover:underline"
                            >
                                Switch to All Channels &rarr;
                            </button>
                            <div
                                class="flex items-center -space-x-1.5 overflow-hidden"
                            >
                                <template v-if="activeAccounts.length > 0">
                                    <img
                                        v-for="acc in activeAccounts.slice(
                                            0,
                                            4,
                                        )"
                                        :key="acc.id"
                                        :src="
                                            acc.avatar_url ||
                                            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100'
                                        "
                                        :title="acc.name"
                                        class="inline-block size-6 rounded-full object-cover ring-2 ring-card"
                                    />
                                    <span
                                        v-if="activeAccounts.length > 4"
                                        class="flex size-6 items-center justify-center rounded-full bg-muted text-[10px] font-bold text-muted-foreground ring-2 ring-card"
                                    >
                                        +{{ activeAccounts.length - 4 }}
                                    </span>
                                </template>
                                <span
                                    v-else
                                    class="text-xs font-medium text-rose-500"
                                >
                                    No channels selected
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Override Sub-Tabs (Universal vs Twitter vs LinkedIn vs Facebook with Live Limit Badges) -->
                    <div
                        class="flex items-center gap-1 overflow-x-auto border-b border-border/60 bg-muted/10 px-4 pt-2 text-xs"
                    >
                        <button
                            type="button"
                            @click="activeTab = 'base'"
                            class="relative shrink-0 cursor-pointer rounded-t-md px-3 py-1.5 font-medium transition"
                            :class="
                                activeTab === 'base'
                                    ? 'border-x border-t border-border/80 bg-card font-semibold text-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span v-if="isTwitterOnly">𝕏 Twitter Post</span>
                            <span v-else-if="isLinkedInOnly"
                                >in LinkedIn Post</span
                            >
                            <span v-else-if="isFacebookOnly"
                                >f Facebook Post</span
                            >
                            <span v-else>Universal Post</span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'twitter'"
                            class="relative flex shrink-0 cursor-pointer items-center gap-1.5 rounded-t-md px-3 py-1.5 font-medium transition"
                            :class="
                                activeTab === 'twitter'
                                    ? 'border-x border-t border-border/80 bg-card font-semibold text-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span>X / Twitter</span>
                            <span
                                v-if="selectedPlatforms.has('twitter')"
                                class="rounded px-1.5 py-0.5 font-mono text-[10px] transition"
                                :class="
                                    isTwitterOver
                                        ? 'bg-destructive/15 font-bold text-destructive ring-1 ring-destructive/40'
                                        : 'bg-muted/70 text-muted-foreground'
                                "
                            >
                                {{ twitterCharCount }}/{{ twitterLimit }}
                            </span>
                            <span
                                v-else
                                class="py-0.2 rounded bg-muted/50 px-1 font-sans text-[9px] text-muted-foreground/60 italic"
                            >
                                Off
                            </span>
                            <span
                                v-if="hasTwitterOverride"
                                class="size-1.5 rounded-full bg-primary"
                                title="Custom override active"
                            ></span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'linkedin'"
                            class="relative flex shrink-0 cursor-pointer items-center gap-1.5 rounded-t-md px-3 py-1.5 font-medium transition"
                            :class="
                                activeTab === 'linkedin'
                                    ? 'border-x border-t border-border/80 bg-card font-semibold text-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span>LinkedIn</span>
                            <span
                                v-if="selectedPlatforms.has('linkedin')"
                                class="rounded px-1.5 py-0.5 font-mono text-[10px] transition"
                                :class="
                                    isLinkedInOver
                                        ? 'bg-destructive/15 font-bold text-destructive ring-1 ring-destructive/40'
                                        : 'bg-muted/70 text-muted-foreground'
                                "
                            >
                                {{ linkedinCharCount }}/3,000
                            </span>
                            <span
                                v-else
                                class="py-0.2 rounded bg-muted/50 px-1 font-sans text-[9px] text-muted-foreground/60 italic"
                            >
                                Off
                            </span>
                            <span
                                v-if="hasLinkedInOverride"
                                class="size-1.5 rounded-full bg-primary"
                                title="Custom override active"
                            ></span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'facebook'"
                            class="relative flex shrink-0 cursor-pointer items-center gap-1.5 rounded-t-md px-3 py-1.5 font-medium transition"
                            :class="
                                activeTab === 'facebook'
                                    ? 'border-x border-t border-border/80 bg-card font-semibold text-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span>Facebook</span>
                            <span
                                v-if="selectedPlatforms.has('facebook')"
                                class="rounded px-1.5 py-0.5 font-mono text-[10px] transition"
                                :class="
                                    isFacebookOver
                                        ? 'bg-destructive/15 font-bold text-destructive ring-1 ring-destructive/40'
                                        : 'bg-muted/70 text-muted-foreground'
                                "
                            >
                                {{ facebookCharCount }}/63k
                            </span>
                            <span
                                v-else
                                class="py-0.2 rounded bg-muted/50 px-1 font-sans text-[9px] text-muted-foreground/60 italic"
                            >
                                Off
                            </span>
                            <span
                                v-if="hasFacebookOverride"
                                class="size-1.5 rounded-full bg-primary"
                                title="Custom override active"
                            ></span>
                        </button>
                    </div>

                    <!-- Post Body Writing Canvas -->
                    <div class="space-y-3 p-4">
                        <!-- Universal Text Tab Area -->
                        <div v-if="activeTab === 'base'">
                            <textarea
                                v-model="content"
                                rows="6"
                                :placeholder="
                                    isTwitterOnly
                                        ? 'What\'s happening? Draft directly for X / Twitter (280 chars max)...'
                                        : isLinkedInOnly
                                          ? 'Share professional insights, articles, or discussions on LinkedIn (3,000 chars max)...'
                                          : isFacebookOnly
                                            ? 'Share updates, questions, or announcements on Facebook...'
                                            : 'What\'s happening? Share insights, announcements, links, or media across all your social channels...'
                                "
                                class="w-full border-0 bg-transparent text-sm leading-relaxed text-foreground placeholder:text-muted-foreground/60 focus:outline-hidden"
                            ></textarea>
                        </div>

                        <!-- Twitter Override Tab Area -->
                        <div
                            v-else-if="activeTab === 'twitter'"
                            class="space-y-2.5"
                        >
                            <!-- Not targeted alert -->
                            <div
                                v-if="
                                    !selectedPlatforms.has('twitter') &&
                                    selectedAccountIds.length > 0
                                "
                                class="flex items-center justify-between rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300"
                            >
                                <span
                                    >X / Twitter is currently not targeted for
                                    this post.</span
                                >
                                <button
                                    type="button"
                                    @click="addPlatformAccounts('twitter')"
                                    class="cursor-pointer font-semibold underline underline-offset-2 hover:opacity-80"
                                >
                                    + Add Twitter to Targets
                                </button>
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-border/50 pb-1.5 text-xs"
                            >
                                <label
                                    class="flex cursor-pointer items-center gap-2 font-medium text-foreground"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="hasTwitterOverride"
                                        @change="
                                            if (
                                                hasTwitterOverride &&
                                                !twitterContent
                                            )
                                                twitterContent = content;
                                        "
                                        class="rounded border-border text-primary focus:ring-primary"
                                    />
                                    <span
                                        >Customize text specifically for X /
                                        Twitter</span
                                    >
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="hasTwitterOverride"
                                        type="button"
                                        @click="resetToUniversal('twitter')"
                                        class="cursor-pointer text-[11px] text-muted-foreground underline hover:text-destructive"
                                    >
                                        Reset to Universal
                                    </button>
                                    <button
                                        v-if="hasTwitterOverride && content"
                                        type="button"
                                        @click="copyFromUniversal('twitter')"
                                        class="cursor-pointer text-[11px] text-primary hover:underline"
                                    >
                                        Copy Universal Text
                                    </button>
                                </div>
                            </div>

                            <textarea
                                v-if="hasTwitterOverride"
                                v-model="twitterContent"
                                rows="6"
                                placeholder="Draft concise, punchy copy tailored for the X / Twitter audience..."
                                class="w-full resize-none border-0 bg-transparent text-sm leading-relaxed text-foreground placeholder:text-muted-foreground/60 focus:outline-hidden"
                            ></textarea>

                            <div
                                v-else
                                class="space-y-2.5 rounded-xl border border-dashed border-border/80 bg-muted/20 p-4 text-center"
                            >
                                <p class="text-xs text-muted-foreground">
                                    Currently mirroring the
                                    <strong class="text-foreground"
                                        >Universal Post</strong
                                    >
                                    text for X / Twitter.
                                </p>
                                <div
                                    v-if="content"
                                    class="rounded-lg border border-border/50 bg-card/70 p-3 text-left"
                                >
                                    <p
                                        class="line-clamp-3 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                    >
                                        {{ content }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="copyFromUniversal('twitter')"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-2xs transition hover:bg-muted"
                                >
                                    <span
                                        >Customize specifically for X /
                                        Twitter</span
                                    >
                                </button>
                            </div>
                        </div>

                        <!-- LinkedIn Override Tab Area -->
                        <div
                            v-else-if="activeTab === 'linkedin'"
                            class="space-y-2.5"
                        >
                            <!-- Not targeted alert -->
                            <div
                                v-if="
                                    !selectedPlatforms.has('linkedin') &&
                                    selectedAccountIds.length > 0
                                "
                                class="flex items-center justify-between rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300"
                            >
                                <span
                                    >LinkedIn is currently not targeted for this
                                    post.</span
                                >
                                <button
                                    type="button"
                                    @click="addPlatformAccounts('linkedin')"
                                    class="cursor-pointer font-semibold underline underline-offset-2 hover:opacity-80"
                                >
                                    + Add LinkedIn to Targets
                                </button>
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-border/50 pb-1.5 text-xs"
                            >
                                <label
                                    class="flex cursor-pointer items-center gap-2 font-medium text-foreground"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="hasLinkedInOverride"
                                        @change="
                                            if (
                                                hasLinkedInOverride &&
                                                !linkedinContent
                                            )
                                                linkedinContent = content;
                                        "
                                        class="rounded border-border text-primary focus:ring-primary"
                                    />
                                    <span
                                        >Customize text specifically for
                                        LinkedIn</span
                                    >
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="hasLinkedInOverride"
                                        type="button"
                                        @click="resetToUniversal('linkedin')"
                                        class="cursor-pointer text-[11px] text-muted-foreground underline hover:text-destructive"
                                    >
                                        Reset to Universal
                                    </button>
                                    <button
                                        v-if="hasLinkedInOverride && content"
                                        type="button"
                                        @click="copyFromUniversal('linkedin')"
                                        class="cursor-pointer text-[11px] text-primary hover:underline"
                                    >
                                        Copy Universal Text
                                    </button>
                                </div>
                            </div>

                            <textarea
                                v-if="hasLinkedInOverride"
                                v-model="linkedinContent"
                                rows="6"
                                placeholder="Add rich professional insights, paragraph breaks, and formatting for LinkedIn..."
                                class="w-full resize-none border-0 bg-transparent text-sm leading-relaxed text-foreground placeholder:text-muted-foreground/60 focus:outline-hidden"
                            ></textarea>

                            <div
                                v-else
                                class="space-y-2.5 rounded-xl border border-dashed border-border/80 bg-muted/20 p-4 text-center"
                            >
                                <p class="text-xs text-muted-foreground">
                                    Currently mirroring the
                                    <strong class="text-foreground"
                                        >Universal Post</strong
                                    >
                                    text for LinkedIn.
                                </p>
                                <div
                                    v-if="content"
                                    class="rounded-lg border border-border/50 bg-card/70 p-3 text-left"
                                >
                                    <p
                                        class="line-clamp-3 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                    >
                                        {{ content }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="copyFromUniversal('linkedin')"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-2xs transition hover:bg-muted"
                                >
                                    <span
                                        >Customize specifically for
                                        LinkedIn</span
                                    >
                                </button>
                            </div>
                        </div>

                        <!-- Facebook Override Tab Area -->
                        <div
                            v-else-if="activeTab === 'facebook'"
                            class="space-y-2.5"
                        >
                            <!-- Not targeted alert -->
                            <div
                                v-if="
                                    !selectedPlatforms.has('facebook') &&
                                    selectedAccountIds.length > 0
                                "
                                class="flex items-center justify-between rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300"
                            >
                                <span
                                    >Facebook is currently not targeted for this
                                    post.</span
                                >
                                <button
                                    type="button"
                                    @click="addPlatformAccounts('facebook')"
                                    class="cursor-pointer font-semibold underline underline-offset-2 hover:opacity-80"
                                >
                                    + Add Facebook to Targets
                                </button>
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-border/50 pb-1.5 text-xs"
                            >
                                <label
                                    class="flex cursor-pointer items-center gap-2 font-medium text-foreground"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="hasFacebookOverride"
                                        @change="
                                            if (
                                                hasFacebookOverride &&
                                                !facebookContent
                                            )
                                                facebookContent = content;
                                        "
                                        class="rounded border-border text-primary focus:ring-primary"
                                    />
                                    <span
                                        >Customize text specifically for
                                        Facebook</span
                                    >
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="hasFacebookOverride"
                                        type="button"
                                        @click="resetToUniversal('facebook')"
                                        class="cursor-pointer text-[11px] text-muted-foreground underline hover:text-destructive"
                                    >
                                        Reset to Universal
                                    </button>
                                    <button
                                        v-if="hasFacebookOverride && content"
                                        type="button"
                                        @click="copyFromUniversal('facebook')"
                                        class="cursor-pointer text-[11px] text-primary hover:underline"
                                    >
                                        Copy Universal Text
                                    </button>
                                </div>
                            </div>

                            <textarea
                                v-if="hasFacebookOverride"
                                v-model="facebookContent"
                                rows="6"
                                placeholder="Draft engaging storytelling, updates, and discussions for your Facebook community..."
                                class="w-full resize-none border-0 bg-transparent text-sm leading-relaxed text-foreground placeholder:text-muted-foreground/60 focus:outline-hidden"
                            ></textarea>

                            <div
                                v-else
                                class="space-y-2.5 rounded-xl border border-dashed border-border/80 bg-muted/20 p-4 text-center"
                            >
                                <p class="text-xs text-muted-foreground">
                                    Currently mirroring the
                                    <strong class="text-foreground"
                                        >Universal Post</strong
                                    >
                                    text for Facebook.
                                </p>
                                <div
                                    v-if="content"
                                    class="rounded-lg border border-border/50 bg-card/70 p-3 text-left"
                                >
                                    <p
                                        class="line-clamp-3 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                    >
                                        {{ content }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="copyFromUniversal('facebook')"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground shadow-2xs transition hover:bg-muted"
                                >
                                    <span
                                        >Customize specifically for
                                        Facebook</span
                                    >
                                </button>
                            </div>
                        </div>

                        <!-- INLINE ATTACHMENT 1: Media Preview Inside Post -->
                        <div
                            v-if="mediaUrl"
                            class="relative overflow-hidden rounded-xl border border-border bg-muted/20"
                        >
                            <img
                                :src="mediaUrl"
                                alt="Attached media"
                                class="max-h-80 w-full object-cover"
                            />
                            <div
                                class="absolute top-2 right-2 flex items-center gap-1.5"
                            >
                                <button
                                    type="button"
                                    @click="isCropperOpen = true"
                                    class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-border/80 bg-background/80 px-2.5 py-1 text-xs font-medium text-foreground shadow-sm backdrop-blur-md transition hover:bg-background"
                                >
                                    <Crop class="size-3.5 text-primary" />
                                    <span>Crop & Aspect</span>
                                </button>
                                <button
                                    type="button"
                                    @click="removeImage"
                                    class="flex size-7 cursor-pointer items-center justify-center rounded-lg border border-border/80 bg-background/80 text-muted-foreground shadow-sm backdrop-blur-md transition hover:bg-destructive/10 hover:text-destructive"
                                    title="Remove photo"
                                >
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- INLINE ATTACHMENT 2: Open Graph Link Preview Card Inside Post -->
                        <div
                            v-if="linkMetadata"
                            class="relative overflow-hidden rounded-xl border border-border bg-card transition hover:border-primary/40"
                        >
                            <button
                                type="button"
                                @click="removeLinkPreview"
                                class="absolute top-2 right-2 z-10 flex size-6 cursor-pointer items-center justify-center rounded-full bg-background/80 text-muted-foreground shadow-sm backdrop-blur-md transition hover:bg-destructive/10 hover:text-destructive"
                                title="Remove link preview"
                            >
                                <X class="size-3" />
                            </button>
                            <img
                                v-if="linkMetadata.image_url"
                                :src="linkMetadata.image_url"
                                alt="OG preview"
                                class="h-40 w-full object-cover"
                            />
                            <div class="bg-muted/20 p-3">
                                <span
                                    class="font-mono text-[10px] text-muted-foreground uppercase"
                                >
                                    {{ linkMetadata.domain || 'example.com' }}
                                </span>
                                <h4
                                    class="mt-0.5 line-clamp-1 text-xs font-bold text-foreground"
                                >
                                    {{ linkMetadata.title }}
                                </h4>
                                <p
                                    class="mt-0.5 line-clamp-2 text-[11px] text-muted-foreground"
                                >
                                    {{ linkMetadata.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Optional Manual Link Input Toolbar Drawer -->
                        <div
                            v-if="isLinkInputOpen && !linkMetadata"
                            class="flex items-center gap-2 rounded-lg border border-border bg-muted/30 p-2"
                        >
                            <Link2 class="ml-1 size-4 shrink-0 text-primary" />
                            <input
                                v-model="linkUrlInput"
                                type="url"
                                placeholder="https://example.com/blog/article"
                                class="flex-1 bg-transparent text-xs text-foreground placeholder:text-muted-foreground focus:outline-hidden"
                                @keydown.enter.prevent="fetchLinkMetadata()"
                            />
                            <button
                                type="button"
                                @click="fetchLinkMetadata()"
                                :disabled="isFetchingLink || !linkUrlInput"
                                class="cursor-pointer rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                {{
                                    isFetchingLink
                                        ? 'Detecting...'
                                        : 'Attach Card'
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- Inline Scheduling Bar (when enabled) -->
                    <div
                        v-if="isScheduled"
                        class="border-t border-border/60 bg-muted/25 px-4 py-3 transition-all"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-2">
                                <Clock class="size-4 text-primary" />
                                <span
                                    class="text-xs font-semibold text-foreground"
                                    >Schedule Publication ({{
                                        workspaceStore.currentOrg?.timezone ||
                                        'UTC'
                                    }}):</span
                                >
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="setSchedulePreset(24)"
                                    class="cursor-pointer rounded border border-border bg-card px-2 py-0.5 text-[11px] font-medium text-muted-foreground hover:text-foreground"
                                >
                                    Tomorrow 10am
                                </button>
                                <button
                                    type="button"
                                    @click="setSchedulePreset(72)"
                                    class="cursor-pointer rounded border border-border bg-card px-2 py-0.5 text-[11px] font-medium text-muted-foreground hover:text-foreground"
                                >
                                    In 3 Days
                                </button>
                            </div>
                        </div>

                        <div
                            class="mt-2.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2"
                        >
                            <div>
                                <input
                                    v-model="scheduledDate"
                                    type="date"
                                    class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-hidden"
                                />
                            </div>
                            <div>
                                <input
                                    v-model="scheduledTime"
                                    type="time"
                                    class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-xs text-foreground focus:ring-1 focus:ring-primary focus:outline-hidden"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Composer Bottom Action Bar (Photo, Link, Schedule, Active Counter & Post Now CTA - Strictly No-Wrap) -->
                    <div
                        class="flex items-center justify-between gap-3 border-t border-border/70 bg-card px-4 py-3"
                    >
                        <!-- Left Media & Tool Attachments -->
                        <div class="flex shrink-0 items-center gap-1.5">
                            <input
                                type="file"
                                id="media-upload-native"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileUpload"
                            />
                            <label
                                for="media-upload-native"
                                class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-muted/30 px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                title="Attach photo or media"
                            >
                                <ImageIcon class="size-3.5 text-primary" />
                                <span>Photo</span>
                            </label>

                            <button
                                type="button"
                                @click="isLinkInputOpen = !isLinkInputOpen"
                                class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                                :class="
                                    linkMetadata || isLinkInputOpen
                                        ? 'border-primary/30 bg-primary/10 text-primary'
                                        : 'border-border bg-muted/30 text-foreground hover:bg-muted'
                                "
                                title="Attach link preview card"
                            >
                                <Link2 class="size-3.5" />
                                <span>Link</span>
                            </button>

                            <button
                                type="button"
                                @click="
                                    isScheduled = !isScheduled;
                                    if (isScheduled && !scheduledDate)
                                        setSchedulePreset(24);
                                "
                                class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                                :class="
                                    isScheduled
                                        ? 'border-primary/30 bg-primary/10 text-primary'
                                        : 'border-border bg-muted/30 text-foreground hover:bg-muted'
                                "
                                title="Toggle scheduled timing"
                            >
                                <Calendar class="size-3.5" />
                                <span>{{
                                    isScheduled ? 'Scheduled' : 'Schedule'
                                }}</span>
                            </button>
                        </div>

                        <!-- Right Publish / Schedule CTA (Adaptive Label) -->
                        <button
                            type="button"
                            @click="handleDispatch(false)"
                            :disabled="
                                isSubmitting ||
                                selectedAccountIds.length === 0 ||
                                (!content && !mediaUrl)
                            "
                            class="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-xl bg-primary px-5 py-2 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                        >
                            <span
                                v-if="isSubmitting"
                                class="size-3.5 animate-spin rounded-full border-2 border-primary-foreground border-t-transparent"
                            ></span>
                            <Send v-else class="size-3.5" />
                            <span>{{
                                isScheduled
                                    ? isTwitterOnly
                                        ? 'Schedule Tweet'
                                        : isLinkedInOnly
                                          ? 'Schedule to LinkedIn'
                                          : isFacebookOnly
                                            ? 'Schedule to Facebook'
                                            : 'Schedule Post'
                                    : isTwitterOnly
                                      ? 'Post Tweet'
                                      : isLinkedInOnly
                                        ? 'Post to LinkedIn'
                                        : isFacebookOnly
                                          ? 'Post to Facebook'
                                          : 'Publish Now'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Target Publishing Channels Integration Dock (BELOW COMPOSE) -->
                <div
                    class="space-y-3.5 overflow-hidden rounded-2xl border border-border bg-card p-4.5 shadow-xs"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                Target Publishing Channels
                            </span>
                            <span
                                class="rounded-full bg-primary/10 px-2 py-0.5 font-mono text-[11px] font-semibold text-primary"
                            >
                                {{ selectedAccountIds.length }} /
                                {{ healthyAccounts.length }} Active
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="selectAllAvailable"
                                class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    selectedAccountIds.length ===
                                        healthyAccounts.length &&
                                    healthyAccounts.length > 0
                                        ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                        : 'border border-border bg-background text-foreground hover:bg-muted'
                                "
                            >
                                All Active
                            </button>
                            <button
                                type="button"
                                @click="selectPlatformOnly('twitter')"
                                class="flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    isTwitterOnly
                                        ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                        : 'border border-border bg-background text-foreground hover:bg-muted'
                                "
                                title="Target X / Twitter accounts only"
                            >
                                <span class="text-[10px] font-bold">𝕏</span>
                                <span>Twitter only</span>
                            </button>
                            <button
                                type="button"
                                @click="selectPlatformOnly('linkedin')"
                                class="flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    isLinkedInOnly
                                        ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                        : 'border border-border bg-background text-foreground hover:bg-muted'
                                "
                                title="Target LinkedIn accounts only"
                            >
                                <span
                                    class="text-[10px] font-bold text-[#0077b5]"
                                    >in</span
                                >
                                <span>LinkedIn only</span>
                            </button>
                            <button
                                type="button"
                                @click="selectPlatformOnly('facebook')"
                                class="flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    isFacebookOnly
                                        ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                        : 'border border-border bg-background text-foreground hover:bg-muted'
                                "
                                title="Target Facebook accounts only"
                            >
                                <span
                                    class="text-[10px] font-bold text-[#1877f2]"
                                    >f</span
                                >
                                <span>Facebook only</span>
                            </button>
                            <button
                                type="button"
                                @click="deselectAll"
                                class="cursor-pointer rounded-lg border border-transparent px-2 py-1 text-xs font-medium text-muted-foreground transition hover:text-destructive"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Revoked / No-Access Feedback Alert Banner -->
                    <div
                        v-if="revokedAccountAlert"
                        class="flex items-start justify-between gap-3 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-800 transition dark:text-rose-200"
                    >
                        <div class="flex items-start gap-2.5">
                            <AlertTriangle
                                class="mt-0.5 size-4 shrink-0 text-rose-600 dark:text-rose-400"
                            />
                            <div>
                                <p class="font-semibold">
                                    No Access:
                                    {{ revokedAccountAlert.name }} ({{
                                        revokedAccountAlert.handle ||
                                        '@channel'
                                    }})
                                </p>
                                <p class="mt-0.5 text-[11px] opacity-90">
                                    Channel credentials were disconnected or
                                    revoked. Please reconnect this account in
                                    <Link
                                        :href="`/w/${workspaceStore.activeOrgSlug}/settings`"
                                        class="font-semibold underline underline-offset-2 hover:opacity-80"
                                    >
                                        Settings &rarr;
                                    </Link>
                                    before dispatching.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="revokedAccountAlert = null"
                            class="cursor-pointer rounded p-1 text-rose-600 hover:bg-rose-500/20 dark:text-rose-300"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>

                    <!-- Standout Channel Cards Grid -->
                    <div
                        class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="acc in accounts"
                            :key="acc.id"
                            @click="toggleAccount(acc)"
                            class="group relative flex cursor-pointer items-center justify-between rounded-xl border p-2.5 transition select-none"
                            :class="[
                                acc.status === 'revoked'
                                    ? 'border-dashed border-rose-500/40 bg-rose-500/5 hover:border-rose-500/60'
                                    : selectedAccountIds.includes(acc.id)
                                      ? 'border-primary/50 bg-primary/5 shadow-2xs ring-1 ring-primary/25'
                                      : 'border-border/80 bg-background/60 hover:border-border hover:bg-muted/30',
                            ]"
                            :title="
                                acc.status === 'revoked'
                                    ? 'Access Revoked - Click for feedback'
                                    : acc.name
                            "
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div class="relative shrink-0">
                                    <img
                                        :src="
                                            acc.avatar_url ||
                                            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100'
                                        "
                                        :alt="acc.name"
                                        class="size-9 rounded-full border border-border object-cover"
                                    />
                                    <!-- Provider Micro Badge -->
                                    <span
                                        class="absolute -right-1 -bottom-1 flex size-4 items-center justify-center rounded-full text-[9px] font-bold shadow-2xs"
                                        :class="[
                                            acc.provider === 'twitter'
                                                ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                                : acc.provider === 'linkedin'
                                                  ? 'bg-[#0077b5] text-white'
                                                  : 'bg-[#1877f2] text-white',
                                        ]"
                                    >
                                        {{
                                            acc.provider === 'twitter'
                                                ? '𝕏'
                                                : acc.provider === 'linkedin'
                                                  ? 'in'
                                                  : 'f'
                                        }}
                                    </span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="truncate text-xs font-semibold text-foreground"
                                        >
                                            {{ acc.name }}
                                        </span>
                                    </div>
                                    <p
                                        class="truncate font-mono text-[10px] text-muted-foreground"
                                    >
                                        {{
                                            acc.handle ||
                                            '@' +
                                                acc.name
                                                    .toLowerCase()
                                                    .replace(/\s+/g, '')
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Status Pill & Checkbox indicator -->
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span
                                    v-if="acc.status === 'revoked'"
                                    class="inline-flex items-center gap-1 rounded-md bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-semibold text-rose-600 dark:text-rose-400"
                                >
                                    <AlertTriangle class="size-2.5" />
                                    <span>No Access</span>
                                </span>
                                <span
                                    v-else-if="acc.status === 'cooling'"
                                    class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[9px] font-semibold text-amber-600 dark:text-amber-400"
                                >
                                    <Clock class="size-2.5" />
                                    <span>Cooldown</span>
                                </span>
                                <span
                                    v-else-if="acc.status === 'expiring'"
                                    class="inline-flex items-center gap-1 rounded-md bg-yellow-500/10 px-1.5 py-0.5 text-[9px] font-semibold text-yellow-700 dark:text-yellow-400"
                                >
                                    <span>Expiring</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    <span>Ready</span>
                                </span>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        v-if="
                                            acc.status !== 'revoked' &&
                                            (selectedAccountIds.length > 1 ||
                                                !selectedAccountIds.includes(
                                                    acc.id,
                                                ))
                                        "
                                        type="button"
                                        @click.stop="
                                            selectSingleAccount(acc.id)
                                        "
                                        class="cursor-pointer text-[10px] text-muted-foreground/70 hover:text-primary hover:underline"
                                        title="Target only this channel"
                                    >
                                        Solo
                                    </button>
                                    <div
                                        class="flex size-4 items-center justify-center rounded-full border transition"
                                        :class="[
                                            acc.status === 'revoked'
                                                ? 'border-rose-400/40 bg-rose-500/10 text-rose-500'
                                                : selectedAccountIds.includes(
                                                        acc.id,
                                                    )
                                                  ? 'border-primary bg-primary text-primary-foreground'
                                                  : 'border-border bg-background',
                                        ]"
                                    >
                                        <Check
                                            v-if="
                                                selectedAccountIds.includes(
                                                    acc.id,
                                                ) && acc.status !== 'revoked'
                                            "
                                            class="size-2.5"
                                        />
                                        <X
                                            v-else-if="acc.status === 'revoked'"
                                            class="size-2.5"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (lg:col-span-5): Sticky Live Native Feed Preview -->
            <div class="sticky top-6 space-y-4 lg:col-span-5">
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
                >
                    <!-- Preview Platform Switcher -->
                    <div
                        class="flex items-center justify-between border-b border-border bg-muted/30 p-3"
                    >
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Live Feed Preview
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-1 rounded-lg border border-border bg-muted p-0.5"
                        >
                            <button
                                type="button"
                                @click="previewPlatform = 'twitter'"
                                class="flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    previewPlatform === 'twitter'
                                        ? 'bg-card font-semibold text-foreground shadow-2xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                            >
                                <span>X / Twitter</span>
                            </button>
                            <button
                                type="button"
                                @click="previewPlatform = 'linkedin'"
                                class="flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    previewPlatform === 'linkedin'
                                        ? 'bg-card font-semibold text-foreground shadow-2xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                            >
                                <span>LinkedIn</span>
                            </button>
                            <button
                                type="button"
                                @click="previewPlatform = 'facebook'"
                                class="flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    previewPlatform === 'facebook'
                                        ? 'bg-card font-semibold text-foreground shadow-2xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                            >
                                <span>Facebook</span>
                            </button>
                        </div>
                    </div>

                    <!-- Native Preview Container -->
                    <div
                        class="flex min-h-115 items-center justify-center bg-muted/20 p-4"
                    >
                        <!-- 1. X / Twitter Native Card Style -->
                        <div
                            v-if="previewPlatform === 'twitter'"
                            class="w-full max-w-md space-y-3 rounded-2xl border border-border bg-card p-4 shadow-sm"
                        >
                            <div class="flex items-start gap-3">
                                <img
                                    :src="
                                        activeTwitterAccount?.avatar_url ||
                                        activeAccountAvatars[0] ||
                                        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150'
                                    "
                                    alt="X Profile"
                                    class="size-10 shrink-0 rounded-full object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="truncate text-sm font-bold text-foreground"
                                        >
                                            {{
                                                activeTwitterAccount?.name ||
                                                workspaceStore.currentOrg
                                                    ?.name ||
                                                'Acme Studio'
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                activeTwitterAccount?.handle ||
                                                '@' +
                                                    (
                                                        workspaceStore
                                                            .currentOrg?.slug ||
                                                        'acmestudio'
                                                    ).replace(/-/g, '')
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >· 1m</span
                                        >
                                    </div>
                                    <p
                                        class="mt-1 text-sm leading-relaxed wrap-break-word whitespace-pre-wrap text-foreground"
                                    >
                                        {{
                                            resolvedTwitterText ||
                                            'Your tweet text will appear here...'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Media or Link Card -->
                            <div
                                v-if="mediaUrl"
                                class="overflow-hidden rounded-xl border border-border"
                            >
                                <img
                                    :src="mediaUrl"
                                    alt="Media"
                                    class="max-h-64 w-full object-cover"
                                />
                            </div>

                            <div
                                v-else-if="linkMetadata"
                                class="overflow-hidden rounded-xl border border-border transition hover:bg-muted/40"
                            >
                                <img
                                    v-if="linkMetadata.image_url"
                                    :src="linkMetadata.image_url"
                                    alt="OG preview"
                                    class="h-36 w-full object-cover"
                                />
                                <div class="bg-muted/30 p-3">
                                    <p
                                        class="font-mono text-[11px] text-muted-foreground uppercase"
                                    >
                                        {{
                                            linkMetadata.domain || 'example.com'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-1 text-xs font-bold text-foreground"
                                    >
                                        {{ linkMetadata.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-2 text-[11px] text-muted-foreground"
                                    >
                                        {{ linkMetadata.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <div
                                class="flex items-center justify-between px-2 pt-2 text-xs text-muted-foreground"
                            >
                                <span
                                    class="flex cursor-pointer items-center gap-1 hover:text-primary"
                                >
                                    <MessageSquare class="size-3.5" /> 12
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1 hover:text-emerald-500"
                                >
                                    <Repeat2 class="size-3.5" /> 4
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1 hover:text-rose-500"
                                >
                                    <Heart class="size-3.5" /> 89
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1 hover:text-primary"
                                >
                                    <Share class="size-3.5" />
                                </span>
                            </div>
                        </div>

                        <!-- 2. LinkedIn Native Card Style -->
                        <div
                            v-else-if="previewPlatform === 'linkedin'"
                            class="w-full max-w-md space-y-3 rounded-xl border border-border bg-card p-4 shadow-sm"
                        >
                            <div class="flex items-start gap-3">
                                <img
                                    :src="
                                        activeLinkedInAccount?.avatar_url ||
                                        activeAccountAvatars[0] ||
                                        'https://images.unsplash.com/photo-1572021335469-31706a17aaef?w=150'
                                    "
                                    alt="LinkedIn Profile"
                                    class="size-10 shrink-0 rounded-md object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="truncate text-sm font-bold text-foreground"
                                    >
                                        {{
                                            activeLinkedInAccount?.name ||
                                            workspaceStore.currentOrg?.name ||
                                            'Acme Corp'
                                        }}
                                    </h4>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        14,290 followers · 2m · 🌐
                                    </p>
                                </div>
                            </div>

                            <p
                                class="text-xs leading-relaxed wrap-break-word whitespace-pre-wrap text-foreground"
                            >
                                {{
                                    resolvedLinkedInText ||
                                    'Your LinkedIn post text will appear here...'
                                }}
                            </p>

                            <!-- Media or Link Card -->
                            <div
                                v-if="mediaUrl"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <img
                                    :src="mediaUrl"
                                    alt="Media"
                                    class="max-h-64 w-full object-cover"
                                />
                            </div>

                            <div
                                v-else-if="linkMetadata"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <img
                                    v-if="linkMetadata.image_url"
                                    :src="linkMetadata.image_url"
                                    alt="OG preview"
                                    class="h-40 w-full object-cover"
                                />
                                <div
                                    class="border-t border-border bg-muted/40 p-3"
                                >
                                    <p
                                        class="font-mono text-[10px] text-muted-foreground uppercase"
                                    >
                                        {{
                                            linkMetadata.domain || 'example.com'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-1 text-xs font-bold text-foreground"
                                    >
                                        {{ linkMetadata.title }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <div
                                class="flex items-center justify-around border-t border-border/80 pt-2 text-xs text-muted-foreground"
                            >
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <ThumbsUp class="size-3.5" /> Like
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <MessageCircle class="size-3.5" />
                                    Comment
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <Share2 class="size-3.5" /> Repost
                                </span>
                            </div>
                        </div>

                        <!-- 3. Facebook Native Card Style -->
                        <div
                            v-else-if="previewPlatform === 'facebook'"
                            class="w-full max-w-md space-y-3 rounded-xl border border-border bg-card p-4 shadow-sm"
                        >
                            <div class="flex items-start gap-3">
                                <img
                                    :src="
                                        activeFacebookAccount?.avatar_url ||
                                        activeAccountAvatars[0] ||
                                        'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150'
                                    "
                                    alt="Facebook Profile"
                                    class="size-10 shrink-0 rounded-full object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="truncate text-sm font-bold text-foreground"
                                    >
                                        {{
                                            activeFacebookAccount?.name ||
                                            workspaceStore.currentOrg?.name ||
                                            'Acme Official'
                                        }}
                                    </h4>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Just now · 👥
                                    </p>
                                </div>
                            </div>

                            <p
                                class="text-xs leading-relaxed wrap-break-word whitespace-pre-wrap text-foreground"
                            >
                                {{
                                    resolvedFacebookText ||
                                    'Your Facebook post text will appear here...'
                                }}
                            </p>

                            <!-- Media or Link Card -->
                            <div
                                v-if="mediaUrl"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <img
                                    :src="mediaUrl"
                                    alt="Media"
                                    class="max-h-64 w-full object-cover"
                                />
                            </div>

                            <div
                                v-else-if="linkMetadata"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <img
                                    v-if="linkMetadata.image_url"
                                    :src="linkMetadata.image_url"
                                    alt="OG preview"
                                    class="h-40 w-full object-cover"
                                />
                                <div
                                    class="border-t border-border bg-muted/40 p-3"
                                >
                                    <p
                                        class="font-mono text-[10px] text-muted-foreground uppercase"
                                    >
                                        {{
                                            linkMetadata.domain || 'example.com'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-1 text-xs font-bold text-foreground"
                                    >
                                        {{ linkMetadata.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-2 text-[11px] text-muted-foreground"
                                    >
                                        {{ linkMetadata.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <div
                                class="flex items-center justify-around border-t border-border/80 pt-2 text-xs text-muted-foreground"
                            >
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <ThumbsUp class="size-3.5" /> Like
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <MessageCircle class="size-3.5" />
                                    Comment
                                </span>
                                <span
                                    class="flex cursor-pointer items-center gap-1.5 py-1 hover:text-primary"
                                >
                                    <Share2 class="size-3.5" /> Share
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Cropper Modal -->
        <ImageCropperModal
            :is-open="isCropperOpen"
            :image-src="mediaUrl"
            @close="isCropperOpen = false"
            @cropped="handleCropped"
        />
    </div>
</template>
