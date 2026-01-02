<?php

namespace App\Http\Controllers;

use App\Domain\Procurement\PurchaseOrder;
use App\Services\PurchaseReceivingService;
use App\Services\UnitConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->attributes->get('active_location');

        $orders = PurchaseOrder::with('warehouse')
            ->where('location_id', $location->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('procurement.purchase_orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder, UnitConversionService $converter): View
    {
        $this->authorizeAccess($request, $purchaseOrder);

        $purchaseOrder->load('lines.item.baseUnit', 'lines.unitDisplay', 'warehouse', 'receipts.lines.item', 'receipts.lines.unitDisplay');
        $lines = $this->lineSummary($purchaseOrder, $converter);
        $receipts = $purchaseOrder->receipts()->with('lines.item', 'lines.unitDisplay')->orderByDesc('received_at')->get();

        return view('procurement.purchase_orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'lines' => $lines,
            'receipts' => $receipts,
        ]);
    }

    public function receiveForm(Request $request, PurchaseOrder $purchaseOrder, UnitConversionService $converter): View
    {
        $this->authorizeAccess($request, $purchaseOrder);
        $purchaseOrder->load('lines.item.baseUnit', 'lines.unitDisplay', 'warehouse');

        if (in_array($purchaseOrder->status, [PurchaseOrder::STATUS_CLOSED, PurchaseOrder::STATUS_CANCELLED], true)) {
            abort(403, 'Purchase order is closed.');
        }

        return view('procurement.purchase_orders.receive', [
            'purchaseOrder' => $purchaseOrder,
            'lines' => $this->lineSummary($purchaseOrder, $converter),
            'default_received_at' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function receive(
        Request $request,
        PurchaseOrder $purchaseOrder,
        PurchaseReceivingService $service
    ): RedirectResponse {
        $this->authorizeAccess($request, $purchaseOrder);

        if (in_array($purchaseOrder->status, [PurchaseOrder::STATUS_CLOSED, PurchaseOrder::STATUS_CANCELLED], true)) {
            return redirect()->route('procurement.purchase-orders.show', $purchaseOrder)
                ->withErrors(['status' => __('Purchase order is closed.')]);
        }

        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $service->receive(
                $purchaseOrder,
                $data['lines'],
                $request->user(),
                $data['received_at'],
                $data['reference'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'lines' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('procurement.purchase-orders.show', $purchaseOrder)
            ->with('status', 'receipt-posted');
    }

    private function authorizeAccess(Request $request, PurchaseOrder $purchaseOrder): void
    {
        $location = $request->attributes->get('active_location');

        if (! $location || $purchaseOrder->location_id !== $location->id) {
            abort(403);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lineSummary(PurchaseOrder $purchaseOrder, UnitConversionService $converter): array
    {
        $receivedTotals = $purchaseOrder->receivedTotalsByItem();

        return $purchaseOrder->lines->map(function ($line) use ($receivedTotals, $converter) {
            $item = $line->item;
            $receivedBase = $receivedTotals[$line->item_id] ?? 0.0;
            $remainingBase = max(0, (float) $line->qty_ordered_in_base - $receivedBase);

            $receivedDisplay = $converter->convert($receivedBase, $item->baseUnit, $line->unitDisplay);
            $remainingDisplay = $converter->convert($remainingBase, $item->baseUnit, $line->unitDisplay);

            return [
                'item_id' => $line->item_id,
                'item_name' => $item->name,
                'unit' => $line->unitDisplay,
                'ordered_display' => (float) $line->qty_display,
                'ordered_base' => (float) $line->qty_ordered_in_base,
                'received_base' => $receivedBase,
                'received_display' => $receivedDisplay,
                'remaining_base' => $remainingBase,
                'remaining_display' => $remainingDisplay,
            ];
        })->toArray();
    }
}
