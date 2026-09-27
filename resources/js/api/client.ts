import axios from 'axios';
import { setupMockAdapter } from '../mocks/mockAdapter';
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

// Always mount adapter with populated API data
setupMockAdapter(apiClient);

export default apiClient;
