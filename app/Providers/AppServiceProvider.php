<?php

namespace App\Providers;

use App\Domain\Location;
use App\Domain\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-locations', function (User $user): bool {
            return $user->hasRole(Role::ADMIN);
        });

        Gate::define('view-location-data', function (User $user, ?Location $location = null): bool {
            $location ??= app()->bound('activeLocation') ? app('activeLocation') : null;

            if (! $location) {
                return false;
            }

            return $user->hasAtLeastRole(Role::MANAGER, $location);
        });
    }
}
