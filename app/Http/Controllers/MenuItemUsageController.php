<?php

namespace App\Http\Controllers;

use App\Domain\Recipes\MenuItem;
use App\Domain\Recipes\MenuItemUsage;
use App\Jobs\RecomputeExpectedConsumptionJob;
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

        $usedOn = $data['used_on'];

        foreach ($data['usages'] as $menuItemId => $qty) {
            if (! $qty || ! in_array((int) $menuItemId, $menuItems, true)) {
                continue;
            }

            MenuItemUsage::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'location_id' => $location->id,
                    'menu_item_id' => $menuItemId,
                    'used_on' => $usedOn,
                ],
                [
                    'quantity' => $qty,
                    'created_by' => $request->user()->id,
                ]
            );
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
