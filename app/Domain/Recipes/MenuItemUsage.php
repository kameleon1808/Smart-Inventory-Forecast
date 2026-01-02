<?php

namespace App\Domain\Recipes;

use App\Domain\Location;
use App\Domain\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'location_id',
        'menu_item_id',
        'used_on',
        'quantity',
        'created_by',
    ];

    protected $casts = [
        'used_on' => 'date',
        'quantity' => 'float',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
