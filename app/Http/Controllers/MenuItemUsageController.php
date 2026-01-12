<?php

namespace App\Http\Controllers;

use App\Domain\Recipes\MenuItem;
use App\Domain\Recipes\MenuItemUsage;
use App\Jobs\RecomputeExpectedConsumptionJob;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemUsageController extends Controller
{
    public function create(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');
        $menuItems = MenuItem::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('menu-usage.create', [
            'menuItems' => $menuItems,
            'date' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $data = $request->validate([
            'used_on' => ['required', 'date'],
            'usages' => ['required', 'array'],
            'usages.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $menuItems = MenuItem::where('organization_id', $organization->id)->pluck('id')->all();

        $usedOn = Carbon::parse($data['used_on'])->toDateString();
        $rows = [];

        foreach ($data['usages'] as $menuItemId => $qty) {
            if (! $qty || ! in_array((int) $menuItemId, $menuItems, true)) {
                continue;
            }

            $rows[$menuItemId] = [
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'menu_item_id' => (int) $menuItemId,
                'used_on' => $usedOn,
                'quantity' => $qty,
                'created_by' => $request->user()->id,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (! empty($rows)) {
            $keys = collect($rows)->map(fn ($r) => [
                'organization_id' => $r['organization_id'],
                'location_id' => $r['location_id'],
                'menu_item_id' => $r['menu_item_id'],
                'used_on' => $r['used_on'],
            ]);

            // Upsert replacements: delete existing rows for same keys, then insert fresh
            MenuItemUsage::whereIn('id', MenuItemUsage::where(function ($q) use ($keys) {
                foreach ($keys as $k) {
                    $q->orWhere(function ($qq) use ($k) {
                        $qq->where('organization_id', $k['organization_id'])
                            ->where('location_id', $k['location_id'])
                            ->where('menu_item_id', $k['menu_item_id'])
                            ->whereDate('used_on', $k['used_on']);
                    });
                }
            })->pluck('id'))->delete();

            MenuItemUsage::insert(array_values($rows));
        }

        RecomputeExpectedConsumptionJob::dispatchSync(
            $organization->id,
            $location->id,
            $usedOn,
            $usedOn
        );

        return redirect()->route('menu-usage.create')->with('status', 'usage-saved');
    }
}
