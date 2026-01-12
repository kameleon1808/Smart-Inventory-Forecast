<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\StockTransactionLine;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Services\AuditLogger;
use App\Services\UnitConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTransactionController extends Controller
{
    public function ledger(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $transactions = StockTransaction::with(['warehouse', 'lines.item'])
            ->where('organization_id', $organization->id)
            ->where('location_id', $location->id)
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('happened_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('happened_at', '<=', $request->date('to')))
            ->orderByDesc('happened_at')
            ->paginate(15)
            ->withQueryString();

        $warehouses = Warehouse::where('location_id', $location->id)->orderBy('name')->get();

        return view('stock.ledger', [
            'transactions' => $transactions,
            'warehouses' => $warehouses,
            'filters' => $request->only(['warehouse_id', 'type', 'from', 'to']),
        ]);
    }

    public function createReceipt(Request $request): View
    {
        return $this->formView($request, StockTransaction::TYPE_RECEIPT);
    }

    public function createWaste(Request $request): View
    {
        return $this->formView($request, StockTransaction::TYPE_WASTE);
    }

    public function createInternalUse(Request $request): View
    {
        return $this->formView($request, StockTransaction::TYPE_INTERNAL_USE);
    }

    public function createAdjustment(Request $request): View
    {
        return $this->formView($request, StockTransaction::TYPE_ADJUSTMENT);
    }

    public function store(Request $request, UnitConversionService $converter, AuditLogger $audit): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $type = $request->string('type');

        $data = $request->validate([
            'type' => ['required', Rule::in([
                StockTransaction::TYPE_RECEIPT,
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
            ])],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('location_id', $location->id)],
            'happened_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'item_id' => ['required', 'exists:items,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'quantity' => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric'],
        ]);

        if (in_array($data['type'], [StockTransaction::TYPE_WASTE, StockTransaction::TYPE_INTERNAL_USE], true)) {
            $request->validate([
                'reason' => ['required', 'string', 'max:255'],
            ]);
        }

        if ($data['type'] === StockTransaction::TYPE_RECEIPT) {
            $request->validate([
                'supplier_name' => ['required', 'string', 'max:255'],
            ]);
        }

        $item = Item::with('baseUnit')->findOrFail($data['item_id']);
        $unit = Unit::findOrFail($data['unit_id']);

        $quantityInBase = $converter->convert((float) $data['quantity'], $unit, $item->baseUnit);

        if (in_array($data['type'], [StockTransaction::TYPE_WASTE, StockTransaction::TYPE_INTERNAL_USE], true)) {
            $quantityInBase *= -1;
        }

        DB::transaction(function () use ($organization, $location, $data, $item, $quantityInBase, $audit): void {
            $transaction = StockTransaction::create([
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'warehouse_id' => $data['warehouse_id'],
                'type' => $data['type'],
                'status' => StockTransaction::STATUS_POSTED,
                'happened_at' => $data['happened_at'],
                'reference' => $data['reference'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'reason' => $data['reason'] ?? null,
                'created_by' => request()->user()->id,
            ]);

            StockTransactionLine::create([
                'stock_transaction_id' => $transaction->id,
                'item_id' => $item->id,
                'unit_id' => $data['unit_id'],
                'quantity' => $data['quantity'],
                'quantity_in_base' => $quantityInBase,
                'unit_cost' => $data['unit_cost'] ?? null,
            ]);

            if ($data['type'] === StockTransaction::TYPE_ADJUSTMENT) {
                $audit->log('stock.adjustment', $transaction, null, [
                    'item_id' => $item->id,
                    'quantity' => $data['quantity'],
                    'warehouse_id' => $data['warehouse_id'],
                    'happened_at' => $data['happened_at'],
                ]);
            }
        });

        return redirect()->route('stock.ledger')->with('status', 'transaction-posted');
    }

    private function formView(Request $request, string $type): View
    {
        $location = $request->attributes->get('active_location');
        $organization = $request->attributes->get('active_organization');

        $warehouses = Warehouse::where('location_id', $location->id)->orderBy('name')->get();
        $items = Item::where('organization_id', $organization->id)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('stock.form', [
            'type' => $type,
            'warehouses' => $warehouses,
            'items' => $items,
            'units' => $units,
        ]);
    }
}
