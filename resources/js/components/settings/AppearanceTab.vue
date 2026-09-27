<script setup lang="ts">
import { ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import {
    Sun,
    Moon,
    Monitor,
    Palette,
    Check,
    LayoutGrid,
    List,
    Bell,
} from '@lucide/vue';

const { appearance, updateAppearance } = useAppearance();

// Feed density state (local preference)
const feedDensity = ref<'spacious' | 'compact'>('spacious');

// Notification toggles
const inAppNotifications = ref(true);
const tokenExpiryAlerts = ref(true);
const weeklyDigest = ref(false);

const themes = [
    {
        id: 'light',
        name: 'Light',
        description: 'Clean, high-contrast crisp theme for daytime publishing',
        icon: Sun,
    },
    {
        id: 'dark',
        name: 'Dark',
        description: 'Obsidian dark palette easy on the eyes in low light',
        icon: Moon,
    },
    {
        id: 'system',
        name: 'System Default',
        description: 'Automatically synchronizes with your device preference',
        icon: Monitor,
    },
] as const;
</script>

<template>
    <div class="space-y-8">
        <!-- 1. Color Theme Mode -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <Palette class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Interface Theme
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Customize how SocialSync looks on your device.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <button
                    v-for="theme in themes"
                    :key="theme.id"
                    type="button"
                    @click="updateAppearance(theme.id)"
                    class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 text-left transition-all focus:outline-none"
                    :class="
                        appearance === theme.id
                            ? 'border-primary bg-accent/40 shadow-xs ring-2 ring-primary/20'
                            : 'border-border bg-background hover:bg-accent/20'
                    "
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <component
                                :is="theme.icon"
                                class="h-4 w-4 text-foreground"
                            />
                            <span class="text-xs font-bold text-foreground">{{
                                theme.name
                            }}</span>
                        </div>
                        <span
                            v-if="appearance === theme.id"
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground"
                        >
                            <Check class="h-2.5 w-2.5 stroke-[3]" />
                        </span>
                    </div>

                    <!-- Mini theme illustration representation -->
                    <div
                        class="flex h-16 w-full gap-1.5 rounded-md border p-2 transition-colors"
                        :class="
                            theme.id === 'dark'
                                ? 'border-slate-800 bg-slate-950'
                                : theme.id === 'light'
                                  ? 'border-slate-200 bg-white'
                                  : 'border-slate-300 bg-gradient-to-r from-white to-slate-950 dark:border-slate-800'
                        "
                    >
                        <div
                            class="w-4 shrink-0 rounded-xs"
                            :class="
                                theme.id === 'dark'
                                    ? 'bg-slate-800'
                                    : theme.id === 'light'
                                      ? 'bg-slate-100'
                                      : 'bg-slate-300 dark:bg-slate-800'
                            "
                        />
                        <div class="flex-1 space-y-1 py-1">
                            <div
                                class="h-2 w-3/4 rounded-xs"
                                :class="
                                    theme.id === 'dark'
                                        ? 'bg-slate-700'
                                        : theme.id === 'light'
                                          ? 'bg-slate-200'
                                          : 'bg-slate-400 dark:bg-slate-700'
                                "
                            />
                            <div
                                class="h-2 w-1/2 rounded-xs"
                                :class="
                                    theme.id === 'dark'
                                        ? 'bg-slate-800'
                                        : theme.id === 'light'
                                          ? 'bg-slate-100'
                                          : 'bg-slate-500 dark:bg-slate-800'
                                "
                            />
                        </div>
                    </div>

                    <p
                        class="mt-3 text-[11px] leading-snug text-muted-foreground"
                    >
                        {{ theme.description }}
                    </p>
                </button>
            </div>
        </section>

        <!-- 2. Feed Density & Layout Preference -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <LayoutGrid class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Content Feed Display Density
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Select how scheduled post queues and campaign feeds
                        render by default.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div
                    @click="feedDensity = 'spacious'"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                    :class="
                        feedDensity === 'spacious'
                            ? 'border-primary bg-accent/40 ring-1 ring-primary/20'
                            : 'border-border bg-background hover:bg-muted/40'
                    "
                >
                    <LayoutGrid class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-foreground"
                                >Spacious (Card View)</span
                            >
                            <Check
                                v-if="feedDensity === 'spacious'"
                                class="h-3.5 w-3.5 text-primary"
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Visual previews of image attachments, platform
                            badges, and scheduled dispatch timestamps.
                        </p>
                    </div>
                </div>

                <div
                    @click="feedDensity = 'compact'"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                    :class="
                        feedDensity === 'compact'
                            ? 'border-primary bg-accent/40 ring-1 ring-primary/20'
                            : 'border-border bg-background hover:bg-muted/40'
                    "
                >
                    <List class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-foreground"
                                >Compact (Dense Table)</span
                            >
                            <Check
                                v-if="feedDensity === 'compact'"
                                class="h-3.5 w-3.5 text-primary"
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Streamlined single-line layout optimized for
                            managing extensive publishing queues and bulk edits.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Notification Preferences -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <Bell class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Notification & Alert Channels
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Configure which events trigger real-time toasts and
                        alerts.
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <!-- In-App Toasts -->
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-3"
                >
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >In-App Publishing Notifications</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Receive notifications when scheduled posts succeed
                            or encounter network rate limits.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="inAppNotifications = !inAppNotifications"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="inAppNotifications ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                inAppNotifications
                                    ? 'translate-x-4'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <!-- Token Expiry Alerts -->
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-3"
                >
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Critical Token Expiration Warnings</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Show top warning banner 7 days before any Facebook
                            or LinkedIn OAuth refresh token expires.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="tokenExpiryAlerts = !tokenExpiryAlerts"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="tokenExpiryAlerts ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                tokenExpiryAlerts
                                    ? 'translate-x-4'
                                    : 'translate-x-0'
                            "
                        />
                    </button>
                </div>

                <!-- Weekly Digest -->
                <div class="flex items-center justify-between">
                    <div class="space-y-0.5 pr-4">
                        <span
                            class="block text-xs font-semibold text-foreground"
                            >Weekly Publishing Performance Digest</span
                        >
                        <span class="text-[11px] text-muted-foreground">
                            Receive an email summary of post reach, engagement,
                            and schedule compliance every Monday.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="weeklyDigest = !weeklyDigest"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="weeklyDigest ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="
                                weeklyDigest ? 'translate-x-4' : 'translate-x-0'
                            "
                        />
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
