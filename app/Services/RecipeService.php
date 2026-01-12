<?php

namespace App\Services;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use App\Domain\Recipes\MenuItem;
use App\Domain\Recipes\RecipeIngredient;
use App\Domain\Recipes\RecipeVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeService
{
    public function __construct(private readonly UnitConversionService $converter)
    {
    }

    /**
     * Create a new recipe version with ingredients, ensuring no overlaps.
     *
     * @param array<int, array{item_id:int, unit_id:int, quantity:float}> $ingredients
     */
    public function createVersion(MenuItem $menuItem, string $validFrom, array $ingredients): RecipeVersion
    {
        $validFromDate = Carbon::parse($validFrom)->toDateString();

        return DB::transaction(function () use ($menuItem, $validFromDate, $ingredients) {
            $latestOpen = $menuItem->recipeVersions()
                ->whereNull('valid_to')
                ->orderByDesc('valid_from')
                ->first();

            if ($latestOpen && $latestOpen->valid_from >= $validFromDate) {
                throw ValidationException::withMessages([
                    'valid_from' => 'Recipe version overlaps with an existing version.',
                ]);
            }

            if ($latestOpen) {
                $latestOpen->update(['valid_to' => Carbon::parse($validFromDate)->subDay()->toDateString()]);
            }

            $overlap = $menuItem->recipeVersions()
                ->where('valid_from', '<=', $validFromDate)
                ->where(function ($q) use ($validFromDate) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $validFromDate);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'valid_from' => 'Recipe version overlaps with an existing version.',
                ]);
            }

            $version = $menuItem->recipeVersions()->create([
                'valid_from' => $validFromDate,
                'valid_to' => null,
            ]);

            $this->syncIngredients($version, $ingredients);

            return $version;
        });
    }

    /**
     * @param array<int, array{item_id:int, unit_id:int, quantity:float}> $ingredients
     */
    public function syncIngredients(RecipeVersion $version, array $ingredients): void
    {
        $version->ingredients()->delete();

        foreach ($ingredients as $ingredient) {
            $item = Item::with('baseUnit')->findOrFail($ingredient['item_id']);
            $unit = Unit::findOrFail($ingredient['unit_id']);

            $quantityInBase = $this->converter->convert((float) $ingredient['quantity'], $unit, $item->baseUnit);

            RecipeIngredient::create([
                'recipe_version_id' => $version->id,
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'quantity' => $ingredient['quantity'],
                'quantity_in_base' => $quantityInBase,
            ]);
        }
    }

    /**
     * Ensure no version overlaps for given menu item.
     */
    public function assertNoOverlap(MenuItem $menuItem, string $validFrom, ?string $validTo): void
    {
        $validFromDate = Carbon::parse($validFrom)->toDateString();
        $validToDate = $validTo ? Carbon::parse($validTo)->toDateString() : null;

        $overlap = $menuItem->recipeVersions()
            ->where(function ($q) use ($validFromDate, $validToDate) {
                $q->where('valid_from', '<=', $validFromDate)
                    ->where(function ($qq) use ($validToDate) {
                        $qq->whereNull('valid_to');
                        if ($validToDate) {
                            $qq->orWhere('valid_to', '>=', $validToDate);
                        }
                    });
            })->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'valid_from' => 'Recipe version overlaps with an existing version.',
            ]);
        }
    }
}
