import { createPinia } from 'pinia';
import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const pinia = createPinia();
const appName = import.meta.env.VITE_APP_NAME || 'SocialSync';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('workspace/'):
                return WorkspaceLayout;
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.use(pinia);
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// Set light / dark mode on page load
initializeTheme();

// Listen for flash toast data from the server
initializeFlashToast();
