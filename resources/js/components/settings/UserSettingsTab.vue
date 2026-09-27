<script setup lang="ts">
import { computed } from 'vue';
import { usePage, useForm, Link } from '@inertiajs/vue3';
import {
    User,
    Mail,
    Lock,
    KeyRound,
    Shield,
    Check,
    AlertCircle,
    Loader2,
} from '@lucide/vue';
import PasswordInput from '@/components/PasswordInput.vue';
import InputError from '@/components/InputError.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import { send } from '@/routes/verification';
import type { Passkey } from '@/types/auth';

export interface UserSettingsProps {
    mustVerifyEmail?: boolean;
    status?: string;
    canManageTwoFactor?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
    passwordRules?: string;
}

const props = withDefaults(defineProps<UserSettingsProps>(), {
    mustVerifyEmail: false,
    status: '',
    canManageTwoFactor: false,
    canManagePasskeys: false,
    passkeys: () => [],
    twoFactorEnabled: false,
    requiresConfirmation: false,
    passwordRules: '',
});

const page = usePage();
const authUser = computed(
    () =>
        page.props.auth?.user || {
            name: '',
            email: '',
            email_verified_at: null,
        },
);

// Profile form
const profileForm = useForm({
    name: authUser.value.name || '',
    email: authUser.value.email || '',
});

const handleUpdateProfile = () => {
    profileForm.patch('/settings/profile', {
        preserveScroll: true,
    });
};

// Password update form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const handleUpdatePassword = () => {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-8">
        <!-- 1. Profile Details Form -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <User class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Personal Profile Information
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Update your account display name and primary login email
                        address.
                    </p>
                </div>
            </div>

            <form @submit.prevent="handleUpdateProfile" class="mt-6 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Full Name -->
                    <div class="space-y-1.5">
                        <label
                            for="user-profile-name"
                            class="block text-xs font-semibold text-foreground"
                            >Full Name</label
                        >
                        <input
                            id="user-profile-name"
                            v-model="profileForm.name"
                            type="text"
                            required
                            autocomplete="name"
                            placeholder="Your full name"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs transition focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        />
                        <InputError :message="profileForm.errors.name" />
                    </div>

                    <!-- Email Address -->
                    <div class="space-y-1.5">
                        <label
                            for="user-profile-email"
                            class="block text-xs font-semibold text-foreground"
                            >Email Address</label
                        >
                        <input
                            id="user-profile-email"
                            v-model="profileForm.email"
                            type="email"
                            required
                            autocomplete="username"
                            placeholder="your.email@example.com"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs transition focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        />
                        <InputError :message="profileForm.errors.email" />
                    </div>
                </div>

                <!-- Unverified Email Alert -->
                <div
                    v-if="props.mustVerifyEmail && !authUser.email_verified_at"
                    class="rounded-lg border border-amber-500/20 bg-amber-500/10 p-3.5 text-xs text-amber-900 dark:text-amber-200"
                >
                    <div class="flex items-start gap-2">
                        <AlertCircle
                            class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div>
                            <p class="font-medium">
                                Your email address is unverified.
                            </p>
                            <Link
                                :href="send()"
                                as="button"
                                class="mt-1 cursor-pointer font-semibold text-amber-800 underline hover:text-amber-900 dark:text-amber-200"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.status === 'verification-link-sent'"
                    class="rounded-lg bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-800 dark:text-emerald-200"
                >
                    A new verification link has been sent to your email address.
                </div>

                <div
                    class="flex items-center justify-between border-t border-border pt-4"
                >
                    <span
                        v-if="profileForm.recentlySuccessful"
                        class="flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <Check class="h-3.5 w-3.5" /> Profile updated
                        successfully.
                    </span>
                    <span v-else></span>

                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="profileForm.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <span>Save Profile</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- 2. Update Password Form -->
        <section class="rounded-xl border border-border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-border pb-4">
                <Lock class="h-4 w-4 text-primary" />
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-foreground">
                        Update Password
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Ensure your account is protected with a secure, random
                        password.
                    </p>
                </div>
            </div>

            <form
                @submit.prevent="handleUpdatePassword"
                class="mt-6 max-w-xl space-y-4"
            >
                <!-- Current Password -->
                <div class="space-y-1.5">
                    <label
                        for="current_password"
                        class="block text-xs font-semibold text-foreground"
                        >Current Password</label
                    >
                    <PasswordInput
                        id="current_password"
                        v-model="passwordForm.current_password"
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                        class="text-xs"
                    />
                    <InputError
                        :message="passwordForm.errors.current_password"
                    />
                </div>

                <!-- New Password -->
                <div class="space-y-1.5">
                    <label
                        for="password"
                        class="block text-xs font-semibold text-foreground"
                        >New Password</label
                    >
                    <PasswordInput
                        id="password"
                        v-model="passwordForm.password"
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        class="text-xs"
                    />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="space-y-1.5">
                    <label
                        for="password_confirmation"
                        class="block text-xs font-semibold text-foreground"
                        >Confirm New Password</label
                    >
                    <PasswordInput
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        class="text-xs"
                    />
                    <InputError
                        :message="passwordForm.errors.password_confirmation"
                    />
                </div>

                <div
                    class="flex items-center justify-between border-t border-border pt-4"
                >
                    <span
                        v-if="passwordForm.recentlySuccessful"
                        class="flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <Check class="h-3.5 w-3.5" /> Password changed
                        successfully.
                    </span>
                    <span v-else></span>

                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="passwordForm.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- 3. Two-Factor Authentication -->
        <section
            v-if="props.canManageTwoFactor"
            class="rounded-xl border border-border bg-card p-6 shadow-xs"
        >
            <ManageTwoFactor
                :canManageTwoFactor="props.canManageTwoFactor"
                :requiresConfirmation="props.requiresConfirmation"
                :twoFactorEnabled="props.twoFactorEnabled"
            />
        </section>

        <!-- 4. Biometric & Passkeys -->
        <section
            v-if="props.canManagePasskeys"
            class="rounded-xl border border-border bg-card p-6 shadow-xs"
        >
            <ManagePasskeys
                :canManagePasskeys="props.canManagePasskeys"
                :passkeys="props.passkeys"
            />
        </section>

        <!-- 5. User Account Danger Zone -->
        <section
            class="rounded-xl border border-destructive/20 bg-destructive/5 p-6 shadow-xs"
        >
            <DeleteUser />
        </section>
    </div>
</template>
