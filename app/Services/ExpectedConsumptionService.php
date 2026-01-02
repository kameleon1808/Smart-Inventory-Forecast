<?php

namespace App\Services;

use App\Domain\Inventory\ExpectedConsumptionDaily;
use App\Domain\Recipes\MenuItemUsage;
use App\Domain\Recipes\RecipeVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpectedConsumptionService
{
    /**
     * Recompute expected consumption for a date range and location.
     */
    public function recompute(int $organizationId, int $locationId, string $fromDate, string $toDate): void
    {
        $from = Carbon::parse($fromDate)->toDateString();
        $to = Carbon::parse($toDate)->toDateString();

        $usages = MenuItemUsage::where('organization_id', $organizationId)
            ->where('location_id', $locationId)
            ->whereBetween('used_on', [$from, $to])
            ->get();

        if ($usages->isEmpty()) {
            ExpectedConsumptionDaily::where('organization_id', $organizationId)
                ->where('location_id', $locationId)
                ->whereBetween('date', [$from, $to])
                ->delete();
            return;
        }

        $aggregates = [];

        foreach ($usages as $usage) {
            $version = RecipeVersion::where('menu_item_id', $usage->menu_item_id)
                ->where('valid_from', '<=', $usage->used_on)
                ->where(function ($q) use ($usage) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $usage->used_on);
                })
                ->orderByDesc('valid_from')
                ->first();

            if (! $version) {
                continue;
            }

            $version->loadMissing('ingredients');

            foreach ($version->ingredients as $ingredient) {
                $key = $usage->used_on.'|'.$ingredient->item_id;
                $aggregates[$key] = ($aggregates[$key] ?? 0) + ($usage->quantity * (float) $ingredient->quantity_in_base);
            }
        }

        DB::transaction(function () use ($aggregates, $organizationId, $locationId, $from, $to): void {
            ExpectedConsumptionDaily::where('organization_id', $organizationId)
                ->where('location_id', $locationId)
                ->whereBetween('date', [$from, $to])
                ->delete();

            foreach ($aggregates as $key => $expected) {
                [$date, $itemId] = explode('|', $key);

                ExpectedConsumptionDaily::create([
                    'organization_id' => $organizationId,
                    'location_id' => $locationId,
                    'date' => $date,
                    'item_id' => (int) $itemId,
                    'expected_qty_in_base' => $expected,
                ]);
            }
        });
    }
}
