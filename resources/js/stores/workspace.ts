import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Organization, SocialAccount } from '../types/workspace';
import {
    DEFAULT_ORG,
    INITIAL_ORGANIZATIONS,
    INITIAL_ACCOUNTS,
} from '../mocks/data';
import { apiClient } from '../api/client';

export const useWorkspaceStore = defineStore('workspace', () => {
    const organizations = ref<Organization[]>(INITIAL_ORGANIZATIONS);
    const currentOrg = ref<Organization | null>(DEFAULT_ORG);
    const accounts = ref<SocialAccount[]>(INITIAL_ACCOUNTS);
    const isLoading = ref<boolean>(false);
    const isAccountsLoading = ref<boolean>(false);

    // Initialize from storage or default
    const initStore = () => {
        try {
            const cachedOrg = localStorage.getItem('workspace_current_org');
            if (cachedOrg) {
                currentOrg.value = JSON.parse(cachedOrg);
            }
        } catch {
            // ignore
        }

        if (!currentOrg.value) {
            currentOrg.value = DEFAULT_ORG;
            localStorage.setItem(
                'workspace_current_org',
                JSON.stringify(DEFAULT_ORG),
            );
        }
    };

    initStore();

    const activeOrgId = computed<string>(() => {
        return currentOrg.value?.id || DEFAULT_ORG.id;
    });

    const activeOrgSlug = computed<string>(() => {
        return currentOrg.value?.slug || DEFAULT_ORG.slug;
    });

    const expiringAccounts = computed<SocialAccount[]>(() => {
        return accounts.value.filter(
            (a) => a.status === 'expiring' || a.status === 'revoked',
        );
    });

    const hasCriticalAccountWarning = computed<boolean>(() => {
        return expiringAccounts.value.length > 0;
    });

    const fetchOrganizations = async () => {
        isLoading.value = true;
        try {
            const res = await apiClient.get<Organization[]>(
                '/user/organizations',
            );
            organizations.value = res.data;

            // Sync currentOrg if not found or matched by slug
            if (currentOrg.value) {
                const matched = organizations.value.find(
                    (o) =>
                        o.id === currentOrg.value?.id ||
                        o.slug === currentOrg.value?.slug,
                );
                if (matched) {
                    currentOrg.value = matched;
                    localStorage.setItem(
                        'workspace_current_org',
                        JSON.stringify(matched),
                    );
                }
            }
        } catch (err) {
            console.error('Failed to fetch organizations', err);
            if (!currentOrg.value) {
                currentOrg.value = DEFAULT_ORG;
            }
        } finally {
            isLoading.value = false;
        }
    };

    const setOrganizationBySlug = async (slug: string) => {
        if (organizations.value.length === 0) {
            await fetchOrganizations();
        }

        const match = organizations.value.find((o) => o.slug === slug);
        if (match) {
            currentOrg.value = match;
            localStorage.setItem(
                'workspace_current_org',
                JSON.stringify(match),
            );
            await fetchAccounts();
            return true;
        }

        if (slug === DEFAULT_ORG.slug) {
            currentOrg.value = DEFAULT_ORG;
            localStorage.setItem(
                'workspace_current_org',
                JSON.stringify(DEFAULT_ORG),
            );
            await fetchAccounts();
            return true;
        }

        return false;
    };

    const switchOrganization = (org: Organization) => {
        currentOrg.value = org;
        localStorage.setItem('workspace_current_org', JSON.stringify(org));
        void fetchAccounts();
    };

    const fetchAccounts = async () => {
        isAccountsLoading.value = true;
        try {
            const res = await apiClient.get<SocialAccount[]>('/accounts');
            accounts.value = res.data;
        } catch (err) {
            console.error('Failed to fetch accounts', err);
        } finally {
            isAccountsLoading.value = false;
        }
    };

    const updateOrgSettings = async (payload: Partial<Organization>) => {
        if (!currentOrg.value) return;
        const res = await apiClient.patch<Organization>(
            `/organizations/${currentOrg.value.id}`,
            payload,
        );
        currentOrg.value = res.data;
        localStorage.setItem('workspace_current_org', JSON.stringify(res.data));
        const index = organizations.value.findIndex(
            (o) => o.id === res.data.id,
        );
        if (index !== -1) {
            organizations.value[index] = res.data;
        }
        return res.data;
    };

    return {
        organizations,
        currentOrg,
        accounts,
        isLoading,
        isAccountsLoading,
        activeOrgId,
        activeOrgSlug,
        expiringAccounts,
        hasCriticalAccountWarning,
        fetchOrganizations,
        setOrganizationBySlug,
        switchOrganization,
        fetchAccounts,
        updateOrgSettings,
    };
});
