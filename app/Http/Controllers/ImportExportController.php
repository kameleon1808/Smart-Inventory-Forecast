<?php

namespace App\Http\Controllers;

use App\Domain\ImportJob;
use App\Jobs\ImportCsvJob;
use App\Services\ForecastService;
use App\Services\ImportService;
use App\Services\ProcurementSuggestionService;
use App\Services\StockService;
use App\Services\VarianceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function index(): View
    {
        $jobs = ImportJob::latest()->take(10)->get();

        return view('import-export.index', [
            'jobs' => $jobs,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([
                ImportService::TYPE_ITEMS,
                ImportService::TYPE_UNIT_CONVERSIONS,
                ImportService::TYPE_RECIPES,
                ImportService::TYPE_OPENING_STOCK,
            ])],
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('file')->store('imports');
        $job = ImportJob::create([
            'type' => $data['type'],
            'file_path' => $path,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        ImportCsvJob::dispatch($job->id, (bool) ($data['dry_run'] ?? false));

        return back()->with('status', 'import-dispatched');
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['ledger','balances','suggestions','variance'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return match ($data['type']) {
            'ledger' => $this->exportLedger($request),
            'balances' => $this->exportBalances($request),
            'suggestions' => $this->exportSuggestions($request),
            'variance' => $this->exportVariance($request),
        };
    }

    private function exportLedger(Request $request): StreamedResponse
    {
        $location = $request->attributes->get('active_location');
        $rows = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.location_id', $location->id)
            ->selectRaw('t.happened_at, t.type, l.item_id, l.quantity_in_base, l.unit_cost')
            ->orderBy('t.happened_at')
            ->get();

        return $this->streamCsv('ledger.csv', ['date','type','item_id','quantity_in_base','unit_cost'], $rows->toArray());
    }

    private function exportBalances(Request $request): StreamedResponse
    {
        $location = $request->attributes->get('active_location');
        $service = app(StockService::class);
        $itemIds = DB::table('items')->where('organization_id', $location->organization_id)->pluck('id')->toArray();
        $warehouseId = DB::table('warehouses')->where('location_id', $location->id)->value('id');
        $balances = $service->balancesForItemsInWarehouse($itemIds, (int) $warehouseId);
        $rows = [];
        foreach ($balances as $itemId => $balance) {
            $rows[] = ['item_id' => $itemId, 'balance' => $balance];
        }

        return $this->streamCsv('balances.csv', ['item_id','balance'], $rows);
    }

    private function exportSuggestions(Request $request): StreamedResponse
    {
        $location = $request->attributes->get('active_location');
        $warehouseId = DB::table('warehouses')->where('location_id', $location->id)->value('id');
        $service = app(ProcurementSuggestionService::class);
        $suggestions = $service->suggestions($location->organization_id, $location->id, (int) $warehouseId);
        $rows = $suggestions->map(fn ($row) => [
            'item' => $row['item']->name,
            'suggested_qty' => $row['suggested_qty'],
            'current_stock' => $row['current_stock'],
        ])->toArray();

        return $this->streamCsv('suggestions.csv', ['item','suggested_qty','current_stock'], $rows);
    }

    private function exportVariance(Request $request): StreamedResponse
    {
        $location = $request->attributes->get('active_location');
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $rows = app(VarianceReportService::class)->calculate(
            $location->organization_id,
            $location->id,
            $from,
            $to
        );

        $mapped = $rows->map(fn ($row) => [
            'item_id' => $row['item_id'],
            'expected' => $row['expected'],
            'actual' => $row['actual'],
            'variance' => $row['variance'],
            'variance_percent' => $row['variance_percent'],
        ])->toArray();

        return $this->streamCsv('variance.csv', ['item_id','expected','actual','variance','variance_percent'], $mapped);
    }

    private function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_values((array) $row));
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
