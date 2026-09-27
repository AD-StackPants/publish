import type {
    Organization,
    SocialAccount,
    Post,
    SystemHealthStatus,
} from '../types/workspace';

export const DEFAULT_ORG: Organization = {
    id: '01923a4b-7c8d-7e9f-a012-3456789abcde',
    name: 'Acme Studio',
    slug: 'acme-studio',
    timezone: 'America/New_York',
    subscription: {
        plan: 'Growth Pro',
        status: 'active',
        seats: 5,
        billing_period: 'monthly',
        renews_at: '2026-10-15',
    },
    created_at: '2026-01-15T09:00:00Z',
    updated_at: '2026-03-20T14:30:00Z',
};

export const INITIAL_ORGANIZATIONS: Organization[] = [
    DEFAULT_ORG,
    {
        id: '01923b5c-8d9e-7f0a-b123-456789abcdef',
        name: 'Nexus Labs',
        slug: 'nexus-labs',
        timezone: 'Europe/London',
        subscription: {
            plan: 'Enterprise',
            status: 'active',
            seats: 25,
            billing_period: 'yearly',
            renews_at: '2027-02-01',
        },
        created_at: '2026-02-01T11:00:00Z',
        updated_at: '2026-02-18T16:45:00Z',
    },
    {
        id: '01923c6d-9e0f-7a1b-c234-56789abcdef0',
        name: 'Starlight Media',
        slug: 'starlight-media',
        timezone: 'America/Los_Angeles',
        subscription: {
            plan: 'Scale',
            status: 'active',
            seats: 12,
            billing_period: 'monthly',
            renews_at: '2026-11-10',
        },
        created_at: '2026-02-10T08:15:00Z',
        updated_at: '2026-03-01T10:00:00Z',
    },
];

export const INITIAL_ACCOUNTS: SocialAccount[] = [
    {
        id: 'acc-li-001',
        organization_id: DEFAULT_ORG.id,
        provider: 'linkedin',
        account_id: 'li_org_892341',
        name: 'Acme Corp',
        handle: 'acme-corp',
        avatar_url:
            'https://images.unsplash.com/photo-1572021335469-31706a17aaef?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-12-31T23:59:59Z',
        status: 'healthy',
        created_at: '2026-01-16T10:00:00Z',
    },
    {
        id: 'acc-tw-002',
        organization_id: DEFAULT_ORG.id,
        provider: 'twitter',
        account_id: 'tw_usr_119028',
        name: 'Acme Studio',
        handle: '@acmestudio',
        avatar_url:
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-08-15T12:00:00Z',
        status: 'cooling',
        cooldown_resumes_in: '20m',
        created_at: '2026-01-16T10:05:00Z',
    },
    {
        id: 'acc-fb-003',
        organization_id: DEFAULT_ORG.id,
        provider: 'facebook',
        account_id: 'fb_page_440912',
        name: 'Acme Official',
        handle: 'acmeofficialpage',
        avatar_url:
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
        token_expires_at: new Date(
            Date.now() + 2 * 24 * 60 * 60 * 1000,
        ).toISOString(), // 2 days from now
        status: 'expiring',
        created_at: '2026-01-16T10:10:00Z',
    },
    {
        id: 'acc-tw-004',
        organization_id: DEFAULT_ORG.id,
        provider: 'twitter',
        account_id: 'tw_usr_998124',
        name: 'Acme News & PR',
        handle: '@acmenews_pr',
        avatar_url:
            'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-01-01T00:00:00Z',
        status: 'revoked',
        created_at: '2026-01-18T10:10:00Z',
    },
    // Nexus Labs Accounts
    {
        id: 'acc-nexus-li',
        organization_id: '01923b5c-8d9e-7f0a-b123-456789abcdef',
        provider: 'linkedin',
        account_id: 'li_nexus_7721',
        name: 'Nexus Labs Official',
        handle: 'nexus-labs',
        avatar_url:
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-11-30T23:59:59Z',
        status: 'healthy',
        created_at: '2026-02-01T12:00:00Z',
    },
    {
        id: 'acc-nexus-tw',
        organization_id: '01923b5c-8d9e-7f0a-b123-456789abcdef',
        provider: 'twitter',
        account_id: 'tw_nexus_9912',
        name: 'Nexus Labs AI',
        handle: '@nexuslabs_ai',
        avatar_url:
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-10-15T12:00:00Z',
        status: 'healthy',
        created_at: '2026-02-01T12:05:00Z',
    },
    // Starlight Media Accounts
    {
        id: 'acc-starlight-fb',
        organization_id: '01923c6d-9e0f-7a1b-c234-56789abcdef0',
        provider: 'facebook',
        account_id: 'fb_star_3391',
        name: 'Starlight Media Network',
        handle: 'starlightmedia',
        avatar_url:
            'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-12-15T12:00:00Z',
        status: 'healthy',
        created_at: '2026-02-10T09:00:00Z',
    },
    {
        id: 'acc-starlight-tw',
        organization_id: '01923c6d-9e0f-7a1b-c234-56789abcdef0',
        provider: 'twitter',
        account_id: 'tw_star_4412',
        name: 'Starlight Media',
        handle: '@starlight_media',
        avatar_url:
            'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&auto=format&fit=crop&q=80',
        token_expires_at: '2026-09-30T12:00:00Z',
        status: 'healthy',
        created_at: '2026-02-10T09:05:00Z',
    },
];

export const INITIAL_POSTS: Post[] = [
    // 1. Published post with direct verification links
    {
        id: 'post-pub-001',
        organization_id: DEFAULT_ORG.id,
        content:
            'We are thrilled to unveil our v2.0 platform! Built from the ground up for high-throughput multi-platform social workflows with cryptographic idempotency and automated failover.',
        platform_overrides: {
            twitter: {
                content:
                    'Excited to launch our v2.0 platform! High-throughput multi-channel social publishing with zero message loss. Check it out: https://example.com',
            },
            linkedin: {
                content:
                    'We are thrilled to unveil our v2.0 platform! Built from the ground up for high-throughput multi-platform social workflows with cryptographic idempotency, rate-limit cooling, and automated failover.\n\nRead our architectural deep-dive below.',
            },
        },
        media_url:
            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80',
        link_metadata: {
            url: 'https://example.com/blog/announcing-v2',
            title: 'Announcing 2.0: Resilient Multi-Tenant Publishing',
            description:
                'Learn how our dual-pipeline architecture eliminates rate-limit collisions and provides guaranteed social delivery.',
            image_url:
                'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80',
            domain: 'example.com',
        },
        status: 'published',
        scheduled_at: null,
        idempotency_key: 'idem_pub_4f8910e82c1b',
        target_account_ids: ['acc-li-001', 'acc-tw-002'],
        live_urls: {
            linkedin:
                'https://linkedin.com/feed/update/urn:li:share:719823401928374',
            twitter: 'https://x.com/acmestudio/status/17928301928391823',
        },
        checkpoints: [
            {
                id: 'chk-pub-1',
                post_id: 'post-pub-001',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-24T14:00:01Z',
            },
            {
                id: 'chk-pub-2',
                post_id: 'post-pub-001',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-24T14:00:02Z',
            },
            {
                id: 'chk-pub-3',
                post_id: 'post-pub-001',
                step: 'Downstream API Dispatch',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-24T14:00:04Z',
            },
        ],
        created_at: '2026-03-24T13:58:00Z',
        updated_at: '2026-03-24T14:00:04Z',
    },

    // 2. Scheduled post #1 (Tomorrow 10:00 AM)
    {
        id: 'post-sch-002',
        organization_id: DEFAULT_ORG.id,
        content:
            '3 design systems lessons we learned building enterprise dashboard components in Vue 3 and Tailwind v4. Thread incoming tomorrow morning! 💡',
        platform_overrides: {
            twitter: {
                content:
                    '3 design systems lessons from building with Vue 3 & Tailwind v4 🧵👇',
            },
        },
        media_url:
            'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=1200&auto=format&fit=crop&q=80',
        link_metadata: null,
        status: 'scheduled',
        scheduled_at: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
        idempotency_key: 'idem_sch_882019ab71e4',
        target_account_ids: ['acc-tw-002', 'acc-li-001'],
        checkpoints: [
            {
                id: 'chk-sch1-1',
                post_id: 'post-sch-002',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T08:00:00Z',
            },
            {
                id: 'chk-sch1-2',
                post_id: 'post-sch-002',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T08:00:02Z',
            },
            {
                id: 'chk-sch1-3',
                post_id: 'post-sch-002',
                step: 'Downstream API Dispatch',
                status: 'pending',
                error_message: null,
                created_at: '2026-03-25T08:00:02Z',
            },
        ],
        created_at: '2026-03-25T08:00:00Z',
        updated_at: '2026-03-25T08:00:02Z',
    },

    // 3. Scheduled post #2 (3 days out)
    {
        id: 'post-sch-003',
        organization_id: DEFAULT_ORG.id,
        content:
            'How our engineering team streamlined social scheduling across 20+ partner brands using scoped tenant isolation and automated DLQ recovery.',
        platform_overrides: null,
        media_url: null,
        link_metadata: {
            url: 'https://example.com/case-studies/multi-tenant-scaling',
            title: 'Scaling Multi-Tenant Publishing: A Technical Breakdown',
            description:
                'How tenant isolation protects brand reputation during downstream API outages.',
            domain: 'example.com',
        },
        status: 'scheduled',
        scheduled_at: new Date(
            Date.now() + 3 * 24 * 60 * 60 * 1000,
        ).toISOString(),
        idempotency_key: 'idem_sch_991240cc83f1',
        target_account_ids: ['acc-li-001', 'acc-fb-003'],
        checkpoints: [
            {
                id: 'chk-sch2-1',
                post_id: 'post-sch-003',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T11:00:00Z',
            },
            {
                id: 'chk-sch2-2',
                post_id: 'post-sch-003',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T11:00:01Z',
            },
            {
                id: 'chk-sch2-3',
                post_id: 'post-sch-003',
                step: 'Downstream API Dispatch',
                status: 'pending',
                error_message: null,
                created_at: '2026-03-25T11:00:01Z',
            },
        ],
        created_at: '2026-03-25T11:00:00Z',
        updated_at: '2026-03-25T11:00:01Z',
    },

    // 4. Draft post with uncommitted edits
    {
        id: 'post-drf-004',
        organization_id: DEFAULT_ORG.id,
        content:
            'Drafting our Q2 community roadmap. Highlights include webhook subscriptions, AI-assisted alt-text generation, and expanded Mastodon & Bluesky support. Thoughts?',
        platform_overrides: {
            twitter: {
                content:
                    'Quick peek at our Q2 roadmap: webhooks, alt-text generation, Bluesky support. What else do you need?',
            },
        },
        media_url:
            'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80',
        link_metadata: null,
        status: 'draft',
        scheduled_at: null,
        idempotency_key: 'idem_drf_338901fe77b2',
        target_account_ids: ['acc-li-001', 'acc-tw-002', 'acc-fb-003'],
        checkpoints: [],
        created_at: '2026-03-26T09:12:00Z',
        updated_at: '2026-03-26T09:45:00Z',
    },

    // 5. Partial failure post (Step 1 & 2 green, Step 3 failed on Meta Graph API 429)
    {
        id: 'post-par-005',
        organization_id: DEFAULT_ORG.id,
        content:
            'Exclusive behind-the-scenes footage from our quarterly product hackathon! Over 48 hours, our engineers shipped 6 new community integrations.',
        platform_overrides: {
            facebook: {
                content:
                    'Behind the scenes at our hackathon! Watch the demo showcase.',
            },
        },
        media_url:
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80',
        link_metadata: null,
        status: 'partial_failure',
        scheduled_at: null,
        idempotency_key: 'idem_par_554891ff33d1',
        target_account_ids: ['acc-li-001', 'acc-fb-003'],
        live_urls: {
            linkedin:
                'https://linkedin.com/feed/update/urn:li:share:719829910293847',
        },
        checkpoints: [
            {
                id: 'chk-par-1',
                post_id: 'post-par-005',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-26T12:00:00Z',
            },
            {
                id: 'chk-par-2',
                post_id: 'post-par-005',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-26T12:00:02Z',
            },
            {
                id: 'chk-par-3',
                post_id: 'post-par-005',
                step: 'Downstream API Dispatch',
                status: 'failed',
                error_message:
                    'Meta Graph API returned HTTP 429 (RateLimitExceeded): "Application request limit reached for call to me/feed. Retry after 180s."',
                created_at: '2026-03-26T12:00:05Z',
            },
        ],
        created_at: '2026-03-26T11:59:00Z',
        updated_at: '2026-03-26T12:00:05Z',
    },

    // 6. DLQ post (Dead Letter Queue trapped, message ID: msg_dlq_99182, permanent failure, replayable)
    {
        id: 'post-dlq-006',
        organization_id: DEFAULT_ORG.id,
        content:
            'System notification: Scheduled maintenance completed with zero downtime. All cluster instances are running optimal build hash #f8e12a.',
        platform_overrides: null,
        media_url: null,
        link_metadata: null,
        status: 'dlq',
        scheduled_at: null,
        idempotency_key: 'idem_dlq_778102aa99c4',
        target_account_ids: ['acc-fb-003'],
        dlq_message_id: 'msg_dlq_99182',
        checkpoints: [
            {
                id: 'chk-dlq-1',
                post_id: 'post-dlq-006',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-26T14:10:00Z',
            },
            {
                id: 'chk-dlq-2',
                post_id: 'post-dlq-006',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-26T14:10:01Z',
            },
            {
                id: 'chk-dlq-3',
                post_id: 'post-dlq-006',
                step: 'Downstream API Dispatch',
                status: 'failed',
                error_message:
                    'Meta OAuth Token Revoked (Error 190, Subcode 460): Session has expired or user changed password. Trapped in DLQ for manual remediation.',
                created_at: '2026-03-26T14:10:04Z',
            },
        ],
        created_at: '2026-03-26T14:09:00Z',
        updated_at: '2026-03-26T14:10:04Z',
    },
    // Nexus Labs Posts
    {
        id: 'post-nexus-001',
        organization_id: '01923b5c-8d9e-7f0a-b123-456789abcdef',
        content:
            'Nexus Labs research paper on low-latency streaming social syndication is out today! Check out our multi-tenant benchmark results.',
        platform_overrides: null,
        media_url:
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200&auto=format&fit=crop&q=80',
        link_metadata: null,
        status: 'published',
        scheduled_at: null,
        idempotency_key: 'idem_nexus_11204',
        target_account_ids: ['acc-nexus-li', 'acc-nexus-tw'],
        live_urls: {
            linkedin: 'https://linkedin.com/feed/update/urn:li:share:nexus_01',
            twitter: 'https://x.com/nexuslabs_ai/status/88912301923',
        },
        checkpoints: [
            {
                id: 'chk-nex-1',
                post_id: 'post-nexus-001',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-20T10:00:00Z',
            },
            {
                id: 'chk-nex-2',
                post_id: 'post-nexus-001',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-20T10:00:01Z',
            },
            {
                id: 'chk-nex-3',
                post_id: 'post-nexus-001',
                step: 'Downstream API Dispatch',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-20T10:00:03Z',
            },
        ],
        created_at: '2026-03-20T09:59:00Z',
        updated_at: '2026-03-20T10:00:03Z',
    },
    {
        id: 'post-nexus-002',
        organization_id: '01923b5c-8d9e-7f0a-b123-456789abcdef',
        content:
            'Upcoming Nexus Labs engineering webinar: Building resilient event consumers in distributed architectures. Reserve your spot!',
        platform_overrides: null,
        media_url: null,
        link_metadata: {
            url: 'https://nexuslabs.io/events/webinar-brokers',
            title: 'Resilient Consumer Pipelines',
            description:
                'Architecting zero-downtime message dispatch with Kafka and Redis.',
            domain: 'nexuslabs.io',
        },
        status: 'scheduled',
        scheduled_at: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(),
        idempotency_key: 'idem_nexus_2291',
        target_account_ids: ['acc-nexus-li'],
        checkpoints: [
            {
                id: 'chk-nex2-1',
                post_id: 'post-nexus-002',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T14:00:00Z',
            },
            {
                id: 'chk-nex2-2',
                post_id: 'post-nexus-002',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-25T14:00:01Z',
            },
            {
                id: 'chk-nex2-3',
                post_id: 'post-nexus-002',
                step: 'Downstream API Dispatch',
                status: 'pending',
                error_message: null,
                created_at: '2026-03-25T14:00:01Z',
            },
        ],
        created_at: '2026-03-25T14:00:00Z',
        updated_at: '2026-03-25T14:00:01Z',
    },
    // Starlight Media Posts
    {
        id: 'post-star-001',
        organization_id: '01923c6d-9e0f-7a1b-c234-56789abcdef0',
        content:
            'Behind the scenes at Starlight Studios: Creating brand visual identities that cut through the noise and capture attention.',
        platform_overrides: null,
        media_url:
            'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&auto=format&fit=crop&q=80',
        link_metadata: null,
        status: 'published',
        scheduled_at: null,
        idempotency_key: 'idem_star_9918',
        target_account_ids: ['acc-starlight-fb', 'acc-starlight-tw'],
        live_urls: {
            facebook: 'https://facebook.com/starlightmedia/posts/88219',
            twitter: 'https://x.com/starlight_media/status/99128301',
        },
        checkpoints: [
            {
                id: 'chk-star-1',
                post_id: 'post-star-001',
                step: 'Credential Verification',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-22T11:00:00Z',
            },
            {
                id: 'chk-star-2',
                post_id: 'post-star-001',
                step: 'Media Storage Processing',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-22T11:00:01Z',
            },
            {
                id: 'chk-star-3',
                post_id: 'post-star-001',
                step: 'Downstream API Dispatch',
                status: 'completed',
                error_message: null,
                created_at: '2026-03-22T11:00:03Z',
            },
        ],
        created_at: '2026-03-22T10:58:00Z',
        updated_at: '2026-03-22T11:00:03Z',
    },
];

export const INITIAL_SYSTEM_HEALTH: SystemHealthStatus = {
    redis_stream: 'healthy',
    worker_status: 'active',
    queue_depth: 3,
    last_heartbeat: new Date().toISOString(),
    active_workers: 4,
};
