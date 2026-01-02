<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'user_organizations')
            ->withTimestamps();
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_locations')
            ->withPivot(['role_id'])
            ->withTimestamps();
    }

    public function hasRole(string $roleSlug, ?Location $location = null): bool
    {
        if ($roleSlug === Role::ADMIN) {
            return $this->locations->contains(function (Location $loc) {
                return $this->locationHasRole($loc, Role::ADMIN);
            });
        }

        $location ??= $this->locations->first();

        if (! $location) {
            return false;
        }

        return $this->locationHasRole($location, $roleSlug);
    }

    public function hasAtLeastRole(string $roleSlug, Location $location): bool
    {
        $currentRole = $this->roleForLocation($location);

        if (! $currentRole) {
            return false;
        }

        return Role::priority($currentRole->slug) >= Role::priority($roleSlug);
    }

    public function roleForLocation(Location $location): ?Role
    {
        $match = $this->locations->firstWhere('id', $location->id);

        if (! $match || ! $match->pivot?->role_id) {
            return null;
        }

        return Role::find($match->pivot->role_id);
    }

    protected function locationHasRole(Location $location, string $roleSlug): bool
    {
        $match = $this->locations->firstWhere('id', $location->id);

        return (bool) ($match && $match->pivot && Role::find($match->pivot->role_id)?->slug === $roleSlug);
    }
}
