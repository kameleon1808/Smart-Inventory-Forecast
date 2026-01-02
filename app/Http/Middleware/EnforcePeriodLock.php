<?php

namespace App\Http\Middleware;

use App\Domain\Role;
use Closure;
use Illuminate\Http\Request;

class EnforcePeriodLock
{
    public function handle(Request $request, Closure $next)
    {
        $location = $request->attributes->get('active_location');
        $lockDate = $location?->lock_before_date;

        if ($lockDate) {
            $happenedAt = $request->input('happened_at') ?: $request->input('counted_at');

            if ($happenedAt && $request->user()) {
                $date = \Carbon\Carbon::parse($happenedAt)->toDateString();
                $isAdmin = $request->user()->hasRole(Role::ADMIN, $location);

                if (! $isAdmin && $date < $lockDate) {
                    abort(403, 'Period locked before '.$lockDate);
                }
            }
        }

        return $next($request);
    }
}
