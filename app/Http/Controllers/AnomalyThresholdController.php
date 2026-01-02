<?php

namespace App\Http\Controllers;

use App\Domain\Anomaly\AnomalyThreshold;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnomalyThresholdController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->attributes->get('active_location');
        $organization = $request->attributes->get('active_organization');

        $thresholds = AnomalyThreshold::with('item', 'category')
            ->where('organization_id', $organization->id)
            ->where('location_id', $location->id)
            ->orderBy('type')
            ->get();

        $items = Item::where('organization_id', $organization->id)->orderBy('name')->get();
        $categories = ItemCategory::orderBy('name')->get();

        return view('anomalies.thresholds', [
            'thresholds' => $thresholds,
            'items' => $items,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $location = $request->attributes->get('active_location');
        $organization = $request->attributes->get('active_organization');

        $data = $request->validate([
            'type' => ['required', 'string'],
            'item_id' => ['nullable', 'exists:items,id'],
            'category_id' => ['nullable', 'exists:item_categories,id'],
            'absolute_threshold' => ['nullable', 'numeric', 'min:0'],
            'percent_threshold' => ['nullable', 'numeric', 'min:0'],
            'count_threshold' => ['nullable', 'integer', 'min:0'],
            'severity' => ['required', 'string'],
        ]);

        AnomalyThreshold::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'type' => $data['type'],
                'item_id' => $data['item_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
            ],
            [
                'absolute_threshold' => $data['absolute_threshold'],
                'percent_threshold' => $data['percent_threshold'],
                'count_threshold' => $data['count_threshold'],
                'severity' => $data['severity'],
            ]
        );

        return back()->with('status', 'threshold-saved');
    }
}
