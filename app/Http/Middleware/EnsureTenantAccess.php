<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantSlug = $request->route('tenant_slug');

        if (! is_string($tenantSlug) || empty($tenantSlug)) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant workspace not specified.');
        }

        /** @var Organization|null $organization */
        $organization = Organization::query()->where('slug', $tenantSlug)->first();

        if (! $organization) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant workspace not found.');
        }

        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Superadmins oversee all subscribed tenants
        if ($user->isSuperAdmin()) {
            app()->instance('tenant', $organization);
            $request->attributes->set('tenant', $organization);

            return $next($request);
        }

        // Regular users must belong to this tenant
        if ($user->organization_id !== $organization->id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. You do not belong to this tenant organization.');
        }

        app()->instance('tenant', $organization);
        $request->attributes->set('tenant', $organization);

        return $next($request);
    }
}
