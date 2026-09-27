<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user with auto-assigned tenant organization.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'organization_name' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        $orgName = ! empty($input['organization_name'])
            ? trim((string) $input['organization_name'])
            : $input['name'].' Workspace';

        $baseSlug = Str::slug($orgName);
        $slug = ! empty($baseSlug) ? $baseSlug : 'workspace';
        if (Organization::query()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $organization = Organization::query()->create([
            'name' => $orgName,
            'slug' => $slug,
            'timezone' => 'UTC',
        ]);

        /** @var Plan|null $defaultPlan */
        $defaultPlan = Plan::query()->where('slug', 'plan-pro')->first()
            ?? Plan::query()->basePlans()->first();

        /** @var Subscription $subscription */
        $subscription = Subscription::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'stripe',
            'customer_id' => 'cus_'.Str::random(14),
            'subscription_id' => 'sub_'.Str::random(14),
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ]);

        if ($defaultPlan) {
            $subscription->subscriptionItems()->create([
                'plan_id' => $defaultPlan->id,
                'quantity' => 1,
            ]);
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'organization_id' => $organization->id,
        ]);
    }
}
