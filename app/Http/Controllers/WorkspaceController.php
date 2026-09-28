<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Services\WorkspaceService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {}

    /**
     * Redirect workspace root to posts feed.
     */
    public function index(string $tenant_slug): RedirectResponse
    {
        return redirect("/w/{$tenant_slug}/posts");
    }

    /**
     * Render the workspace posts feed.
     */
    public function posts(string $tenant_slug): Response
    {
        return Inertia::render('workspace/PostsFeed', [
            'tenant_slug' => $tenant_slug,
        ]);
    }

    /**
     * Render the workspace post composer.
     */
    public function composer(string $tenant_slug): Response
    {
        return Inertia::render('workspace/Composer', [
            'tenant_slug' => $tenant_slug,
        ]);
    }

    /**
     * Render the connected social channels management view.
     */
    public function channels(string $tenant_slug): Response
    {
        return Inertia::render('workspace/Channels', [
            'tenant_slug' => $tenant_slug,
        ]);
    }

    /**
     * Render the workspace settings view.
     */
    public function settings(Request $request, string $tenant_slug): Response
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('tenant') ?? Organization::query()->where('slug', $tenant_slug)->firstOrFail();
        /** @var User|null $user */
        $user = $request->user();

        $canManageTwoFactor = class_exists(Features::class) && Features::canManageTwoFactorAuthentication();
        $canManagePasskeys = class_exists(Features::class) && Features::canManagePasskeys();

        $passkeys = [];
        if ($canManagePasskeys && $user) {
            $passkeys = $user->passkeys()
                ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                ->latest()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'authenticator' => $p->authenticator,
                    'created_at_diff' => $p->created_at?->diffForHumans() ?? '',
                    'last_used_at_diff' => $p->last_used_at?->diffForHumans(),
                ])
                ->values()
                ->all();
        }

        $twoFactorEnabled = false;
        $requiresConfirmation = false;
        if ($canManageTwoFactor && $user) {
            $twoFactorEnabled = (bool) $user->hasEnabledTwoFactorAuthentication();
            $requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $members = $this->workspaceService->getMembers($organization);
        $seats = $this->workspaceService->getSeatsLimit($organization);

        return Inertia::render('workspace/Settings', [
            'tenant_slug' => $tenant_slug,
            'organization_id' => (string) $organization->id,
            'organization_name' => $organization->name,
            'organization_seats' => $seats,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'canManageTwoFactor' => $canManageTwoFactor,
            'canManagePasskeys' => $canManagePasskeys,
            'passkeys' => $passkeys,
            'twoFactorEnabled' => $twoFactorEnabled,
            'requiresConfirmation' => $requiresConfirmation,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'members' => $members,
        ]);
    }
}
