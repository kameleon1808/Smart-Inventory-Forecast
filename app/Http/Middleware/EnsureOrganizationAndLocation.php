<?php

namespace App\Http\Middleware;

use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAndLocation
{
    /**
     * Ensure the authenticated user has an active organization/location context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user->loadMissing('locations.organization');

        $activeLocationId = (int) $request->session()->get('active_location_id');
        $location = $activeLocationId ? $user->locations->firstWhere('id', $activeLocationId) : null;

        if (! $location) {
            $location = $user->locations->first();

            if ($location) {
                $request->session()->put('active_location_id', $location->id);
            }
        }

        if (! $location) {
            $organization = Organization::firstOrCreate(['name' => 'Default Organization']);
            $defaultLocation = Location::firstOrCreate(
                ['name' => 'Default Location', 'organization_id' => $organization->id]
            );
            $viewerRole = Role::firstOrCreate(
                ['slug' => Role::VIEWER],
                ['name' => 'Viewer']
            );

            $user->organizations()->syncWithoutDetaching([$defaultLocation->organization_id]);
            $user->locations()->syncWithoutDetaching([
                $defaultLocation->id => ['role_id' => $viewerRole->id],
            ]);

            $user->load('locations.organization');
            $location = $user->locations->first();
            $request->session()->put('active_location_id', $location?->id);
        }

        if (! $location) {
            abort(403, 'No location assigned to your account.');
        }

        $location->setRelation('organization', $location->organization);

        $request->attributes->set('active_location', $location);
        $request->attributes->set('active_organization', $location->organization);

        app()->instance(Location::class, $location);
        app()->instance('activeLocation', $location);
        app()->instance('activeOrganization', $location->organization);

        return $next($request);
    }
}
