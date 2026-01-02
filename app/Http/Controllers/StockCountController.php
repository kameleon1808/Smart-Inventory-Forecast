<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockCount;
use App\Domain\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockCountController extends Controller
{
    public function create(Request $request): View
    {
        return $this->form($request, new StockCount());
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $data = $this->validateCount($request, $location->id);

        $count = StockCount::create([
            'organization_id' => $organization->id,
            'location_id' => $location->id,
            'warehouse_id' => $data['warehouse_id'],
            'status' => StockCount::STATUS_DRAFT,
            'counted_at' => $data['counted_at'],
            'created_by' => $request->user()->id,
        ]);

        $this->syncLines($count, $data['lines']);

        return redirect()->route('stock-counts.edit', $count)->with('status', 'count-saved');
    }

    public function edit(Request $request, StockCount $stockCount): View
    {
        $this->authorizeLocation($request, $stockCount);

        return $this->form($request, $stockCount);
    }

    public function update(Request $request, StockCount $stockCount): RedirectResponse
    {
        $this->authorizeLocation($request, $stockCount);

        if ($stockCount->status === StockCount::STATUS_POSTED) {
            abort(403, 'Cannot edit a posted stock count.');
        }

        $data = $this->validateCount($request, $stockCount->location_id);

        $stockCount->update([
            'warehouse_id' => $data['warehouse_id'],
            'counted_at' => $data['counted_at'],
        ]);

        $this->syncLines($stockCount, $data['lines']);

        return redirect()->route('stock-counts.edit', $stockCount)->with('status', 'count-updated');
    }

    public function post(Request $request, StockCount $stockCount, StockCountService $service, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeLocation($request, $stockCount);

        $request->user()->can('post-stock-count') || abort(403);

        $differences = $service->post($stockCount);
        $audit->log('stockcount.posted', $stockCount, null, ['warehouse_id' => $stockCount->warehouse_id, 'counted_at' => $stockCount->counted_at]);

        return redirect()->route('stock.ledger')->with([
            'status' => 'count-posted',
            'count_summary' => $differences,
        ]);
    }

    private function validateCount(Request $request, int $locationId): array
    {
        return $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('location_id', $locationId)],
            'counted_at' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'distinct', Rule::exists('items', 'id')],
            'lines.*.counted_quantity' => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @param array<int, array{item_id:int, counted_quantity:float}> $lines
     */
    private function syncLines(StockCount $count, array $lines): void
    {
        $count->lines()->delete();

        foreach ($lines as $line) {
            $count->lines()->create([
                'item_id' => $line['item_id'],
                'counted_quantity_in_base' => $line['counted_quantity'],
            ]);
        }
    }

    private function form(Request $request, StockCount $stockCount): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $warehouses = Warehouse::where('location_id', $location->id)->orderBy('name')->get();
        $items = Item::with('baseUnit')
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        $stockCount->loadMissing('lines');

        return view('stock.count-form', [
            'count' => $stockCount,
            'warehouses' => $warehouses,
            'items' => $items,
        ]);
    }

    private function authorizeLocation(Request $request, StockCount $stockCount): void
    {
        $activeLocation = $request->attributes->get('active_location');
        if ($stockCount->location_id !== $activeLocation?->id) {
            abort(403);
        }
    }
}
