<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orgId = $request->header('X-Organization-Id');

        if (empty($orgId)) {
            return response()->json([
                'message' => 'Missing X-Organization-Id header.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var Organization|null $organization */
        $organization = Organization::query()->find($orgId);

        if (! $organization) {
            return response()->json([
                'message' => 'Unauthorized or invalid tenant organization.',
            ], Response::HTTP_FORBIDDEN);
        }

        app()->instance('tenant', $organization);
        $request->attributes->set('tenant', $organization);
        $request->attributes->set('tenant_id', $orgId);

        return $next($request);
    }
}
