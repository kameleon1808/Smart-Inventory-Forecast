<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Procurement\PurchaseOrder;
use App\Domain\Procurement\PurchaseOrderLine;
use App\Domain\Warehouse;
use App\Services\ProcurementSuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcurementSuggestionController extends Controller
{
    public function index(Request $request, ProcurementSuggestionService $service): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');
        $warehouseId = $request->input('warehouse_id') ?: Warehouse::where('location_id', $location->id)->value('id');

        $warehouses = Warehouse::where('location_id', $location->id)->orderBy('name')->get();
        $suggestions = $warehouseId ? $service->suggestions($organization->id, $location->id, (int) $warehouseId) : collect();

        return view('procurement.suggestions', [
            'warehouses' => $warehouses,
            'warehouse_id' => $warehouseId,
            'suggestions' => $suggestions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        DB::transaction(function () use ($data, $organization, $location, $request) {
            $po = PurchaseOrder::create([
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'warehouse_id' => $data['warehouse_id'],
                'supplier_name' => $data['supplier_name'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['lines'] as $line) {
                $item = Item::findOrFail($line['item_id']);
                $pack = $item->pack_size ?: 1;

                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                    'qty_ordered_in_base' => $line['qty'],
                    'unit_id_display' => $item->base_unit_id,
                    'qty_display' => $line['qty'],
                    'unit_cost_estimate' => null,
                ]);
            }
        });

        return redirect()->route('procurement.suggestions')->with('status', 'po-created');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('approve-po');

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('status', 'po-not-draft');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'po-approved');
    }
}
