import axios from 'axios';
import { DEFAULT_ORG } from '../mocks/data';

export const apiClient = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

// Request interceptor injecting tenant header X-Organization-Id
apiClient.interceptors.request.use((config) => {
    let orgId: string | null = null;

    try {
        const storedOrg = localStorage.getItem('workspace_current_org');
        if (storedOrg) {
            const parsed = JSON.parse(storedOrg);
            orgId = parsed?.id;
        }
    } catch {
        // Ignore JSON parse errors
    }

    if (!orgId) {
        orgId = DEFAULT_ORG.id;
    }

    config.headers['X-Organization-Id'] = orgId;
    return config;
});

// Mount the mock adapter only in demo mode AND in a browser context.
// The typeof guard prevents the SSR (Node.js) evaluator from ever touching
// localStorage or axios-mock-adapter, which would throw ReferenceError.
if (
    import.meta.env.VITE_APP_ENV === 'demo' &&
    typeof window !== 'undefined'
) {
    const { setupMockAdapter } = await import('../mocks/mockAdapter');
    setupMockAdapter(apiClient);
}

export default apiClient;
