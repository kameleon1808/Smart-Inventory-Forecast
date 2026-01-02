<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const WAITER = 'waiter';
    public const VIEWER = 'viewer';

    protected $fillable = [
        'name',
        'slug',
    ];

    public static function priority(string $slug): int
    {
        return [
            self::VIEWER => 0,
            self::WAITER => 1,
            self::MANAGER => 2,
            self::ADMIN => 3,
        ][$slug] ?? -1;
    }

    public static function ordered(): array
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::WAITER,
            self::VIEWER,
        ];
    }

    public function userLocations(): HasMany
    {
        return $this->hasMany(UserLocation::class);
    }
}
