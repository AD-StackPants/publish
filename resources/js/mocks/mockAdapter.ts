import type {
    AxiosInstance,
    AxiosResponse,
    InternalAxiosRequestConfig,
} from 'axios';
import {
    DEFAULT_ORG,
    INITIAL_ACCOUNTS,
    INITIAL_ORGANIZATIONS,
    INITIAL_POSTS,
    INITIAL_SYSTEM_HEALTH,
} from './data';
import { handleBillingRoute } from './billingMock';
import type {
    Organization,
    SocialAccount,
    Post,
    SystemHealthStatus,
    LinkMetadata,
} from '../types/workspace';

const STORAGE_KEYS = {
    ORGS: 'demo_workspace_orgs',
    ACCOUNTS: 'demo_workspace_accounts',
    POSTS: 'demo_workspace_posts',
    HEALTH: 'demo_workspace_health',
};

function getStorage<T>(key: string, fallback: T): T {
    try {
        const item = localStorage.getItem(key);
        if (!item) return fallback;
        return JSON.parse(item) as T;
    } catch {
        return fallback;
    }
}

function setStorage<T>(key: string, value: T): void {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.warn('LocalStorage save failed', e);
    }
}

// Reset/Seed if not initialized
export function initDemoStorage(): void {
    const existingAccounts = getStorage<SocialAccount[]>(
        STORAGE_KEYS.ACCOUNTS,
        [],
    );
    const existingPosts = getStorage<Post[]>(STORAGE_KEYS.POSTS, []);

    // Merge missing initial accounts across all organizations
    const accountIds = new Set(existingAccounts.map((a) => a.id));
    const mergedAccounts = [...existingAccounts];
    for (const initAcc of INITIAL_ACCOUNTS) {
        if (!accountIds.has(initAcc.id)) {
            mergedAccounts.push(initAcc);
        }
    }
    setStorage(STORAGE_KEYS.ACCOUNTS, mergedAccounts);

    // Merge missing initial posts across all organizations
    const postIds = new Set(existingPosts.map((p) => p.id));
    const mergedPosts = [...existingPosts];
    for (const initPost of INITIAL_POSTS) {
        if (!postIds.has(initPost.id)) {
            mergedPosts.push(initPost);
        }
    }
    setStorage(STORAGE_KEYS.POSTS, mergedPosts);

    if (!localStorage.getItem(STORAGE_KEYS.ORGS)) {
        setStorage(STORAGE_KEYS.ORGS, INITIAL_ORGANIZATIONS);
    }
    if (!localStorage.getItem(STORAGE_KEYS.HEALTH)) {
        setStorage(STORAGE_KEYS.HEALTH, INITIAL_SYSTEM_HEALTH);
    }
}

export function resetDemoStorage(): void {
    setStorage(STORAGE_KEYS.ORGS, INITIAL_ORGANIZATIONS);
    setStorage(STORAGE_KEYS.ACCOUNTS, INITIAL_ACCOUNTS);
    setStorage(STORAGE_KEYS.POSTS, INITIAL_POSTS);
    setStorage(STORAGE_KEYS.HEALTH, INITIAL_SYSTEM_HEALTH);
}

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

export function setupMockAdapter(axiosInstance: AxiosInstance): void {
    initDemoStorage();

    axiosInstance.interceptors.request.use(
        async (config: InternalAxiosRequestConfig) => {
            const rawUrl = config.url || '';
            const baseURL = config.baseURL || '';
            const method = (config.method || 'get').toLowerCase();

            // Normalize path to always resolve the /api/v1 prefix
            let fullPath = rawUrl;
            if (!rawUrl.startsWith('http://') && !rawUrl.startsWith('https://')) {
                const cleanBase = baseURL.replace(/\/+$/, '');
                const cleanUrl = rawUrl.replace(/^\/+/, '');
                if (cleanBase && !cleanUrl.startsWith(cleanBase.replace(/^\/+/, ''))) {
                    fullPath = `${cleanBase}/${cleanUrl}`;
                } else if (!cleanUrl.startsWith('api/v1')) {
                    fullPath = `/api/v1/${cleanUrl}`;
                }
            }

            // Only handle /api/v1 routes in mock adapter
            if (!fullPath.includes('/api/v1')) {
                return config;
            }

            // Simulate 50ms network latency
            await sleep(50);

            const tenantId =
                (config.headers?.['X-Organization-Id'] as string) ||
                DEFAULT_ORG.id;
            const parsedUrl = new URL(fullPath, 'http://localhost');
            const pathname = parsedUrl.pathname;
            
            // Extract search params from URL and config.params
            const searchParams = new URLSearchParams(parsedUrl.search);
            if (config.params) {
                if (config.params instanceof URLSearchParams) {
                    config.params.forEach((val, key) => searchParams.set(key, val));
                } else if (typeof config.params === 'object') {
                    Object.entries(config.params).forEach(([key, val]) => {
                        if (val !== undefined && val !== null) {
                            searchParams.set(key, String(val));
                        }
                    });
                }
            }

            let responseData: unknown = null;
            let statusCode = 200;

            try {
                // Billing subsystem endpoints
                const parsedBody =
                    typeof config.data === 'string'
                        ? (() => {
                              try {
                                  return JSON.parse(config.data);
                              } catch {
                                  return config.data;
                              }
                          })()
                        : config.data;

                const billingResult = handleBillingRoute(
                    pathname,
                    method,
                    parsedBody,
                    tenantId,
                );

                if (billingResult) {
                    responseData = billingResult.data;
                    statusCode = billingResult.status;
                }

                // 1. Organizations: GET /api/v1/user/organizations
                else if (
                    pathname === '/api/v1/user/organizations' &&
                    method === 'get'
                ) {
                    responseData = getStorage<Organization[]>(
                        STORAGE_KEYS.ORGS,
                        INITIAL_ORGANIZATIONS,
                    );
                }

                // 1b. Update Org: PATCH /api/v1/organizations/:id
                else if (
                    pathname.startsWith('/api/v1/organizations/') &&
                    method === 'patch'
                ) {
                    const orgId = pathname.replace(
                        '/api/v1/organizations/',
                        '',
                    );
                    const body =
                        typeof config.data === 'string'
                            ? JSON.parse(config.data)
                            : config.data;
                    const orgs = getStorage<Organization[]>(
                        STORAGE_KEYS.ORGS,
                        INITIAL_ORGANIZATIONS,
                    );
                    const index = orgs.findIndex((o) => o.id === orgId);
                    if (index !== -1) {
                        orgs[index] = {
                            ...orgs[index],
                            ...body,
                            updated_at: new Date().toISOString(),
                        };
                        setStorage(STORAGE_KEYS.ORGS, orgs);
                        responseData = orgs[index];
                    } else {
                        statusCode = 404;
                        responseData = { message: 'Organization not found' };
                    }
                }

                // 2. Accounts: GET /api/v1/accounts
                else if (pathname === '/api/v1/accounts' && method === 'get') {
                    const accounts = getStorage<SocialAccount[]>(
                        STORAGE_KEYS.ACCOUNTS,
                        INITIAL_ACCOUNTS,
                    );
                    responseData = accounts.filter(
                        (acc) => acc.organization_id === tenantId,
                    );
                }

                // 2b. Connect Account: POST /api/v1/accounts
                else if (pathname === '/api/v1/accounts' && method === 'post') {
                    const body =
                        typeof config.data === 'string'
                            ? JSON.parse(config.data)
                            : config.data;
                    const accounts = getStorage<SocialAccount[]>(
                        STORAGE_KEYS.ACCOUNTS,
                        INITIAL_ACCOUNTS,
                    );
                    const newAccount: SocialAccount = {
                        id: `acc-${Date.now().toString(36)}`,
                        organization_id: tenantId,
                        provider: body.provider || 'twitter',
                        account_id: `${body.provider}_${Date.now()}`,
                        name: body.name || 'New Connected Account',
                        handle: body.handle || `@${body.provider}_page`,
                        avatar_url:
                            body.avatar_url ||
                            'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
                        token_expires_at: new Date(
                            Date.now() + 60 * 24 * 60 * 60 * 1000,
                        ).toISOString(),
                        status: 'healthy',
                        created_at: new Date().toISOString(),
                    };
                    accounts.push(newAccount);
                    setStorage(STORAGE_KEYS.ACCOUNTS, accounts);
                    responseData = newAccount;
                }

                // 2c. Disconnect Account: DELETE /api/v1/accounts/:id
                else if (
                    pathname.startsWith('/api/v1/accounts/') &&
                    method === 'delete'
                ) {
                    const accId = pathname.replace('/api/v1/accounts/', '');
                    const accounts = getStorage<SocialAccount[]>(
                        STORAGE_KEYS.ACCOUNTS,
                        INITIAL_ACCOUNTS,
                    );
                    const filtered = accounts.filter((a) => a.id !== accId);
                    setStorage(STORAGE_KEYS.ACCOUNTS, filtered);
                    responseData = {
                        success: true,
                        message: 'Account disconnected',
                    };
                }

                // 2d. Reconnect Account: POST /api/v1/accounts/:id/reconnect
                else if (
                    pathname.match(/^\/api\/v1\/accounts\/[^/]+\/reconnect$/) &&
                    method === 'post'
                ) {
                    const accId = pathname.split('/')[4];
                    const accounts = getStorage<SocialAccount[]>(
                        STORAGE_KEYS.ACCOUNTS,
                        INITIAL_ACCOUNTS,
                    );
                    const acc = accounts.find((a) => a.id === accId);
                    if (acc) {
                        acc.status = 'healthy';
                        acc.token_expires_at = new Date(
                            Date.now() + 90 * 24 * 60 * 60 * 1000,
                        ).toISOString();
                        acc.cooldown_resumes_in = undefined;
                        setStorage(STORAGE_KEYS.ACCOUNTS, accounts);
                        responseData = acc;
                    } else {
                        statusCode = 404;
                        responseData = { message: 'Account not found' };
                    }
                }

                // 3. Posts: GET /api/v1/posts
                else if (pathname === '/api/v1/posts' && method === 'get') {
                    let posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );
                    posts = posts.filter((p) => p.organization_id === tenantId);

                    const statusFilter = searchParams.get('status');
                    const search = searchParams.get('search');
                    const platform = searchParams.get('platform');

                    if (statusFilter && statusFilter !== 'all') {
                        if (statusFilter === 'failed') {
                            posts = posts.filter(
                                (p) =>
                                    p.status === 'partial_failure' ||
                                    p.status === 'dlq',
                            );
                        } else {
                            posts = posts.filter(
                                (p) => p.status === statusFilter,
                            );
                        }
                    }

                    if (platform && platform !== 'all') {
                        const accounts = getStorage<SocialAccount[]>(
                            STORAGE_KEYS.ACCOUNTS,
                            INITIAL_ACCOUNTS,
                        );
                        const matchingAccIds = accounts
                            .filter((a) => a.provider === platform)
                            .map((a) => a.id);
                        posts = posts.filter((p) =>
                            p.target_account_ids?.some((id) =>
                                matchingAccIds.includes(id),
                            ),
                        );
                    }

                    if (search) {
                        const q = search.toLowerCase();
                        posts = posts.filter((p) =>
                            p.content.toLowerCase().includes(q),
                        );
                    }

                    // Sort newest first
                    posts.sort(
                        (a, b) =>
                            new Date(b.created_at || '').getTime() -
                            new Date(a.created_at || '').getTime(),
                    );

                    responseData = posts;
                }

                // 3b. Create Post: POST /api/v1/social/posts
                else if (
                    pathname === '/api/v1/social/posts' &&
                    method === 'post'
                ) {
                    const body =
                        typeof config.data === 'string'
                            ? JSON.parse(config.data)
                            : config.data;
                    const posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );

                    const isScheduled = !!body.scheduled_at;
                    const status =
                        body.status ||
                        (isScheduled ? 'scheduled' : 'published');

                    const newPost: Post = {
                        id: `post-${Date.now().toString(36)}`,
                        organization_id: tenantId,
                        content: body.content || '',
                        platform_overrides: body.platform_overrides || null,
                        media_url: body.media_url || null,
                        link_metadata: body.link_metadata || null,
                        status: status,
                        scheduled_at: body.scheduled_at || null,
                        idempotency_key:
                            body.idempotency_key || `idem_${Date.now()}`,
                        target_account_ids: body.target_account_ids || [],
                        live_urls:
                            status === 'published'
                                ? {
                                      linkedin:
                                          'https://linkedin.com/feed/update/urn:li:share:new_' +
                                          Date.now(),
                                      twitter:
                                          'https://x.com/acmestudio/status/' +
                                          Date.now(),
                                  }
                                : undefined,
                        checkpoints: isScheduled
                            ? [
                                  {
                                      id: `chk-${Date.now()}-1`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Credential Verification',
                                      status: 'completed',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                                  {
                                      id: `chk-${Date.now()}-2`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Media Storage Processing',
                                      status: 'completed',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                                  {
                                      id: `chk-${Date.now()}-3`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Downstream API Dispatch',
                                      status: 'pending',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                              ]
                            : [
                                  {
                                      id: `chk-${Date.now()}-1`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Credential Verification',
                                      status: 'completed',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                                  {
                                      id: `chk-${Date.now()}-2`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Media Storage Processing',
                                      status: 'completed',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                                  {
                                      id: `chk-${Date.now()}-3`,
                                      post_id: `post-${Date.now().toString(36)}`,
                                      step: 'Downstream API Dispatch',
                                      status: 'completed',
                                      error_message: null,
                                      created_at: new Date().toISOString(),
                                  },
                              ],
                        created_at: new Date().toISOString(),
                        updated_at: new Date().toISOString(),
                    };

                    posts.unshift(newPost);
                    setStorage(STORAGE_KEYS.POSTS, posts);
                    responseData = newPost;
                    statusCode = 201;
                }

                // 3c. Delete Post: DELETE /api/v1/posts/:id
                else if (
                    pathname.startsWith('/api/v1/posts/') &&
                    method === 'delete'
                ) {
                    const postId = pathname.replace('/api/v1/posts/', '');
                    const posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );
                    const filtered = posts.filter((p) => p.id !== postId);
                    setStorage(STORAGE_KEYS.POSTS, filtered);
                    responseData = { success: true, message: 'Post removed' };
                }

                // 3d. Update/Reschedule Post: PATCH /api/v1/posts/:id
                else if (
                    pathname.startsWith('/api/v1/posts/') &&
                    method === 'patch'
                ) {
                    const postId = pathname.replace('/api/v1/posts/', '');
                    const body =
                        typeof config.data === 'string'
                            ? JSON.parse(config.data)
                            : config.data;
                    const posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );
                    const post = posts.find((p) => p.id === postId);
                    if (post) {
                        Object.assign(post, body, {
                            updated_at: new Date().toISOString(),
                        });
                        setStorage(STORAGE_KEYS.POSTS, posts);
                        responseData = post;
                    } else {
                        statusCode = 404;
                        responseData = { message: 'Post not found' };
                    }
                }

                // 4. Replay from DLQ: POST /api/v1/dlq/:id/replay
                else if (
                    pathname.match(/^\/api\/v1\/dlq\/[^/]+\/replay$/) &&
                    method === 'post'
                ) {
                    const postId = pathname.split('/')[4];
                    const posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );
                    const post = posts.find((p) => p.id === postId);
                    if (post) {
                        post.status = 'published';
                        post.live_urls = {
                            facebook:
                                'https://facebook.com/acmeofficial/posts/' +
                                Date.now(),
                        };
                        if (post.checkpoints) {
                            post.checkpoints.forEach((chk) => {
                                chk.status = 'completed';
                                chk.error_message = null;
                            });
                        }
                        post.updated_at = new Date().toISOString();
                        setStorage(STORAGE_KEYS.POSTS, posts);
                        responseData = post;
                    } else {
                        statusCode = 404;
                        responseData = { message: 'Post not found in DLQ' };
                    }
                }

                // 4b. Retry failed channel: POST /api/v1/posts/:id/retry-failed
                else if (
                    pathname.match(/^\/api\/v1\/posts\/[^/]+\/retry-failed$/) &&
                    method === 'post'
                ) {
                    const postId = pathname.split('/')[4];
                    const posts = getStorage<Post[]>(
                        STORAGE_KEYS.POSTS,
                        INITIAL_POSTS,
                    );
                    const post = posts.find((p) => p.id === postId);
                    if (post) {
                        post.status = 'published';
                        post.live_urls = {
                            ...post.live_urls,
                            facebook:
                                'https://facebook.com/acmeofficial/posts/re_' +
                                Date.now(),
                        };
                        if (post.checkpoints) {
                            post.checkpoints.forEach((chk) => {
                                chk.status = 'completed';
                                chk.error_message = null;
                            });
                        }
                        post.updated_at = new Date().toISOString();
                        setStorage(STORAGE_KEYS.POSTS, posts);
                        responseData = post;
                    } else {
                        statusCode = 404;
                        responseData = { message: 'Post not found' };
                    }
                }

                // 5. Open Graph Preview: GET /api/v1/tools/preview-link
                else if (
                    pathname === '/api/v1/tools/preview-link' &&
                    method === 'get'
                ) {
                    const targetUrl =
                        searchParams.get('url') || 'https://example.com';
                    let domain = 'example.com';
                    try {
                        domain = new URL(targetUrl).hostname;
                    } catch {
                        domain = targetUrl
                            .replace(/https?:\/\//, '')
                            .split('/')[0];
                    }

                    const mockOg: LinkMetadata = {
                        url: targetUrl,
                        title: `Exploring Next-Gen Architecture at ${domain}`,
                        description: `An in-depth analysis of decoupled social distribution, high-resiliency job checkpoints, and multi-tenant isolation.`,
                        image_url:
                            'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80',
                        domain: domain,
                    };
                    responseData = mockOg;
                }

                // 6. System Engine Health: GET /api/v1/system/health
                else if (
                    pathname === '/api/v1/system/health' &&
                    method === 'get'
                ) {
                    const health = getStorage<SystemHealthStatus>(
                        STORAGE_KEYS.HEALTH,
                        INITIAL_SYSTEM_HEALTH,
                    );
                    health.last_heartbeat = new Date().toISOString();
                    responseData = health;
                } else {
                    statusCode = 404;
                    responseData = {
                        message: `Not found: ${method.toUpperCase()} ${pathname}`,
                    };
                }
            } catch (err: any) {
                statusCode = 500;
                responseData = {
                    message: err?.message || 'Mock Adapter internal error',
                };
            }

            // Return a mock adapter response resolving with AxiosResponse shape
            config.adapter = async (): Promise<AxiosResponse> => {
                if (statusCode >= 400) {
                    const error: any = new Error(
                        `Request failed with status code ${statusCode}`,
                    );
                    error.response = {
                        data: responseData,
                        status: statusCode,
                        statusText:
                            statusCode === 404 ? 'Not Found' : 'Server Error',
                        headers: {},
                        config,
                    };
                    error.config = config;
                    throw error;
                }

                return {
                    data: responseData,
                    status: statusCode,
                    statusText: 'OK',
                    headers: { 'content-type': 'application/json' },
                    config,
                };
            };

            return config;
        },
    );
}
