<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {}

    /**
     * Handle incoming dashboard redirect to user's active tenant workspace.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $preferredSlug = $user->isSuperAdmin() ? $request->cookie('active_tenant_slug') : null;
        $organization = $this->workspaceService->resolveUserOrganization(
            $user,
            is_string($preferredSlug) ? $preferredSlug : null
        );

        $redirectPath = $request->query('redirect', '/posts');
        if (! is_string($redirectPath) || ! str_starts_with($redirectPath, '/')) {
            $redirectPath = '/posts';
        }

        return redirect("/w/{$organization->slug}{$redirectPath}");
    }
}
