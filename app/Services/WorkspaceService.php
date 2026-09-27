<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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
     * @param  array<string, mixed>  $data
     */
    public function updateOrganization(string $id, array $data): Organization
    {
        /** @var Organization $organization */
        $organization = Organization::query()->findOrFail($id);
        $organization->update($data);

        return $organization;
    }
}
