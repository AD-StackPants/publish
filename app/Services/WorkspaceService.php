<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class WorkspaceService
{
    /**
     * @return Collection<int, Organization>
     */
    public function getUserOrganizations(?User $user = null): Collection
    {
        $user = $user ?? Auth::user();

        if ($user instanceof User && ! $user->isSuperAdmin()) {
            if ($user->organization_id) {
                return Organization::query()->where('id', $user->organization_id)->get();
            }

            return new Collection;
        }

        return Organization::query()->get();
    }

    /**
     * Resolve the active organization for a user, or provision a default workspace if none exists.
     */
    public function resolveUserOrganization(User $user, ?string $preferredSlug = null): Organization
    {
        /** @var Organization|null $organization */
        $organization = null;

        if ($user->isSuperAdmin()) {
            if (is_string($preferredSlug) && ! empty($preferredSlug)) {
                $organization = Organization::query()->where('slug', $preferredSlug)->first();
            }
            if (! $organization) {
                $organization = $user->organization ?? Organization::query()->first();
            }
        } else {
            $organization = $user->organization;
        }

        if (! $organization) {
            $baseSlug = Str::slug($user->name);
            $slug = ! empty($baseSlug) ? $baseSlug : 'workspace';
            if (Organization::query()->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::lower(Str::random(4));
            }

            $organization = Organization::query()->create([
                'name' => $user->name.' Workspace',
                'slug' => $slug,
                'timezone' => 'UTC',
            ]);

            $user->update(['organization_id' => $organization->id]);
        }

        return $organization;
    }

    public function getSeatsLimit(Organization $organization): int
    {
        if ($organization->subscription) {
            $basePlan = $organization->subscription->basePlan();

            return (int) ($basePlan?->features['team_members'] ?? 5);
        }

        return 5;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrganization(string $id, array $data): Organization
    {
        /** @var Organization $organization */
        $organization = Organization::query()->findOrFail($id);
        $organization->update($data);

        return $organization;
    }

    /**
     * @return array<int, array{id: string, name: string, email: string, role: string, created_at: string}>
     */
    public function getMembers(Organization $organization): array
    {
        return $organization->users()
            ->select(['id', 'name', 'email', 'is_superadmin', 'role', 'created_at'])
            ->get()
            ->map(fn (User $u) => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->isSuperAdmin() ? 'Owner' : ($u->role ?: 'Member'),
                'created_at' => $u->created_at?->format('M d, Y') ?? 'Recently',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: string, email: string, role: string, created_at: string}
     */
    public function inviteMember(Organization $organization, string $email, string $role = 'Editor'): array
    {
        // 1. Check seat limits
        $seatsLimit = $this->getSeatsLimit($organization);

        if ($organization->users()->count() >= $seatsLimit) {
            throw ValidationException::withMessages([
                'email' => "Workspace seat limit of {$seatsLimit} reached. Upgrade your subscription or add extra seats in billing to invite more members.",
            ]);
        }

        // 2. Check if already member
        $existingMember = $organization->users()->where('email', $email)->first();
        if ($existingMember) {
            throw ValidationException::withMessages([
                'email' => 'This user is already a member of this workspace.',
            ]);
        }

        // 3. Find or create user
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $user->update([
                'organization_id' => $organization->id,
                'role' => $role,
            ]);
        } else {
            $name = ucfirst(explode('@', $email)[0]);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                // 'password' => Hash::make(Str::random(24)),
                'password' => Hash::make('password'),
                'organization_id' => $organization->id,
                'is_superadmin' => false,
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }

        /** @var User|null $inviter */
        $inviter = Auth::user();

        // 4. Send email invitation notification
        $user->notify(new WorkspaceInvitationNotification($organization, $role, $inviter));

        // 5. Structured audit logging
        Log::info('Workspace invitation sent to team member', [
            'tenant_id' => (string) $organization->id,
            'organization_slug' => $organization->slug,
            'recipient_email' => $email,
            'role' => $role,
            'inviter_id' => $inviter ? (string) $inviter->id : null,
            'inviter_email' => $inviter?->email,
        ]);

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'created_at' => $user->created_at?->format('M d, Y') ?? 'Just now',
        ];
    }

    public function removeMember(Organization $organization, string $userId): bool
    {
        /** @var User $user */
        $user = $organization->users()->where('id', $userId)->firstOrFail();

        if ($user->isSuperAdmin() || $user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'member' => 'Cannot remove the workspace owner or yourself.',
            ]);
        }

        $user->update([
            'organization_id' => null,
            'role' => 'Member',
        ]);

        return true;
    }
}
