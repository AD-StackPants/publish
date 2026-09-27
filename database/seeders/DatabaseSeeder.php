<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database for production readiness.
     */
    public function run(): void
    {
        // 1. Seed Plans Catalog
        $plans = [
            [
                'slug' => 'plan-free',
                'name' => 'Free Plan',
                'type' => 'base_plan',
                'price_cents' => 0,
                'billing_interval' => 'month',
                'features' => [
                    'channels_limit' => 1,
                    'allows_scheduling' => false,
                    'team_members' => 1,
                    'description' => 'Basic social connectivity for hobbyists and individual creators.',
                ],
                'is_active' => true,
            ],
            [
                'slug' => 'plan-pro',
                'name' => 'Pro Plan',
                'type' => 'base_plan',
                'price_cents' => 1900,
                'billing_interval' => 'month',
                'features' => [
                    'channels_limit' => 5,
                    'allows_scheduling' => true,
                    'team_members' => 5,
                    'description' => 'Essential toolkit for fast-growing brands and professional creators.',
                ],
                'is_active' => true,
            ],
            [
                'slug' => 'plan-agency',
                'name' => 'Agency Plan',
                'type' => 'base_plan',
                'price_cents' => 4900,
                'billing_interval' => 'month',
                'features' => [
                    'channels_limit' => 15,
                    'allows_scheduling' => true,
                    'team_members' => 25,
                    'priority_worker' => true,
                    'description' => 'High-throughput publishing cluster for agencies and multi-brand teams.',
                ],
                'is_active' => true,
            ],
            [
                'slug' => 'addon-extra-channels',
                'name' => 'Extra Channel Pack (+3)',
                'type' => 'addon',
                'price_cents' => 500,
                'billing_interval' => 'month',
                'features' => [
                    'channels_limit' => 3,
                    'description' => 'Expand your publishing reach with 3 additional connected social channels.',
                ],
                'is_active' => true,
            ],
            [
                'slug' => 'addon-priority-worker',
                'name' => 'Dedicated Priority Worker',
                'type' => 'addon',
                'price_cents' => 1000,
                'billing_interval' => 'month',
                'features' => [
                    'priority_worker' => true,
                    'description' => 'Dedicated isolated queue runner with sub-100ms instant dispatch SLA.',
                ],
                'is_active' => true,
            ],
            [
                'slug' => 'addon-custom-domain',
                'name' => 'Custom Short Domain',
                'type' => 'addon',
                'price_cents' => 500,
                'billing_interval' => 'month',
                'features' => [
                    'custom_domain' => true,
                    'description' => 'Branded link shortener with custom OpenGraph attribution.',
                ],
                'is_active' => true,
            ],
        ];

        /** @var array<string, Plan> $seededPlans */
        $seededPlans = [];
        foreach ($plans as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
            $seededPlans[$planData['slug']] = $plan;
        }

        // 2. Seed Tenant Organization: "stackpants"
        /** @var Organization $tenant */
        $tenant = Organization::query()->updateOrCreate(
            ['slug' => 'stackpants'],
            [
                'name' => 'Stackpants',
                'timezone' => 'UTC',
            ]
        );

        // 3. Seed Active Subscription for the "stackpants" Tenant
        $proPlan = $seededPlans['plan-pro'] ?? Plan::query()->where('slug', 'plan-pro')->first();

        /** @var Subscription $subscription */
        $subscription = Subscription::query()->updateOrCreate(
            ['organization_id' => $tenant->id],
            [
                'provider' => 'stripe',
                'customer_id' => 'cus_stackpants_prod',
                'subscription_id' => 'sub_stackpants_prod',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
                'cancel_at_period_end' => false,
            ]
        );

        if ($proPlan) {
            $subscription->subscriptionItems()->updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $proPlan->id,
                ],
                [
                    'quantity' => 1,
                ]
            );
        }

        // 4. Seed Standard User (keeping test email: test@example.com)
        $defaultPassword = Hash::make('password');

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'is_superadmin' => false,
            ]
        );

        // 5. Seed Superadmin User (oversees subscribed tenants and bypasses subscription-based restrictions)
        User::query()->updateOrCreate(
            ['email' => 'superadmin@stackpants.com'],
            [
                'name' => 'Super Admin',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'is_superadmin' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'is_superadmin' => true,
            ]
        );
    }
}
