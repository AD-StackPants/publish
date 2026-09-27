import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Organization, SocialAccount } from '../types/workspace';
import { apiClient } from '../api/client';

export const useWorkspaceStore = defineStore('workspace', () => {
    const organizations = ref<Organization[]>([]);
    const currentOrg = ref<Organization | null>(null);
    const accounts = ref<SocialAccount[]>([]);
    const isLoading = ref<boolean>(false);
    const isAccountsLoading = ref<boolean>(false);

    // Rehydrate currentOrg from localStorage on store init (browser only)
    const initStore = () => {
        if (typeof localStorage === 'undefined') return;

        try {
            const cachedOrg = localStorage.getItem('workspace_current_org');
            if (cachedOrg) {
                currentOrg.value = JSON.parse(cachedOrg) as Organization;
            }
        } catch {
            // ignore malformed cache
        }
    };

    initStore();

    const activeOrgId = computed<string>(() => currentOrg.value?.id ?? '');

    const activeOrgSlug = computed<string>(() => currentOrg.value?.slug ?? '');

    const expiringAccounts = computed<SocialAccount[]>(() =>
        accounts.value.filter(
            (a) => a.status === 'expiring' || a.status === 'revoked',
        ),
    );

    const hasCriticalAccountWarning = computed<boolean>(
        () => expiringAccounts.value.length > 0,
    );

    const fetchOrganizations = async () => {
        isLoading.value = true;
        try {
            const res = await apiClient.get<Organization[]>('/user/organizations');
            organizations.value = res.data;

            // Sync currentOrg to the freshly fetched version if we have one cached
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
            } else if (organizations.value.length > 0) {
                // Auto-select the first org when there is no cached selection
                currentOrg.value = organizations.value[0] ?? null;
                if (currentOrg.value) {
                    localStorage.setItem(
                        'workspace_current_org',
                        JSON.stringify(currentOrg.value),
                    );
                }
            }
        } catch (err) {
            console.error('Failed to fetch organizations', err);
        } finally {
            isLoading.value = false;
        }
    };

    const setOrganizationBySlug = async (slug: string): Promise<boolean> => {
        if (organizations.value.length === 0) {
            await fetchOrganizations();
        }

        const match = organizations.value.find((o) => o.slug === slug);
        if (match) {
            currentOrg.value = match;
            localStorage.setItem('workspace_current_org', JSON.stringify(match));
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
            accounts.value = Array.isArray(res.data) ? res.data : [];
        } catch (err) {
            console.error('Failed to fetch accounts', err);
            accounts.value = [];
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
        const index = organizations.value.findIndex((o) => o.id === res.data.id);
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
