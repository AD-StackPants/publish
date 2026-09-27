export type TenantSubscription = {
    plan: string;
    status: 'active' | 'trialing' | 'past_due';
    seats: number;
    billing_period: 'monthly' | 'yearly';
    renews_at?: string;
};

export type Organization = {
    id: string;
    name: string;
    slug: string;
    timezone: string;
    subscription?: TenantSubscription;
    created_at?: string;
    updated_at?: string;
};

export type SocialAccountStatus =
    | 'healthy'
    | 'expiring'
    | 'revoked'
    | 'cooling';

export type SocialProvider = 'linkedin' | 'facebook' | 'twitter';

export type SocialAccount = {
    id: string;
    organization_id: string;
    provider: SocialProvider;
    account_id: string;
    name: string;
    handle?: string;
    avatar_url: string | null;
    token_expires_at: string | null;
    status: SocialAccountStatus;
    cooldown_resumes_in?: string; // e.g. "20m"
    created_at?: string;
    updated_at?: string;
};

export type PostStatus =
    | 'draft'
    | 'scheduled'
    | 'publishing'
    | 'published'
    | 'partial_failure'
    | 'dlq';

export type CheckpointStatus =
    | 'pending'
    | 'in_progress'
    | 'completed'
    | 'failed';

export type PostCheckpoint = {
    id: string;
    post_id: string;
    step: string;
    status: CheckpointStatus;
    error_message: string | null;
    created_at?: string;
    updated_at?: string;
};

export type LinkMetadata = {
    url: string;
    title: string;
    description: string;
    image_url?: string;
    domain?: string;
};

export type PlatformOverrides = {
    twitter?: {
        content?: string;
    };
    linkedin?: {
        content?: string;
    };
    facebook?: {
        content?: string;
    };
};

export type Post = {
    id: string;
    organization_id: string;
    content: string;
    platform_overrides?: PlatformOverrides | null;
    media_url?: string | null;
    link_metadata?: LinkMetadata | null;
    status: PostStatus;
    scheduled_at?: string | null;
    idempotency_key: string;
    target_account_ids?: string[];
    delivery_results?: Record<string, any>;
    live_urls?: Record<string, string>;
    dlq_message_id?: string;
    checkpoints?: PostCheckpoint[];
    created_at?: string;
    updated_at?: string;
};

export type SystemHealthStatus = {
    redis_stream: 'healthy' | 'degraded' | 'offline';
    worker_status: 'active' | 'idle' | 'congested';
    queue_depth: number;
    last_heartbeat: string;
    active_workers: number;
};
