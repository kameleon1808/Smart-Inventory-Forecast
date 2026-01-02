<?php

namespace App\Http\Controllers;

use App\Domain\Forecast\ForecastJob;
use App\Domain\Forecast\ForecastResultDaily;
use App\Domain\Inventory\Item;
use App\Domain\Location;
use App\Jobs\PredictForecastJob;
use App\Services\ForecastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ForecastController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $locations = $user->locations()->with('organization')->get();
        $selectedLocationId = (int) ($request->input('location_id') ?: $request->session()->get('active_location_id'));
        $location = $locations->firstWhere('id', $selectedLocationId) ?? $locations->first();

        abort_if(! $location, 403);

        $items = Item::where('organization_id', $location->organization_id)
            ->orderBy('name')
            ->get();

        $filters = [
            'item_id' => $request->input('item_id'),
            'horizon' => (int) ($request->input('horizon') ?: 14),
        ];

        $results = ForecastResultDaily::with('item')
            ->where('location_id', $location->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->when($filters['item_id'], fn ($q) => $q->where('item_id', $filters['item_id']))
            ->orderBy('date')
            ->get();

        $lastTrainedAt = ForecastJob::where('location_id', $location->id)
            ->where('status', ForecastJob::STATUS_COMPLETED)
            ->where('params->action', 'train')
            ->latest('updated_at')
            ->value('updated_at');

        return view('forecast.index', [
            'locations' => $locations,
            'location' => $location,
            'items' => $items,
            'results' => $results,
            'filters' => $filters,
            'lastTrainedAt' => $lastTrainedAt,
        ]);
    }

    public function generate(Request $request, ForecastService $service): RedirectResponse
    {
        $user = $request->user();
        $locations = $user->locations()->pluck('locations.id')->toArray();

        $data = $request->validate([
            'location_id' => ['required', Rule::in($locations)],
            'horizon' => ['required', 'integer', 'min:1', 'max:90'],
            'item_id' => ['nullable', 'exists:items,id'],
        ]);

        $location = Location::findOrFail($data['location_id']);

        $itemIds = $data['item_id']
            ? [(int) $data['item_id']]
            : $service->defaultItemIds($location->organization_id);

        if (empty($itemIds)) {
            return back()->withErrors(['item_id' => __('No items available for forecasting in this location.')]);
        }

        PredictForecastJob::dispatch(
            $location->organization_id,
            $location->id,
            $itemIds,
            (int) $data['horizon'],
            $user->id
        );

        return redirect()->route('forecast.index', [
            'location_id' => $location->id,
            'horizon' => $data['horizon'],
            'item_id' => $data['item_id'],
        ])->with('status', 'forecast-dispatched');
    }
}
