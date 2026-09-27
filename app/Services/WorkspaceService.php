<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

final class WorkspaceService
{
    /**
     * @return Collection<int, Organization>
     */
    public function getUserOrganizations(): Collection
    {
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
