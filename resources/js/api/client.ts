import axios from 'axios';
import { DEFAULT_ORG } from '../mocks/data';
import { setupMockAdapter } from '../mocks/mockAdapter';

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

// Mount the mock adapter only in demo mode (VITE_APP_ENV=demo).
// In local / staging / production the real Laravel API is called directly.
// Vite statically replaces import.meta.env.VITE_APP_ENV at build time,
// so dead-code elimination removes the mock adapter from non-demo bundles.
if (import.meta.env.VITE_APP_ENV === 'demo') {
    setupMockAdapter(apiClient);
}

export default apiClient;
