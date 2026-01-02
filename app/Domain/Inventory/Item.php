<?php

namespace App\Domain\Inventory;

use App\Domain\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'category_id',
        'base_unit_id',
        'sku',
        'name',
        'pack_size',
        'min_stock',
        'safety_stock',
        'lead_time_days',
        'shelf_life_days',
        'is_active',
    ];

    protected $casts = [
        'pack_size' => 'float',
        'min_stock' => 'float',
        'safety_stock' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($term): void {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function scopeCategory(Builder $query, ?int $categoryId): Builder
    {
        if (! $categoryId) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    public function scopeActive(Builder $query, ?bool $isActive): Builder
    {
        if ($isActive === null) {
            return $query;
        }

        return $query->where('is_active', $isActive);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
