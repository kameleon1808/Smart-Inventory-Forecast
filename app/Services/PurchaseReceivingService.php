<?php

namespace App\Services;

use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\StockTransactionLine;
use App\Domain\Procurement\PurchaseOrder;
use App\Domain\Procurement\PurchaseReceipt;
use App\Domain\Procurement\PurchaseReceiptLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseReceivingService
{
    public function __construct(private readonly UnitConversionService $converter)
    {
    }

    /**
     * @param  array<int, array{item_id:int, qty:float}>  $lines
     */
    public function receive(PurchaseOrder $purchaseOrder, array $lines, User $user, string $receivedAt, ?string $reference = null): PurchaseReceipt
    {
        if (in_array($purchaseOrder->status, [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_CLOSED], true)) {
            throw new InvalidArgumentException('Cannot receive a closed or cancelled purchase order.');
        }

        $purchaseOrder->loadMissing('lines.item.baseUnit', 'lines.unitDisplay', 'organization', 'location', 'warehouse');

        $lineCollection = $this->sanitizeLines($lines);

        if ($lineCollection->isEmpty()) {
            throw new InvalidArgumentException('At least one quantity is required.');
        }

        return DB::transaction(function () use ($purchaseOrder, $lineCollection, $user, $receivedAt, $reference) {
            $receipt = PurchaseReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_at' => Carbon::parse($receivedAt),
                'status' => PurchaseReceipt::STATUS_POSTED,
                'created_by' => $user->id,
            ]);

            $transaction = StockTransaction::create([
                'organization_id' => $purchaseOrder->organization_id,
                'location_id' => $purchaseOrder->location_id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'type' => StockTransaction::TYPE_RECEIPT,
                'status' => StockTransaction::STATUS_POSTED,
                'happened_at' => Carbon::parse($receivedAt),
                'reference' => $reference ?? 'PO #'.$purchaseOrder->id,
                'supplier_name' => $purchaseOrder->supplier_name,
                'created_by' => $user->id,
            ]);

            foreach ($lineCollection as $lineData) {
                $poLine = $purchaseOrder->lines->firstWhere('item_id', $lineData['item_id']);

                if (! $poLine) {
                    throw new InvalidArgumentException('Item does not belong to purchase order.');
                }

                $item = $poLine->item;
                $unitDisplay = $poLine->unitDisplay;

                $qtyInBase = $this->converter->convert(
                    $lineData['qty'],
                    $unitDisplay,
                    $item->baseUnit
                );

                PurchaseReceiptLine::create([
                    'purchase_receipt_id' => $receipt->id,
                    'item_id' => $item->id,
                    'qty_received_in_base' => $qtyInBase,
                    'unit_cost' => $poLine->unit_cost_estimate,
                    'qty_display' => $lineData['qty'],
                    'unit_id_display' => $unitDisplay->id,
                ]);

                StockTransactionLine::create([
                    'stock_transaction_id' => $transaction->id,
                    'item_id' => $item->id,
                    'unit_id' => $unitDisplay->id,
                    'quantity' => $lineData['qty'],
                    'quantity_in_base' => $qtyInBase,
                    'unit_cost' => $poLine->unit_cost_estimate,
                ]);
            }

            $totals = $purchaseOrder->receivedTotalsByItem();

            $fullyReceived = $purchaseOrder->lines->every(function ($line) use ($totals): bool {
                $received = $totals[$line->item_id] ?? 0.0;

                return $received >= ((float) $line->qty_ordered_in_base - 0.0001);
            });

            $purchaseOrder->update([
                'status' => $fullyReceived ? PurchaseOrder::STATUS_CLOSED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ]);

            return $receipt->fresh(['lines']);
        });
    }

    /**
     * @param  array<int, array{item_id:int, qty:float}>  $lines
     */
    private function sanitizeLines(array $lines): Collection
    {
        return collect($lines)
            ->map(fn (array $line) => [
                'item_id' => (int) ($line['item_id'] ?? 0),
                'qty' => (float) ($line['qty'] ?? 0),
            ])
            ->filter(fn (array $line) => $line['item_id'] > 0 && $line['qty'] > 0)
            ->values();
    }
}
