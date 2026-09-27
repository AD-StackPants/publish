<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useWorkspaceStore } from '@/stores/workspace';
import {
    Send,
    Calendar,
    Clock,
    Sparkles,
    Check,
    CheckCircle2,
    Layers,
    Shield,
    Users,
    ArrowRight,
    Zap,
    AlertTriangle,
    Eye,
    SlidersHorizontal,
    Globe,
    ExternalLink,
    ChevronDown,
    Plus,
    CreditCard,
    BarChart3,
    Menu,
    X,
} from '@lucide/vue';

const page = usePage();
const workspaceStore = useWorkspaceStore();
const isAuthenticated = computed(
    () => !!(page.props.auth as { user: unknown } | null)?.user,
);

const tenantSlug = computed(() => {
    return (
        workspaceStore.activeOrgSlug || (page.props.tenant_slug as string) || ''
    );
});

// Auth-aware deep link — redirects guests to login, sends authed users to their workspace.
const workspaceHref = (path = '/posts') => {
    if (!isAuthenticated.value) {
        return '/login';
    }
    const slug = tenantSlug.value;
    if (slug) {
        return `/w/${slug}${path}`;
    }
    return path === '/posts'
        ? '/dashboard'
        : `/dashboard?redirect=${encodeURIComponent(path)}`;
};

onMounted(() => {
    if (isAuthenticated.value && workspaceStore.organizations.length === 0) {
        void workspaceStore.fetchOrganizations();
    }
});

// Mobile menu toggle
const isMobileMenuOpen = ref(false);

// Billing Cycle Toggle for Pricing
const billingCycle = ref<'monthly' | 'yearly'>('yearly');

// Interactive Demo State inside the Studio Showcase
const demoTab = ref<'universal' | 'twitter' | 'linkedin' | 'facebook'>(
    'universal',
);
const demoText = ref(
    'Excited to announce the launch of Posexei! 🚀 One studio to write, customize, and publish seamlessly across all your social channels. Authentic live previews included.',
);

// Active FAQ accordions
const openFaq = ref<number | null>(0);
const toggleFaq = (index: number) => {
    openFaq.value = openFaq.value === index ? null : index;
};
</script>

<template>
    <div
        class="min-h-screen bg-background text-foreground selection:bg-primary/20 selection:text-primary"
    >
        <Head
            title="Posexei — Multi-Tenant Social Media Publishing & Management Platform"
        />

        <!-- 1. Top Navigation Bar -->
        <header
            class="sticky top-0 z-50 w-full border-b border-border/80 bg-background/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <!-- Brand Logo -->
                <Link href="/" class="flex items-center gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-xl bg-primary text-lg font-black text-primary-foreground shadow-sm"
                    >
                        P
                    </div>
                    <div>
                        <span
                            class="text-base font-bold tracking-tight text-foreground"
                            >Posexei</span
                        >
                        <span
                            class="ml-2 hidden rounded-full border border-border/80 bg-muted/40 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-muted-foreground uppercase sm:inline-block"
                        >
                            Social Studio
                        </span>
                    </div>
                </Link>

                <!-- Desktop Nav Links -->
                <nav
                    class="hidden items-center gap-7 text-sm font-medium text-muted-foreground md:flex"
                >
                    <a href="#demo" class="transition hover:text-foreground"
                        >Composer Live</a
                    >
                    <a
                        href="#why-posexei"
                        class="transition hover:text-foreground"
                        >Why Posexei</a
                    >
                    <a href="#features" class="transition hover:text-foreground"
                        >Features</a
                    >
                    <a href="#pricing" class="transition hover:text-foreground"
                        >Pricing</a
                    >
                    <a href="#faq" class="transition hover:text-foreground"
                        >FAQ</a
                    >
                </nav>

                <!-- Actions & Mobile Hamburger -->
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!isAuthenticated"
                        href="/login"
                        class="hidden cursor-pointer items-center gap-1.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground sm:inline-flex"
                    >
                        Sign In
                    </Link>
                    <Link
                        :href="workspaceHref('/posts')"
                        class="hidden cursor-pointer items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 sm:inline-flex"
                    >
                        <span>{{
                            isAuthenticated ? 'Go to Studio' : 'Launch Studio'
                        }}</span>
                        <ArrowRight class="size-3.5" />
                    </Link>

                    <!-- Mobile Menu Hamburger Button -->
                    <button
                        type="button"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="flex size-9 cursor-pointer items-center justify-center rounded-lg border border-border bg-card text-foreground md:hidden"
                        aria-label="Toggle navigation menu"
                    >
                        <X v-if="isMobileMenuOpen" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="isMobileMenuOpen"
                class="space-y-4 border-b border-border bg-card px-4 py-4 shadow-lg md:hidden"
            >
                <nav
                    class="flex flex-col space-y-2.5 text-sm font-medium text-muted-foreground"
                >
                    <a
                        href="#demo"
                        @click="isMobileMenuOpen = false"
                        class="py-1 text-foreground transition hover:text-primary"
                    >
                        Composer Live
                    </a>
                    <a
                        href="#why-posexei"
                        @click="isMobileMenuOpen = false"
                        class="py-1 transition hover:text-primary"
                    >
                        Why Posexei
                    </a>
                    <a
                        href="#features"
                        @click="isMobileMenuOpen = false"
                        class="py-1 transition hover:text-primary"
                    >
                        Features
                    </a>
                    <a
                        href="#pricing"
                        @click="isMobileMenuOpen = false"
                        class="py-1 transition hover:text-primary"
                    >
                        Pricing
                    </a>
                    <a
                        href="#faq"
                        @click="isMobileMenuOpen = false"
                        class="py-1 transition hover:text-primary"
                    >
                        FAQ
                    </a>
                </nav>
                <div class="flex flex-col gap-2 border-t border-border/80 pt-3">
                    <Link
                        v-if="!isAuthenticated"
                        href="/login"
                        class="w-full rounded-xl border border-border py-2 text-center text-xs font-semibold text-foreground hover:bg-muted"
                    >
                        Sign In
                    </Link>
                    <Link
                        :href="workspaceHref('/composer')"
                        class="w-full rounded-xl bg-primary py-2 text-center text-xs font-semibold text-primary-foreground shadow-sm hover:bg-primary/90"
                    >
                        {{ isAuthenticated ? 'Go to Studio' : 'Launch Studio' }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- 2. Hero Section -->
        <section class="relative overflow-hidden pt-16 pb-16 md:pt-24 md:pb-20">
            <!-- Subtle Radial Gradient Background -->
            <div
                class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center"
            >
                <div
                    class="size-175 rounded-full bg-primary/5 blur-[120px] dark:bg-primary/10"
                ></div>
            </div>

            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <!-- Eyebrow Badge -->
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card px-3.5 py-1.5 text-xs font-medium text-foreground shadow-2xs"
                >
                    <span
                        class="flex size-2 animate-pulse rounded-full bg-emerald-500"
                    ></span>
                    <span>The Multi-Tenant Social Publishing Engine</span>
                    <span class="text-muted-foreground">·</span>
                    <span class="font-semibold text-primary">Version 2.0</span>
                </div>

                <!-- Main Headline -->
                <h1
                    class="mx-auto mt-6 max-w-4xl text-4xl leading-[1.12] font-extrabold tracking-tight text-foreground sm:text-5xl md:text-6xl"
                >
                    Create Once. Tailor Everywhere.
                    <br class="hidden sm:inline" />
                    <span class="text-muted-foreground"
                        >Publish with 100% Reliability.</span
                    >
                </h1>

                <!-- Subtitle -->
                <p
                    class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Posexei empowers social media managers, marketing agencies,
                    and modern creators to draft, schedule, and natively preview
                    posts across 𝕏 (Twitter), LinkedIn, and Facebook without
                    enterprise bloat.
                </p>

                <!-- Primary CTAs -->
                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-3.5"
                >
                    <Link
                        :href="workspaceHref('/composer')"
                        class="inline-flex cursor-pointer items-center gap-2.5 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-md transition hover:bg-primary/90"
                    >
                        <span>Open Universal Composer</span>
                        <Send class="size-4" />
                    </Link>
                    <Link
                        :href="workspaceHref('/posts')"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-border bg-card px-5 py-3 text-sm font-semibold text-foreground shadow-2xs transition hover:bg-muted"
                    >
                        <Calendar class="size-4 text-muted-foreground" />
                        <span>View Publishing Calendar</span>
                    </Link>
                </div>

                <!-- Supported Networks Strip -->
                <div
                    class="mt-12 flex flex-wrap items-center justify-center gap-6 text-xs font-medium text-muted-foreground sm:gap-10"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-5 items-center justify-center rounded-full bg-foreground text-[10px] font-bold text-background"
                            >𝕏</span
                        >
                        <span>X / Twitter (280 chars)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-5 items-center justify-center rounded-full bg-[#0077b5] text-[10px] font-bold text-white"
                            >in</span
                        >
                        <span>LinkedIn (3,000 chars)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-5 items-center justify-center rounded-full bg-[#1877f2] text-[10px] font-bold text-white"
                            >f</span
                        >
                        <span>Facebook Community</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Shield class="size-4 text-emerald-500" />
                        <span>Tenant Isolation</span>
                    </div>
                </div>

                <!-- Interactive Live Studio Showcase Merged in Hero -->
                <div
                    id="demo"
                    class="mx-auto mt-14 max-w-6xl scroll-mt-24 text-left sm:mt-16"
                >
                    <div
                        class="overflow-hidden rounded-2xl border border-border bg-card shadow-2xl ring-1 ring-border/50"
                    >
                        <!-- Window Top Chrome -->
                        <div
                            class="flex items-center justify-between border-b border-border bg-muted/40 px-4 py-3"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="size-3 rounded-full bg-rose-500/80"
                                ></span>
                                <span
                                    class="size-3 rounded-full bg-amber-500/80"
                                ></span>
                                <span
                                    class="size-3 rounded-full bg-emerald-500/80"
                                ></span>
                                <span
                                    class="ml-2 font-mono text-xs text-muted-foreground"
                                    >posexei.studio/composer</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    ● Live Synchronization
                                </span>
                            </div>
                        </div>

                        <!-- 2-Column Mock View: Compose on Left, Preview on Right -->
                        <div
                            class="grid grid-cols-1 divide-y divide-border lg:grid-cols-12 lg:divide-x lg:divide-y-0"
                        >
                            <!-- Left Column: Compose Surface -->
                            <div class="space-y-4 p-6 lg:col-span-7">
                                <!-- Channel Presets Bar -->
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-2"
                                >
                                    <span
                                        class="text-xs font-bold text-foreground"
                                        >Targeting:</span
                                    >
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="demoTab = 'universal'"
                                            class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                            :class="
                                                demoTab === 'universal'
                                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                                    : 'border border-border bg-background text-foreground'
                                            "
                                        >
                                            All Channels
                                        </button>
                                        <button
                                            type="button"
                                            @click="demoTab = 'twitter'"
                                            class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                            :class="
                                                demoTab === 'twitter'
                                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                                    : 'border border-border bg-background text-foreground'
                                            "
                                        >
                                            𝕏 Twitter only
                                        </button>
                                        <button
                                            type="button"
                                            @click="demoTab = 'linkedin'"
                                            class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                            :class="
                                                demoTab === 'linkedin'
                                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                                    : 'border border-border bg-background text-foreground'
                                            "
                                        >
                                            in LinkedIn only
                                        </button>
                                        <button
                                            type="button"
                                            @click="demoTab = 'facebook'"
                                            class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                            :class="
                                                demoTab === 'facebook'
                                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                                    : 'border border-border bg-background text-foreground'
                                            "
                                        >
                                            f Facebook only
                                        </button>
                                    </div>
                                </div>

                                <!-- Textarea Editor -->
                                <div
                                    class="rounded-xl border border-border/80 bg-muted/10 p-3"
                                >
                                    <textarea
                                        v-model="demoText"
                                        rows="5"
                                        class="w-full resize-none border-0 bg-transparent text-sm leading-relaxed text-foreground placeholder:text-muted-foreground focus:outline-hidden"
                                        placeholder="Write your social post..."
                                    ></textarea>
                                </div>

                                <!-- Bottom Action & Character Badges -->
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded bg-muted/60 px-2 py-0.5 font-mono text-[11px] text-muted-foreground"
                                        >
                                            𝕏 {{ demoText.length }}/280
                                        </span>
                                        <span
                                            class="rounded bg-muted/60 px-2 py-0.5 font-mono text-[11px] text-muted-foreground"
                                        >
                                            in {{ demoText.length }}/3,000
                                        </span>
                                    </div>
                                    <Link
                                        :href="workspaceHref('/composer')"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs hover:bg-primary/90"
                                    >
                                        <span>Try in App</span>
                                        <ArrowRight class="size-3" />
                                    </Link>
                                </div>
                            </div>

                            <!-- Right Column: Authentic Native Feed Preview -->
                            <div
                                class="flex flex-col justify-center bg-muted/20 p-6 lg:col-span-5"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <span
                                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                    >
                                        {{
                                            demoTab === 'linkedin'
                                                ? 'LinkedIn Preview'
                                                : demoTab === 'facebook'
                                                  ? 'Facebook Preview'
                                                  : '𝕏 / Twitter Preview'
                                        }}
                                    </span>
                                    <span
                                        class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                                        >Real-Time Native</span
                                    >
                                </div>

                                <!-- 𝕏 Native Card -->
                                <div
                                    v-if="
                                        demoTab === 'twitter' ||
                                        demoTab === 'universal'
                                    "
                                    class="space-y-2.5 rounded-2xl border border-border bg-card p-4 shadow-sm"
                                >
                                    <div class="flex items-start gap-3">
                                        <img
                                            src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100"
                                            alt="Avatar"
                                            class="size-9 rounded-full object-cover"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center gap-1.5 text-xs"
                                            >
                                                <span
                                                    class="font-bold text-foreground"
                                                    >Acme Studio</span
                                                >
                                                <span
                                                    class="text-muted-foreground"
                                                    >@acmestudio · 1m</span
                                                >
                                            </div>
                                            <p
                                                class="mt-1 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                            >
                                                {{
                                                    demoText ||
                                                    'Your post preview will appear here...'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- LinkedIn Native Card -->
                                <div
                                    v-else-if="demoTab === 'linkedin'"
                                    class="space-y-2.5 rounded-2xl border border-border bg-card p-4 shadow-sm"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-9 items-center justify-center rounded-full bg-[#0077b5] text-xs font-bold text-white"
                                        >
                                            in
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4
                                                class="text-xs font-bold text-foreground"
                                            >
                                                Acme Studio
                                            </h4>
                                            <p
                                                class="text-[10px] text-muted-foreground"
                                            >
                                                1,240 followers · Just now
                                            </p>
                                            <p
                                                class="mt-2 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                            >
                                                {{ demoText }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Facebook Native Card -->
                                <div
                                    v-else
                                    class="space-y-2.5 rounded-2xl border border-border bg-card p-4 shadow-sm"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-9 items-center justify-center rounded-full bg-[#1877f2] text-xs font-bold text-white"
                                        >
                                            f
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4
                                                class="text-xs font-bold text-foreground"
                                            >
                                                Acme Community
                                            </h4>
                                            <p
                                                class="text-[10px] text-muted-foreground"
                                            >
                                                Public · Just now
                                            </p>
                                            <p
                                                class="mt-2 text-xs leading-relaxed whitespace-pre-wrap text-foreground"
                                            >
                                                {{ demoText }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Problem & Solution Section: Why Posexei & Guaranteed Post Delivery -->
        <section
            id="why-posexei"
            class="border-t border-border bg-muted/20 py-20 md:py-28"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h2
                        class="text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        The Anti-Bloat Social Engine
                    </h2>
                    <h3
                        class="mt-3 text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Tired of Overpriced, Bloated Enterprise Schedulers?
                    </h3>
                    <p
                        class="mt-4 text-base leading-relaxed text-muted-foreground"
                    >
                        Legacy platforms like Hootsuite and Sprout Social were
                        built a decade ago for corporate bureaucracies, charging
                        $150–$300/mo for social listening and ticketing features
                        you will never use.
                        <strong
                            >Posexei is lightweight, focused, and right-sized
                            for social managers, startup founders, and software
                            engineers</strong
                        >
                        who just want to publish content seamlessly with zero
                        friction.
                    </p>
                </div>

                <div
                    class="mx-auto mt-14 grid max-w-5xl grid-cols-1 gap-8 md:grid-cols-2"
                >
                    <!-- The Legacy Bloatware Trap -->
                    <div
                        class="space-y-5 rounded-2xl border border-border/80 bg-card p-7 shadow-xs"
                    >
                        <div
                            class="flex items-center gap-2.5 text-sm font-bold text-rose-500"
                        >
                            <span
                                class="flex size-5 items-center justify-center rounded-full bg-rose-500/10 text-xs font-bold"
                                >✕</span
                            >
                            <span
                                >Legacy Enterprise Suites (Hootsuite, Sprout
                                Social)</span
                            >
                        </div>
                        <ul
                            class="space-y-3.5 text-xs leading-relaxed text-muted-foreground"
                        >
                            <li class="flex items-start gap-2.5">
                                <span class="font-bold text-rose-500">·</span>
                                <span
                                    ><strong>Expensive & Restrictive</strong>:
                                    Forcing you into $150–$300/month tiers just
                                    to connect an extra social account or invite
                                    a teammate.</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="font-bold text-rose-500">·</span>
                                <span
                                    ><strong>Overwhelming Feature Bloat</strong
                                    >: Cluttered with enterprise CRM, sentiment
                                    analysis, and social listening tabs that
                                    slow you down.</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="font-bold text-rose-500">·</span>
                                <span
                                    ><strong>Silent Dispatch Failures</strong>:
                                    Posts silently fail in the background
                                    because an account token expired weeks ago
                                    with zero notification.</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="font-bold text-rose-500">·</span>
                                <span
                                    ><strong>Clunky Multi-Tab Workflow</strong>:
                                    You still end up copying and pasting copy
                                    across 4 tabs to fix hashtags and test image
                                    previews.</span
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- The Posexei Way -->
                    <div
                        class="space-y-5 rounded-2xl border-2 border-primary/50 bg-card p-7 shadow-md ring-2 ring-primary/10"
                    >
                        <div
                            class="flex items-center gap-2.5 text-sm font-bold text-primary"
                        >
                            <CheckCircle2 class="size-5 text-emerald-500" />
                            <span
                                >The Posexei Studio (Built for Founders,
                                Engineers & Social Managers)</span
                            >
                        </div>
                        <ul
                            class="space-y-3.5 text-xs leading-relaxed text-foreground"
                        >
                            <li class="flex items-start gap-2.5">
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                />
                                <span
                                    ><strong>Right-Sized & Affordable</strong>:
                                    Start completely free, upgrade to Creator
                                    Pro for just $23/mo, and buy $5/mo add-ons
                                    only when you need them.</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                />
                                <span
                                    ><strong>Zero Bloat, High Velocity</strong>:
                                    A distraction-free studio with 1-click solo
                                    targeting (`𝕏 Twitter only`, `LinkedIn
                                    only`, `Facebook only`).</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                />
                                <span
                                    ><strong>Guaranteed Post Delivery</strong>:
                                    Automated pre-dispatch token health checks,
                                    safe idempotency keys, and real-time
                                    delivery logs ensure zero ghost drops.</span
                                >
                            </li>
                            <li class="flex items-start gap-2.5">
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                />
                                <span
                                    ><strong
                                        >Authentic Pixel-Perfect
                                        Previews</strong
                                    >: Live mobile and desktop feed cards show
                                    exactly how your post looks before hitting
                                    publish.</span
                                >
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Guaranteed Delivery Reliability Banner -->
                <div
                    class="mx-auto mt-12 max-w-5xl rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6 shadow-xs sm:p-8"
                >
                    <div
                        class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center"
                    >
                        <div class="space-y-2">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                <Shield
                                    class="size-3.5 text-emerald-600 dark:text-emerald-400"
                                />
                                <span>100% Delivery Reliability Guarantee</span>
                            </div>
                            <h4 class="text-xl font-bold text-foreground">
                                Never Wonder If Your Post Actually Published
                            </h4>
                            <p
                                class="max-w-2xl text-xs leading-relaxed text-muted-foreground sm:text-sm"
                            >
                                Unlike legacy schedulers that silently fail when
                                social APIs hiccup, Posexei combines
                                <strong>pre-dispatch token verification</strong
                                >,
                                <strong
                                    >safe cryptographic idempotency keys</strong
                                >
                                (preventing duplicate tweets), and
                                <strong>detailed delivery checkpoints</strong>.
                                If an account needs reconnection, you're alerted
                                before dispatch—not hours after a missed
                                campaign.
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col gap-2">
                            <div
                                class="flex items-center gap-2 text-xs font-semibold text-foreground"
                            >
                                <CheckCircle2 class="size-4 text-emerald-500" />
                                <span>Token Expiration Watchdog</span>
                            </div>
                            <div
                                class="flex items-center gap-2 text-xs font-semibold text-foreground"
                            >
                                <CheckCircle2 class="size-4 text-emerald-500" />
                                <span>Idempotent Safe Dispatches</span>
                            </div>
                            <div
                                class="flex items-center gap-2 text-xs font-semibold text-foreground"
                            >
                                <CheckCircle2 class="size-4 text-emerald-500" />
                                <span>Real-Time Checkpoint Logs</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Core Features Grid -->
        <section
            id="features"
            class="border-t border-border bg-background py-20 md:py-28"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h2
                        class="text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        Engineered for Social Velocity
                    </h2>
                    <h3
                        class="mt-3 text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Everything Your Team Needs to Dominate Social Channels
                    </h3>
                    <p class="mt-4 text-base text-muted-foreground">
                        Stop juggling multiple dashboard tabs and copying text
                        back and forth. Posexei unifies authoring, custom
                        platform variations, visual scheduling, and team
                        management.
                    </p>
                </div>

                <div
                    class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Feature 1: Universal Composer & Solo Targeting -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Send class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Universal Composer & Solo Mode
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Draft a universal post once, then isolate specific
                            networks like <strong>Twitter Only</strong> or
                            <strong>LinkedIn Only</strong> with a single click.
                            Character limit alerts automatically adapt.
                        </p>
                    </div>

                    <!-- Feature 2: Authentic Native Live Feed Previews -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Eye class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Authentic Native Previews
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Experience pixel-perfect feed cards for 𝕏, LinkedIn,
                            and Facebook as you type. Test image aspect ratios,
                            OpenGraph link cards, and line breaks before
                            anything goes live.
                        </p>
                    </div>

                    <!-- Feature 3: Visual Publishing Feed & Calendar -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Calendar class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Interactive Publishing Feed
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Switch between chronological feed inspection and
                            interactive monthly calendars. Track dispatch
                            checkpoints, status badges (Published, Scheduled,
                            Failed), and inspect payload logs.
                        </p>
                    </div>

                    <!-- Feature 4: Proactive Channel Health & Access Alerts -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <AlertTriangle class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Channel Health Monitoring
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Prevent silent dispatch failures. Get proactive
                            alerts when account tokens are expiring, cooling
                            down, or revoked, with instant reconnection
                            feedback.
                        </p>
                    </div>

                    <!-- Feature 5: Multi-Tenant Workspaces for Agencies -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Layers class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Strict Tenant Isolation
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Manage multiple client brands or internal product
                            lines. Every query and dispatch is isolated by
                            organization ID, ensuring zero data bleeding between
                            teams.
                        </p>
                    </div>

                    <!-- Feature 6: Flexible Addon Marketplace -->
                    <div
                        class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs transition hover:border-primary/40"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <CreditCard class="size-5" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Modular Addon Cart & Billing
                        </h4>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Never pay for bloated tiers you don't need. Purchase
                            extra channel slots, team seats, or priority
                            dispatch queues on demand with our slide-out billing
                            cart.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Pricing Section -->
        <section
            id="pricing"
            class="border-t border-border bg-muted/20 py-20 md:py-28"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <h2
                        class="text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        Simple, Predictable Pricing
                    </h2>
                    <h3
                        class="mt-3 text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Plans That Scale With Your Audience
                    </h3>
                    <p class="mt-4 text-base text-muted-foreground">
                        Start for free. Upgrade as your team and client channels
                        grow. Cancel anytime with zero lock-in.
                    </p>

                    <!-- Billing Toggle -->
                    <div
                        class="mt-8 inline-flex items-center gap-2 rounded-full border border-border bg-card p-1 text-xs"
                    >
                        <button
                            type="button"
                            @click="billingCycle = 'monthly'"
                            class="cursor-pointer rounded-full px-3 py-1 font-medium transition"
                            :class="
                                billingCycle === 'monthly'
                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            Monthly Billing
                        </button>
                        <button
                            type="button"
                            @click="billingCycle = 'yearly'"
                            class="flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1 font-medium transition"
                            :class="
                                billingCycle === 'yearly'
                                    ? 'bg-primary font-semibold text-primary-foreground shadow-2xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span>Annual Billing</span>
                            <span
                                class="py-0.2 rounded-full bg-emerald-500/10 px-1.5 font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                Save 20%
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Plan Cards Grid -->
                <div
                    class="mx-auto mt-14 grid max-w-6xl grid-cols-1 items-stretch gap-8 md:grid-cols-3"
                >
                    <!-- Plan 1: Free Starter -->
                    <div
                        class="flex flex-col justify-between rounded-2xl border border-border bg-card p-7 shadow-xs"
                    >
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-base font-bold text-foreground">
                                    Free Starter
                                </h4>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    For solo creators getting started with
                                    social sync.
                                </p>
                            </div>
                            <div class="pt-2">
                                <span
                                    class="text-3xl font-extrabold text-foreground"
                                    >$0</span
                                >
                                <span class="text-xs text-muted-foreground">
                                    / month</span
                                >
                            </div>
                            <ul
                                class="space-y-2.5 pt-4 text-xs text-foreground"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>1 Workspace Organization</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Up to 3 Connected Channels</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>30 Scheduled Posts per month</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Universal Social Composer</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            :href="workspaceHref('/posts')"
                            class="mt-8 block rounded-xl border border-border bg-background py-2.5 text-center text-xs font-semibold text-foreground transition hover:bg-muted"
                        >
                            Get Started Free
                        </Link>
                    </div>

                    <!-- Plan 2: Creator Pro (Featured) -->
                    <div
                        class="relative flex flex-col justify-between rounded-2xl border-2 border-primary bg-card p-7 shadow-md ring-2 ring-primary/10"
                    >
                        <div
                            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-0.5 text-[10px] font-bold tracking-wider text-primary-foreground uppercase"
                        >
                            Most Popular
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-base font-bold text-foreground">
                                    Creator Pro
                                </h4>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    For active content creators and growing
                                    brands.
                                </p>
                            </div>
                            <div class="pt-2">
                                <span
                                    class="text-3xl font-extrabold text-foreground"
                                >
                                    {{
                                        billingCycle === 'yearly'
                                            ? '$23'
                                            : '$29'
                                    }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    / month</span
                                >
                            </div>
                            <ul
                                class="space-y-2.5 pt-4 text-xs text-foreground"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>3 Workspace Organizations</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Up to 10 Connected Channels</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Unlimited Scheduled Posts</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Smart Solo Mode & Live Previews</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Proactive Channel Health Alerts</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            :href="workspaceHref('/settings?tab=billing')"
                            class="mt-8 block rounded-xl bg-primary py-2.5 text-center text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                        >
                            Start 14-Day Free Trial
                        </Link>
                    </div>

                    <!-- Plan 3: Agency Scale -->
                    <div
                        class="flex flex-col justify-between rounded-2xl border border-border bg-card p-7 shadow-xs"
                    >
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-base font-bold text-foreground">
                                    Agency Scale
                                </h4>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    For agencies managing multiple clients and
                                    team seats.
                                </p>
                            </div>
                            <div class="pt-2">
                                <span
                                    class="text-3xl font-extrabold text-foreground"
                                >
                                    {{
                                        billingCycle === 'yearly'
                                            ? '$71'
                                            : '$89'
                                    }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    / month</span
                                >
                            </div>
                            <ul
                                class="space-y-2.5 pt-4 text-xs text-foreground"
                            >
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span
                                        >Unlimited Workspace Organizations</span
                                    >
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>25 Connected Channels included</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Multi-seat Team Collaboration</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Priority Dispatch Queue</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <Check class="size-3.5 text-emerald-500" />
                                    <span>Dedicated Account Support</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            :href="workspaceHref('/settings?tab=billing')"
                            class="mt-8 block rounded-xl border border-border bg-background py-2.5 text-center text-xs font-semibold text-foreground transition hover:bg-muted"
                        >
                            Scale Your Agency
                        </Link>
                    </div>
                </div>

                <!-- Addon Marketplace Callout -->
                <div
                    class="mx-auto mt-12 flex max-w-4xl flex-wrap items-center justify-between gap-4 rounded-xl border border-border/80 bg-card p-4 text-xs sm:p-5"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Plus class="size-4" />
                        </div>
                        <div>
                            <p class="font-bold text-foreground">
                                Need more channels or team seats?
                            </p>
                            <p class="text-muted-foreground">
                                Add extra capacity a la carte from $5/month
                                without changing your plan.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="workspaceHref('/settings?tab=billing')"
                        class="cursor-pointer text-xs font-semibold text-primary hover:underline"
                    >
                        Explore Add-on Cart &rarr;
                    </Link>
                </div>
            </div>
        </section>

        <!-- 7. Frequently Asked Questions -->
        <section
            id="faq"
            class="border-t border-border bg-background py-20 md:py-28"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2
                        class="text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        Got Questions?
                    </h2>
                    <h3
                        class="mt-3 text-3xl font-extrabold tracking-tight text-foreground"
                    >
                        Frequently Asked Questions
                    </h3>
                </div>

                <div class="mt-10 space-y-3">
                    <div
                        v-for="(faq, idx) in [
                            {
                                q: 'Can I customize text specifically for Twitter and LinkedIn in the same post?',
                                a: 'Yes! The Universal Composer allows you to write a base post for all channels, and then click into the X / Twitter, LinkedIn, or Facebook tabs to customize text specifically for that platform while keeping your media attachments unified.',
                            },
                            {
                                q: 'How does Smart Solo Mode work?',
                                a: 'If you want to create a post for Twitter only or LinkedIn only, simply click the platform preset pill (e.g., \'Twitter only\') in the channels dock. The composer immediately isolates that network, adjusts character limits (e.g. giving you 3,000 characters for LinkedIn), and switches your live preview.',
                            },
                            {
                                q: 'What makes Posexei reliable compared to other schedulers?',
                                a: 'Posexei performs pre-dispatch token health verification and uses cryptographic idempotency keys. If an account is cooling down or expiring, you are warned upfront, and network blips will never duplicate or drop your posts.',
                            },
                            {
                                q: 'How does multi-tenancy work for agencies?',
                                a: 'Each client or brand is structured as an isolated Workspace Organization with its own connected social channels, posts, and team members. Data is strictly scoped by organization ID.',
                            },
                            {
                                q: 'Can I purchase add-on channels without upgrading to an expensive tier?',
                                a: 'Absolutely. You can open the Add-on Cart Drawer in your Billing settings to add extra channel slots ($5/mo) or team seats ($10/mo) to your existing subscription at any time.',
                            },
                        ]"
                        :key="idx"
                        class="overflow-hidden rounded-xl border border-border bg-card"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="flex w-full cursor-pointer items-center justify-between p-4.5 text-left text-xs font-semibold text-foreground transition hover:bg-muted/30 sm:text-sm"
                        >
                            <span>{{ faq.q }}</span>
                            <ChevronDown
                                class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
                                :class="
                                    openFaq === idx
                                        ? 'rotate-180 text-primary'
                                        : ''
                                "
                            />
                        </button>
                        <div
                            v-show="openFaq === idx"
                            class="border-t border-border/50 px-4.5 pt-3 pb-4.5 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Bottom High-Impact Call to Action -->
        <section class="border-t border-border bg-muted/20 py-20 text-center">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <h3
                    class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                >
                    Ready to Streamline Your Social Media Publishing?
                </h3>
                <p class="mx-auto max-w-xl text-sm text-muted-foreground">
                    Join forward-thinking creators and marketing agencies using
                    Posexei to publish smarter and faster.
                </p>
                <div class="pt-2">
                    <Link
                        :href="workspaceHref('/posts')"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-primary-foreground shadow-md transition hover:bg-primary/90"
                    >
                        <span>Launch Workspace Studio Now</span>
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- 9. Footer -->
        <footer
            class="border-t border-border bg-card py-12 text-xs text-muted-foreground"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 sm:flex-row sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-primary text-sm font-black text-primary-foreground"
                    >
                        P
                    </div>
                    <span class="font-bold text-foreground">Posexei</span>
                    <span>© 2026 Posexei Inc. All rights reserved.</span>
                </div>
                <div class="flex flex-wrap items-center gap-6">
                    <Link
                        :href="workspaceHref('/posts')"
                        class="transition hover:text-foreground"
                        >Workspace</Link
                    >
                    <Link
                        :href="workspaceHref('/composer')"
                        class="transition hover:text-foreground"
                        >Composer</Link
                    >
                    <Link
                        :href="workspaceHref('/channels')"
                        class="transition hover:text-foreground"
                        >Channels</Link
                    >
                    <Link
                        :href="workspaceHref('/settings?tab=billing')"
                        class="transition hover:text-foreground"
                        >Billing</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
